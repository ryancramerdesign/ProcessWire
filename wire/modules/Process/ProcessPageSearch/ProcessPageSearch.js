

ProcessPageSearch = {

        t: 0,
	defaultLabel: 'Search',
        lastQuery: '',

        search: function() {
                var query = $('#ProcessPageSearchQuery').val();
                if(query == this.lastQuery) return false;
                $('#ProcessPageSearchStatus').text('Searching');
                $('#ProcessPageSearchLiveResults').load(config.urls.admin + 'page/search/', { q: query }, function(data) {
                        var numResults = parseInt($('#search_num_results').hide().text());
                        if(numResults) {
                                $('#search_results').fadeIn("fast").find("a").click(function() {
                                        // reduces time that horiz scrollbar shows in FF
                                        $("#search_results").css("overflow", "hidden");
                                });
                                $('#search_status').text(numResults + ' matches');
                        } else {
                                $('#search_status').text('No matches');
                        }
                });
                this.lastQuery = query;
                return false;
        },

        hide: function() {
                $('#search_results').fadeOut("fast", function() { $(this).remove(); });
                $('#search_status').text('');
                $('#search_query').val(this.defaultLabel);
        },

        init: function() {
		this.lastQuery = this.defaultLabel;
                $('#container').append('<div id="search_container"></div><div id="search_status"></div>');

                $('#search_form').unbind().submit(function() {
                        return this.search();
                });

                $('#search_query').attr('autocomplete', 'off').focus(function() {
                        $(this).keyup(function() {
                                if($(this).val().length < 4) return;
                                if(this.t) clearTimeout(this.t);
                                this.t = setTimeout("liveSearch.search()", 500);
                        });

                }).blur(function() {
                        setTimeout("liveSearch.hide()", 250);
                });
        }

}

$(document).ready(function() {
	var $searchQuery = $("#ProcessPageSearchQuery"); 
	var label = $('#ProcessPageSearchSubmit').val();

	if($searchQuery.attr('value').length < 1) {
		$searchQuery.attr('value', label);
		$searchQuery.css('opacity', 0.6)
	}

	$searchQuery.focus(function() {
			if($(this).val() == label) $(this).val('');
			$(this).css('opacity', 1.0); 
		}).blur(function() {
			if($(this).val().length < 1) $(this).val(label).css('opacity', 0.6); 
		}); 
});
