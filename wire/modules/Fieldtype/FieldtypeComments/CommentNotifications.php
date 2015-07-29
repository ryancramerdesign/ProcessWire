<?php

class CommentNotifications extends Wire {
	
	protected $page;	
	protected $field;

	/*
	 * FYI
	 * 
	protected $commentActions = array(
		"1" => "Comment received", 
		"2" => "Comment action success",
		"3" => "Comment action failure", 
		"approve" => "Approve", 
		"pending" => "Pending", 
		"spam" => "Spam", 
		"unsub" => "Unsubscribe from notifications", 
		"confirm" => "Subscribe to notifications",
	);
	 */
	
	public function __construct(Page $page, Field $field) {
		$this->page = $page;
		$this->field = $field;
	}


	/**
	 * Send notification email to specified admin to review the comment
	 * 
	 * @param Comment $comment
	 * @return int Number of emails sent
	 *
	 */
	protected function ___sendAdminNotificationEmail(Comment $comment) {

		if(!$this->field->notificationEmail) return false;
		
		$field = $this->field;
		$page = $this->page; 
		$actionURL = $page->httpUrl . "?field=$field->name&page_id=$page->id&code=$comment->code&comment_success=";

		// skip notification when spam
		if($comment->status == Comment::statusSpam && !$field->notifySpam) return;

		if($comment->status == Comment::statusPending) {
			$status = $this->_("Pending Approval");
			$actionURL .= "approve";
			$actionLabel = $this->_('Approve Now'); 
		} else if($comment->status == Comment::statusApproved) {
			$status = $this->_("Approved");
			$actionURL .= "spam";
			$actionLabel = $this->_('Mark as SPAM'); 
		} else if($comment->status == Comment::statusSpam) {
			$status = sprintf($this->_("SPAM - will be deleted automatically after %d days"), $field->deleteSpamDays);
			$actionURL .= "approve";
			$actionLabel = $this->_('Not SPAM: Approve Now'); 
		} else {
			$actionURL = '';
			$actionLabel = '';
			$status = "Unknown";
		}

		$subject = sprintf($this->_('Comment posted to: %s'), $this->wire('config')->httpHost . " - $page->title");
		
		$values = array(
			'page' => array(
				'label' => $this->_x('Page', 'email-body'), 
				'value' => $page->httpUrl
			),
			'cite' => array(
				'label' => $this->_x('From', 'email-body'),
				'value' => $comment->cite, 
			),	
			'email' => array(
				'label' => $this->_x('Email', 'email-body'),
				'value' => $comment->email
			), 
			'website' => array(
				'label' => $this->_x('Website', 'email-body'),
				'value' => $comment->website, 
			), 
			'status' => array(
				'label' => $this->_x('Status', 'email-body'),
				'value' => $status
			),
			'action' => array(
				'label' => $this->_x('Action', 'email-body'), 
				'value' => "$actionLabel: $actionURL"
			), 
		);
		
		$values['text'] = array(
			'label' => $this->_x('Text', 'email-body'),
			'value' => $comment->text
		);
		
		$body = '';
		$bodyHTML = 
			"<html><head><body>\n" . 
			"<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
		
		foreach($values as $key => $info) {
			
			$body .= "$info[label]: $info[value]\n";
		
			if($key == 'action') continue; 
			
			if($key == 'status') {
				$value = "$info[value] (<a href='$actionURL'>$actionLabel</a>)";
				
			} else if($key == 'page') { 
				$value = "<a href='$info[value]'>$page->title</a> (<a href='$page->editUrl'>" . $this->_('Edit') . ")";
						
			} else {
				$value = $this->wire('sanitizer')->entities($info['value']);
			}
			
			$bodyHTML .= 
				"<tr>" . 
				"<td style='padding: 5px; border-bottom: 1px solid #ccc; font-weight: bold; vertical-align: top'>$info[label]</td>" . 
				"<td style='padding: 5px; border-bottom: 1px solid #ccc; width: 90%;'>$value</td>" . 
				"</tr>\n";
		}
		
		$bodyHTML .= "</table></body></html>\n\n";

		$emails = $this->parseEmails($field->notificationEmail); 	
		if(count($emails)) {
			$mail = wireMail();
			foreach($emails as $email) $mail->to($email);
			$mail->subject($subject)->body($body)->bodyHTML($bodyHTML);
			$fromEmail = $this->getFromEmail();
			if($fromEmail) $mail->from($fromEmail);
			return $mail->send();
		}
		
		return 0; 	
	}
	
