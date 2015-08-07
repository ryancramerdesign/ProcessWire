
/**
 * Establish tabs for all ".langTabs" elements in the given element
 * 
 * @param $form
 * 
 */
function setupLanguageTabs($form) {
	var $langTabs;
	if($form.hasClass('langTabs')) $langTabs = $form; 
		else $langTabs = $form.find('.langTabs');
	$langTabs.each(function() {
		var $this = $(this);
		if($this.hasClass('ui-tabs')) return;
		var $inputfield = $this.closest('.Inputfield');
		var $content = $inputfield.children('.InputfieldContent'); 
		if(!$content.hasClass('langTabsContainer')) {
			if($inputfield.find('.langTabsContainer').length == 0) $content.addClass('langTabsContainer');
		}
		$this.tabs({ active: config.LanguageTabs.activeTab });
		if($inputfield.length) $inputfield.addClass('hasLangTabs');
		var $parent = $this.parent('.InputfieldContent'); 
		if($parent.length) {
			var $span = $("<span></span>")
				.attr('title', config.LanguageTabs.title)
				.attr('class', 'langTabsToggle')
				.append("<i class='fa fa-folder-o'></i>");
			$parent.prev('.InputfieldHeader').append($span);
		}
	});
}

/**
 * Click event that toggles language tabs on/off
 * 
 * @returns {boolean}
 * 
 */
function toggleLanguageTabs() {
	var $this = $(this);
	var $header = $this.closest('.InputfieldHeader');
	var $content = $header.next('.InputfieldContent');
	var $inputfield = $header.parent('.Inputfield');
	var $langTabs = $content.children('.langTabs');

	if($content.hasClass('langTabsContainer')) {
		$content.find('.ui-tabs-nav').find('a').click(); // activate all (i.e. for CKEditor)
		$content.removeClass('langTabsContainer');
		$inputfield.removeClass('hasLangTabs');
		$this.addClass('langTabsOff');
		$langTabs.tabs('destroy');
		$this.attr("title", config.LanguageTabs.labelClose)
			.find('i').removeClass("fa-folder-o").addClass("fa-folder-open-o");
	} else {
		$content.addClass('langTabsContainer');
		$inputfield.addClass('hasLangTabs');
		$this.removeClass('langTabsOff');
		$langTabs.tabs();
		$(this).attr("title", config.LanguageTabs.labelOpen)
			.find('i').addClass("fa-folder-o").removeClass("fa-folder-open-o");
	}
	return false;
}

$(document).ready(function() { 
	$(document).on('click', '.langTabsToggle', toggleLanguageTabs); 
	$(document).on('reloaded', '.Inputfield', function() {
		setupLanguageTabs($(this));
	});
	$(document).on('AjaxUploadDone', '.InputfieldHasFileList .InputfieldFileList', function() {
		setupLanguageTabs($(this));
	});
}); 

