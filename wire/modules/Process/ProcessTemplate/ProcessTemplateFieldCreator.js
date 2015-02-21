// made by adrian
function TemplateFieldAddDialog() {

    var $a = $(this);
    var closeOnSave = false;
    var $iframe = pwModalWindow($a.attr('href'), {}, 'large'); 

    $iframe.load(function() {

        var buttons = [];
        var $icontents = $iframe.contents();
        var n = 0;

        // hide things we don't need in a modal context
        $icontents.find('#breadcrumbs ul.nav, #Inputfield_submit_save_field_copy').hide();

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
                            $iframe.dialog('close');

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

        $iframe.setButtons(buttons); 
    });

    return false;
}



$(document).ready(function() {
    $('#wrap_fieldgroup_fields p.description a').click(TemplateFieldAddDialog);
});