	protected function getFromEmail() {
		if($this->field->fromEmail) {
			$fromEmail = $this->field->fromEmail;
		} else {
			$fromEmail = $this->_x('processwire', 'email-from-name') . '@' . $this->wire('config')->httpHost;
		}
		return $fromEmail;
	}

	/**
	 * Given a string containing emails or references to them, convert to array of emails
	 * 
	 * Recognized email references are: 
	 * 	- Regular email address: "email@company.com"
	 * 	- Pull email from field on current page: "field:email_field_name"
	 * 	- Pull email from user account (field=email): "user:karen"
	 * 	- Pull email from page ID and field name: "123:email_field_name"
	 * 	- Pull email from page path and field name: "/path/to/page:email_field_name"
	 * 
	 * @param $str
	 * @return array
	 * 
	 */
	public function parseEmails($str) {
		
		$str = str_replace(',', ' ', $str); 	
		$emails = array();
		
		foreach(explode(' ', $str) as $value) {
			$email = null;
			
			if(strpos($value, '@')) {
				// regular email address
				$email = $value; 
				
			} else if(strpos($value, ':')) {
				// reference to email address somewhere else			
				list($a, $b) = explode(':', $value); 
				if(empty($a) || empty($b)) continue; 
				if($a == 'field') {
					$email = $this->page->get($b); 	
				} else if($a == 'user') {
					$user = $this->wire('users')->get($b); 
					if($user && $user->id) $email = $user->email;	
				} else {
					// page ID or page path
					$page = $this->wire('pages')->get($a); 
					if($page->id) $email = $page->get($b); 
				}
				
			} else {
				// unrecognized
			}
			
			if($email) {
				$email = $this->wire('sanitizer')->email($email);
				if($email) $emails[] = $email;
			}
		}
		return $emails; 
	}


