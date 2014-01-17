$(function(){

	var $langField = $(".Inputfield").has(".LanguageSupport");
	$langField.each(function(){

		var $this = $(this);

		var $langHeader =  $this.children(".InputfieldHeader");
		var $langContent =  $this.children(".InputfieldContent");
		var $langSupport = $langContent.children(".LanguageSupport");


		if ( $langSupport.length > 1) { //should avoid applying to single language field like Language "alt" fields


			var fieldID = $this.attr("id"); // we will later combine the input ID with the index to create unique anchors for jqueryui tabs to use
			var i = 0;

			// markup variables
			var LangTabsBox = "<div class='langTabs'><ul></ul></div>";
			


			// add the markup the LangTabs will go inside
			$langContent.append(LangTabsBox);
			var $langTabsBox = $this.find(".langTabs"); 
			var $langTabs = $langTabsBox.children("ul"); 

			$langContent.parent('.Inputfield').addClass('hasLangTabs'); 


			$langSupport.each(function(){

				var $this = $(this);


				var label = $this.children(".LanguageSupportLabel").text();
				// console.log(label);
				var anchor = fieldID+i;

				var $textarea = $this.find("textarea");
				var $input = $this.find("input");
				var $textareaInline = $this.find("div[contenteditable]"); 
				var fieldValueClass = 'langTabEmpty';

				if($textarea.length > 0) { 
					if($textarea.text().length > 0) fieldValueClass = '';

				} else if($textareaInline.length > 0) { 
					if($textareaInline.text().length > 0) fieldValueClass = '';

				} else if($input.length > 0 && $input.eq(0).val().length > 0) {
					if($input.attr('name').indexOf('_pw_page_name') === 0) {
						// defer to the "active" checkbox in determining whether it shows empty class or not
						var $checkbox = $input.next('label').children('input[type=checkbox]'); 	
						if(!$checkbox.size() || $checkbox.is(":checked")) fieldValueClass = '';
					} else {
						fieldValueClass = '';
					}
				}
				
				$langTabs.append("<li><a class='"+fieldValueClass+"' href='#"+anchor+"'>"+label+"</a></li>");
				$this.attr("id", anchor).appendTo($langTabsBox);

				i++;
			});

			var $span = $("<span></span>")
				.attr('title', config.LanguageTabs.title)
				.attr('class', 'langTabsToggle')
				.append("<i class='fa fa-folder-o'></i>"); 

			$langContent.addClass("langTabsContainer").siblings("label").prepend($span); 

			$langTabsBox.tabs({ active: config.LanguageTabs.activeTab });
		}

		// state toggle button to turn tabs on and off
		// will add class of "langTabsOff" so we can hide the menu markup

		var $langTabsToggle = $langHeader.children(".langTabsToggle");
		$langTabsToggle.toggle(function(){
			$langContent.removeClass("langTabsContainer");
			$langContent.parent('.Inputfield').removeClass('hasLangTabs'); 
			$this.addClass('langTabsOff');
			$langTabsBox.tabs( "destroy" );
			$(this).attr("title","Collapse Language Tabs").find('i').removeClass("fa-folder-o").addClass("fa-folder-open-o");
			
		}, function(){
			$langContent.addClass("langTabsContainer");
			$langContent.parent('.Inputfield').addClass('hasLangTabs'); 
			$this.removeClass('langTabsOff');
			$langTabsBox.tabs();
			$(this).attr("title","Expand Language Tabs").find('i').addClass("fa-folder-o").removeClass("fa-folder-open-o");
		});

		$langTabsToggle.mouseout(function(){
			$(this).removeClass("ui-state-active");
		});
	});




}); 
