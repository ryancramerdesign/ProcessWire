
var InputfieldPageName = {
	sanitize: function(name) {

		// replace leading and trailing whitespace 
		name = jQuery.trim(name).toLowerCase();  
	  
		// multi-character replacements
		var srch = ['ä',  'ö',  'ü',  'đ',  'ж',  'х',  'ц',  'ч',  'ш',  'щ',    'ю',  'я'];
		var repl = ['ae', 'oe', 'ue', 'dj', 'zh', 'kh', 'tc', 'ch', 'sh', 'shch', 'iu', 'ia']; 
	
		// change single characters that translate to multi-char 
		for(var cnt = 0; cnt < srch.length; cnt++) {
			var c = srch[cnt];
			if(name.indexOf(c) > -1) {
				var re = new RegExp(c, 'g'); 
				name = name.replace(re, repl[cnt]); 
			}	
		}
	
		// single character, utf8 accented to ascii translation
		var str1 = ":,àáâèéëêìíïîòóôùúûñçčćďĺľńňŕřšťýžабвгдеёзийклмнопрстуфыэ";
		var str2 = "--aaaeeeeiiiiooouuuncccdllnnrrstyzabvgdeeziiklmnoprstufye";
	
		// change common accented characters to ascii equivalent
		for(var cnt = 0, n = str1.length; cnt < n; cnt++) {
			var c = str1.charAt(cnt); 
			if(name.indexOf(c) > -1) { 
				var re = new RegExp(str1.charAt(cnt), 'g'); 
				name = name.replace(re, str2.charAt(cnt));
			}
		}
	
		// replace invalid with dash
		name = name.replace(/[^-_.a-z0-9 ]/g, '-');
	
		// convert whitespace to dash
		name = name.replace(/\s+/g, '-') 
	
		// convert multiple dashes or dots to single
		name = name.replace(/--+/g, '-'); 
	
		// convert multiple dots to single
		name = name.replace(/\.\.+/g, '.'); 
	
		// remove ugly combinations next to each other
		name = name.replace(/(\.-|-\.)/g, '-'); 
	
		// remove leading or trailing dashes, underscores and dots
		name = name.replace(/(^[-_.]+|[-_.]+$)/g, ''); 

		// make sure it's not too long
		if(name.length > 128) name = name.substring(0, 128); 
	
		return name;
	},

	updatePreview: function($t, value) {
		$t.parent('p').siblings(".InputfieldPageNameURL").children("strong").text((value.length > 0 ? value + '/' : ''))
	}
};

jQuery(document).ready(function($) {

	$(".InputfieldPageName").find("input[type=text]").keyup(function() {
		var value = InputfieldPageName.sanitize($(this).val());
		InputfieldPageName.updatePreview($(this), value); 
		
	}).blur(function() {
		var value = InputfieldPageName.sanitize($(this).val());
		$(this).val(value); 
		InputfieldPageName.updatePreview($(this), value); 
	}).keyup();
}); 
