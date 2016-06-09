
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
		$this.tabs({ active: ProcessWire.config.LanguageTabs.activeTab });
		if($inputfield.length) $inputfield.addClass('hasLangTabs');
		var $parent = $this.parent('.InputfieldContent'); 
		if($parent.length) {
			var $span = $("<span></span>")
				.attr('title', ProcessWire.config.LanguageTabs.title)
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
		$this.attr("title", ProcessWire.config.LanguageTabs.labelClose)
			.find('i').removeClass("fa-folder-o").addClass("fa-folder-open-o");
	} else {
		$content.addClass('langTabsContainer');
		$inputfield.addClass('hasLangTabs');
		$this.removeClass('langTabsOff');
		$langTabs.tabs();
		$(this).attr("title", ProcessWire.config.LanguageTabs.labelOpen)
			.find('i').addClass("fa-folder-o").removeClass("fa-folder-open-o");
	}
	return false;
}

function hideLanguageTabs() {
	
	// hide all inputs except default (for cases where all inputs are shown rather than tabs)
	$(".InputfieldContent").each(function() {
		var n = 0;
		$(this).children('.LanguageSupport').each(function() {
			if(++n == 1) {
				$(this).closest('.Inputfield').addClass('hadLanguageSupport');
				return;
			}
			$(this).addClass('langTabsHidden');
		});
	});

	// make sure first tab is clicked
	var $tab = $(".langTabs").find("li:eq(0)");
	if(!$tab.hasClass('ui-state-active')) $tab.find('a').click();

	// hide the tab toggler
	$(".langTabsToggle, .LanguageSupportLabel:visible, .langTabs > ul").addClass('langTabsHidden');
	$(".hasLangTabs").removeClass("hasLangTabs").addClass("hadLangTabs");
}

function unhideLanguageTabs() {
	// un-hide the previously hidden language tabs
	$('.langTabsHidden').removeClass('langTabsHidden');
	$('.hadLangTabs').removeClass('hadLangTabs').addClass('hasLangTabs');
	$('.hadLanguageSupport').removeClass('hadLanguageSupport'); // just .Inputfield with open inputs
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