	/**
	 * Check for a GET variable comment approval code and take action is valid
	 *
	 * @return array(
	 * 	'success' => true|false, whether or not comment was updated
	 * 	'valid' => true|false, whether or not the request was valid 
	 * 	'action' => approve|spam|pending|unknown
	 * 	'message' => 'string', 
	 * 	'pageID' => id of page or 0 if not known
	 * 	'fieldName' => 'name of field or blank if not known'
	 * 	'commentID' => id of comment or 0 if not applicable,
	 * )
	 *
	 */
	public function checkActions() {
		
		$info = array(
			'success' => false,
			'valid' => false, 
			'action' => 'unknown',
			'message' => '', 	
			'pageID' => 0, 
			'fieldName' => '', 
			'commentID' => 0, 
		);

		$get = $this->wire('input')->get;
		
		$action = $get->comment_success;
		if(!$action) return $info;
		$action = $this->wire('sanitizer')->pageName($action);
		$status = null;

		switch($action) {
			case 'approve': $status = Comment::statusApproved; break;
			case 'spam': $status = Comment::statusSpam; break;
			case 'pending': $status = Comment::statusPending; break;
			case 'confirm': break;
			case 'unsub': break;
			case 'upvote': break;
			case 'downvote': break;
			default:
				$info['message'] = "Unknown action: $action";
				return $info;
		}
		$info['action'] = $action;
		
		if($action == 'unsub' || $action == 'confirm') {
			// early exit for unsub action
			$subcode = $this->wire('sanitizer')->fieldName(substr($this->wire('input')->get('subcode'), 0, 40));
			if($action == 'unsub') {
				if(strlen($subcode) && $this->modifyNotifications($subcode, false)) {
					$info['valid'] = true;
					$info['success'] = true;
					$info['message'] = $this->_('You have unsubscribed from comment notifications on this page.');
				} else {
					$info['message'] = 'Error disabling notifications';
				}
			} else if($action == 'confirm') {
				if(strlen($subcode) && $this->modifyNotifications($subcode, true)) {
					$info['valid'] = true;
					$info['success'] = true;
					$info['message'] = $this->_('You have confirmed receipt of notifications from this page.');
				} else {
					$info['message'] = 'Error confirming notifications';
				}
			}
			return $info;
			
		} else if($action == 'upvote' || $action == 'downvote') {
			$info = array_merge($info, $this->field->type->checkVoteAction($this->page)); 	
			return $info;
		}

		$page = $this->page; 
		$pageID = $get->page_id;
		$info['pageID'] = $pageID; 
		if($pageID != $page->id) {
			$info['message'] = "Invalid page specified: $pageID";
			return $info;
		}
		
		$fieldName = $this->wire('sanitizer')->fieldName($get->field);
		$info['fieldName'] = $fieldName; 
		if(!$fieldName || $fieldName != $this->field->name) {
			$info['message'] = "Incorrect field name: $fieldName";
			return $info;
		}
		
		$field = $this->wire('fields')->get($fieldName);
		if(!$field) {
			$info['message'] = "Unknown field: $fieldName";
			return $info;
		}
		
		if(!$page->template->fieldgroup->hasField($field)) {
			$info['message'] = "This page does not have field: $fieldName";
			return $info;
		}
		
		$code = substr($get->code, 0, 128);
		if(!strlen($code)) {
			$info['message'] = "No approval code provided";
			return $info;
		}
		
		$info['valid'] = true; // all required vars are present
		
		$comment = $this->field->type->getCommentByCode($page, $field, $code);
		if(!$comment) {
			$info['message'] = "Invalid comment code or code has already been used";
			return $info;
		}
		$info['commentID'] = $comment->id; 

		$properties = array(
			'status' => $status, // update status
			'code' => null // remove code, since it is a 1-time use code
		);

		if($this->field->type->updateComment($page, $field, $comment, $properties)) {
			$info['message'] = "Updated comment $comment->id to '$action'";
			$info['success'] = true; 
			$this->wire('log')->message($info['message']);
		} else {
			$info['message'] = "Failed to update comment $comment->id to '$action'";
			$this->wire('log')->error($info['message']);
		}
		
		return $info;
	}
	
	/**
	 * Given a subscriber code, modify notifications on any comments where their email is present
	 * 
	 * @param string $subcode 40 digit subscriber code
	 * @param bool $enable Whether to enable or disable notifications
	 * @param bool $all Specify true to disable notifications site-wide, rather than just current page
	 * @throws WireException
	 * @return bool
	 * 
	 */
	public function modifyNotifications($subcode, $enable, $all = false) {
		
		$table = $this->wire('database')->escapeTable($this->field->getTable());	
		$sql = "SELECT email FROM `$table` WHERE pages_id=:pages_id AND subcode=:subcode"; 
		$query = $this->wire('database')->prepare($sql);
		$query->bindValue(':pages_id', $this->page->id);
		$query->bindValue(':subcode', $subcode); 
		$query->execute();
		$email = '';
		if($query->rowCount()) list($email) = $query->fetch(PDO::FETCH_NUM); 
		if(!strlen($email)) return false;
	
		if($all) {
			$sql = "SELECT id, flags FROM `$table` WHERE email=:email";
		} else {
			$sql = "SELECT id, flags FROM `$table` WHERE pages_id=:pages_id AND email=:email";
		}
		$query = $this->wire('database')->prepare($sql);
		if(!$all) $query->bindValue(':pages_id', $this->page->id);
		$query->bindValue(':email', $email); 
		$query->execute();
		if(!$query->rowCount()) return false;
		
		while($row = $query->fetch(PDO::FETCH_NUM)) {
			list($id, $flags) = $row; 
			if($enable) {
				// enable
				$flags = $flags | Comment::flagNotifyConfirmed;
			} else {
				// disable
				if($flags & Comment::flagNotifyAll) {
					$flags = $flags & ~Comment::flagNotifyAll;
				} else if($flags & Comment::flagNotifyReply) {
					$flags = $flags & ~Comment::flagNotifyReply;
				} else {
					continue;
				}
			}
			$sql = "UPDATE `$table` SET flags=:flags WHERE id=:id"; 
			$update = $this->wire('database')->prepare($sql);
			$update->bindValue(':flags', $flags);
			$update->bindValue(':id', $id); 
			$update->execute();
		}
	
		if($enable) {
			$this->wire('log')->message('Confirmed notifications: ' . $email);
		} else {
			$this->wire('log')->message('Unsubscribed from notifications: ' . $email);
		}
	
		return true; 
	}
	
