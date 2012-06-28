
var InputfieldTinyMCEConfigDefaults = {
	mode: 'none', 
	width: "100%", 
	relative_urls: false,	
	convert_urls: false,
	remove_script_host: true,
	theme: "advanced",
	//skin: "process",
	skin: "o2k7",
	inline_styles: false, 
	cleanup: true,
	cleanup_on_startup: true, 
	apply_source_formatting: false, 
	verify_html: true, 
	verify_css_classes: false, 
	theme_advanced_toolbar_location: "top",
	theme_advanced_toolbar_align: "left",
	theme_advanced_resizing: true,
	theme_advanced_resize_horizontal: false,
	theme_advanced_statusbar_location: "bottom",
	paste_create_paragraphs: true, 
	paste_create_linebreaks: false, 
	paste_auto_cleanup_on_paste: true, 
	paste_convert_middot_lists: true, 
	paste_convert_headers_to_string: true, 
	paste_remove_spans: true, 
	paste_remove_styles: true, 
	paste_strip_class_attributes: 'all', 
	forced_root_block: 'p', 
	force_br_newlines: false, 
	dialog_type: "modal",
	content_css: config.InputfieldTinyMCE.url + 'content.css', 
	remove_linebreaks: false, // required for preelementfix plugin
	entity_encoding: 'raw', 

	/*
	// these are ready to use if needed
	setup: function(ed) {
		$('#' + ed.id).triggerHandler({
			type: 'setup',
			ed: ed
			}); 
	},

	onchange_callback: function(ed) {
		$('#' + ed.id).triggerHandler({
			type: 'change', 
			ed: ed
			}); 
	},
	*/

	paste_preprocess : function(pl, o) {
		if(o.content.indexOf('<br') > -1) {
			o.content = o.content.replace(/\<br\>\<br\>/gi, '</p><p>');
			o.content = '<p>'+o.content+'</p>';
		}
	},

	paste_postprocess: function(pl, o) {
		var ed = pl.editor, dom = ed.dom;
		// Remove all img tags: comment the below out if you don't want img and a tags removed during paste
		tinymce.each(dom.select('*', o.node), function(el) {    
			var tag = el.tagName.toLowerCase();
			if (tag == "img") {    
				dom.remove(el, 1); // 1 = KeepChildren
			}
			dom.setAttrib(el, 'style', '');
		});
	}, 

	advimagescale_resize_callback: function(ed, node) {
		var $node = $(node); 
		var src = $node.attr('src'); 
		var w = $node.attr('width');
		var h = $node.attr('height'); 
		var url = config.urls.admin + 'page/image/resize?file=' + src + '&width=' + w;

		$.get(url, function(data) {
			var $div = $("<div></div>").html(data);
			$img = $div.find("#selected_image");
			// note IE8 won't properly read the width/height attrs via ajax
			// so we provide the width/height in separate fields
			$width = $div.find("#selected_image_width").text(); 
			$height = $div.find("#selected_image_height").text();
			ed.dom.setAttrib(node, 'src', $img.attr('src'));
			ed.dom.setAttrib(node, 'width', $width); 
			ed.dom.setAttrib(node, 'height', $height); 
		});
	}
}; 

$(document).ready(function() {

	var InputfieldTinyMCEPlugins = ['pwimage', 'pwlink', 'advimagescale', 'preelementfix']; 

	$.each(InputfieldTinyMCEPlugins, function(key, value) {
		tinymce.PluginManager.load(value, config.InputfieldTinyMCE.url + 'plugins/' + value + '/editor_plugin.js'); 	
	}); 

	$.each(config.InputfieldTinyMCE.elements, function(key, value) {
		var custom_plugins = '';
		// @soma--> load custom thirdparty plugins from outside core
		if(config[value].custom_plugins) {
			custom_plugins = loadCustomPlugins(config[value].custom_plugins);
			config[value].custom_plugins = null;
		}
		// convert config object values to objects if necessary <--@soma
		config[value] = convertObjects(config[value]);

		tinyMCE.settings = $.extend(InputfieldTinyMCEConfigDefaults, config[value]); 
		if(config.InputfieldTinyMCE.language.length > 0) tinyMCE.settings.language = config.InputfieldTinyMCE.language; 
		tinyMCE.settings.plugins += ', -pwimage, -pwlink, -advimagescale, -preelementfix' + custom_plugins;
		tinyMCE.execCommand('mceAddControl', true, value); 
	
	}); 

	// @soma-->
	// convert config string that start with a [ to a js object
	// this ensures it works with object type of configurations like template_templates:[{title:'mytemplate'},...]
	function convertObjects(config) {
		$.each(config, function(key, value) {
			if(typeof value == "string") {
				if(value.substr(0, 1) == "[") config[key] = eval(value);
			}
		});
		return config;
	};

	// load additional third party plugins and create "-name" string
	function loadCustomPlugins(config) {
		var plugins = '';
		$.each(config, function(key, value) {
			$.each(value, function(k,v) {
				tinymce.PluginManager.load(k, v + '/editor_plugin.js');
				plugins += ", -" + k;
			});
		});
		return plugins;
	};
	// <--@soma

}); 

