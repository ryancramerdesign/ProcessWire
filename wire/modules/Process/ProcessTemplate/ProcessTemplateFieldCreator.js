// made by adrian
function TemplateFieldAddDialog() {

    var $a = $(this);
    var url = $a.attr('href');
    var closeOnSave = false;
    var $iframe = $('<iframe id="fieldAddFrame" frameborder="0" src="' + url + '"></iframe>');
    var windowWidth = $(window).width()-100;
    var windowHeight = $(window).height()-220;

    var $dialog = $iframe.dialog({
        modal: true,
        height: windowHeight,
        width: windowWidth,
        position: [50,49]
    }).width(windowWidth).height(windowHeight);

    $iframe.load(function() {

        var buttons = [];
        var $icontents = $iframe.contents();
        var n = 0;
        var title = $icontents.find('title').text();

        // set the dialog window title
        $dialog.dialog('option', 'title', title);

        // hide things we don't need in a modal context
        $icontents.find('#masthead, #breadcrumbs ul.nav, #Inputfield_submit_save_field_copy, #footer').hide();

        // copy buttons in iframe to dialog
        $icontents.find("#content form button.ui-button[type=submit]").each(function() {
            var $button = $(this);
            var text = $button.text();
            var skip = false;
            // avoid duplicate buttons
            for(i = 0; i < buttons.length; i++) {
                if(buttons[i].text == text || text.length < 1) skip = true;
            }
            if(!skip) {
                buttons[n] = {
                    'text': text,
                    'class': ($button.is('.ui-priority-secondary') ? 'ui-priority-secondary' : ''),
                    'click': function() {
                        $button.click();
                        if(closeOnSave) setTimeout(function() {
                            var newFieldId = $("iframe").contents().find("#Inputfield_id:last").val();
                            $dialog.dialog('close');

                            var numOptions = $('#fieldgroup_fields option').size();

                            $("#fieldgroup_fields option").eq(1).before($("<option></option>").val(newFieldId).text($("iframe").contents().find("#Inputfield_name").val()));
                            $('#fieldgroup_fields option[value="'+newFieldId+'"]')
                                .attr('id', 'asm0option'+numOptions)
                                .attr('data-desc', ($("iframe").contents().find("#field_label").val()))
                                .attr('data-status', ($("iframe").contents().find("#Inputfield_type option:selected").text()));

                            $("#asmSelect0 option").eq(1).before($("<option></option>").val(newFieldId).text($("iframe").contents().find("#Inputfield_name").val()));
                            $("#asmSelect0").find('option:selected').removeAttr("selected");
                            $('#asmSelect0 option[value="'+newFieldId+'"]')
                                .attr('rel', 'asm0option'+numOptions)
                                .attr('selected', 'selected')
                                .addClass('asmOptionDisabled')
                                .attr('disabled', 'disabled')
                                .trigger('change')
                                .removeAttr("selected");

                            $("iframe").remove(); // clear iframe from dom as each new field was getting the same ID as the previous one

                        }, 500);
                        closeOnSave = true;
                    }
                };
                n++;
            };
            $button.hide();
        });

        if(buttons.length > 0) $dialog.dialog('option', 'buttons', buttons);
        $dialog.width(windowWidth).height(windowHeight);
    });

    return false;
}



$(document).ready(function() {
    $('#wrap_fieldgroup_fields p.description a').click(TemplateFieldAddDialog);
});