	/**
	 * Send a user (not admin) notification email
	 * 
	 * @param Comment $comment
	 * @param $email
	 * @param $subcode
	 * 
	 */
	public function ___sendNotificationEmail(Comment $comment, $email, $subcode) {
		
		$page = $comment->getPage();
		$title = $page->get('title|name'); 
		$url = $page->httpUrl . "#Comment$comment->id";
		$unsubURL = $page->httpUrl . "?comment_success=unsub&subcode=$subcode";
		$subject = $this->_('New comment posted:') . " $title"; // Email subject
		$body = $this->_('Posted at: %s') . "\n"; 
		$body .= sprintf($this->_('Posted by: %s'), $this->wire('sanitizer')->name($comment->cite)) . "\n";
		$bodyHTML = "<p>" . nl2br(sprintf($body, "<a href='$url'>$page->title</a>")) . "</p>";
		$body = sprintf($body, $page->title) . "\n" . sprintf($this->_('URL: %s'), $url); 
		$footer = $this->_('Disable Notifications');
		$body .= "\n\n$footer: $unsubURL";	
		$bodyHTML .= "<p><a href='$unsubURL'>$footer</a></p>";
		
		$mail = wireMail();
		$mail->to($email)->subject($subject)->body($body)->bodyHTML($bodyHTML);
		$fromEmail = $this->getFromEmail();
		if($fromEmail) $mail->from($fromEmail);
		
		$result = $mail->send();
		if($result) {
			$this->wire('log')->message("Sent comment notification email to $email"); 
		} else {
			$this->wire('log')->error("Failed sending comment notification to $email"); 
		}
		
		return $result;
	}

	/**
	 * Send confirmation/opt-in email for notifications (not yet active)
	 * 
	 * @param Comment $comment
	 * @param $email
	 * @param $subcode
	 * @return mixed
	 * @throws WireException
	 * 
	 */
	public function ___sendConfirmationEmail(Comment $comment, $email, $subcode) {
		
		$page = $comment->getPage();
		$title = $page->get('title|name');
		$url = $page->httpUrl;
		$confirmURL = $page->httpUrl . "?comment_success=confirm&subcode=$subcode";
		$subject = $this->_('Please confirm notification') . " - $title"; // Email subject
		$body = $this->_('You requested to be notified of replies to your comment at %s. Please confirm this by clicking the link below. If you did not request this then please ignore this email.');
		$bodyHTML = sprintf($body, "<a href='$url'>" . $this->wire('config')->httpHost . "</a>");
		$body = sprintf($body, $this->wire('config')->httpHost);
		$footer = $this->_('Confirm Notifications');
		$body .= "\n\n$footer: $confirmURL";
		$bodyHTML .= "<p><strong><a href='$confirmURL'>$footer</a></strong></p>";

		$mail = wireMail();
		$mail->to($email)->subject($subject)->body($body)->bodyHTML($bodyHTML);
		$fromEmail = $this->getFromEmail();
		if($fromEmail) $mail->from($fromEmail);

		$result = $mail->send();
		if($result) {
			$this->wire('log')->message("Sent confirmation/opt-in email to $email");
		} else {
			$this->wire('log')->error("Failed sending confirmation/opt-in email to $email");
		}

		return $result;
	}
	
}
