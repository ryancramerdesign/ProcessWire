jQuery(function ($) {

  var $selectAll = $('.InputfieldCheckboxesSelectAll a'),
      $removeField = $('[name^="remove_fields"]'),
      allChecked = false;

  $selectAll.on('click', function () {
    allChecked = !allChecked;
    $removeField.attr('checked', allChecked);
    $(this).text(allChecked ? InputfieldCheckboxesConfig.deSelectAll : InputfieldCheckboxesConfig.selectAll);
  });

  $removeField.on('change', function () {
    var allCheckedLen = $.grep($removeField, function (item) {
      return item.checked;
    }).length;
    if ( allCheckedLen === $removeField.length ) {
      allChecked = true;
      $selectAll.text(InputfieldCheckboxesConfig.deSelectAll);
      return;
    }
    allChecked = false;
    $selectAll.text(InputfieldCheckboxesConfig.selectAll);
  })

});