jQuery(function ($) {

  /**
   * Iterating over all occurrences of the input field
   * on the page.
   */
  $('.InputfieldCheckboxesSelectAll').each(function () {

    var $selectAll = $(this).find('a'),
        $checkboxes = $('#' + this.id.replace('-select-all', '')).find('input[type="checkbox"]'),
        allChecked = false;

    /**
     * Changes all enabled checkboxes.
     * 
     * @return {boolean} True, if at least one checkbox changed
     */
    function unCheckAll() {
      var changed = 0;
      $.each($checkboxes, function () {
        if ( !this.disabled ) {
          this.checked = !allChecked;
          changed += 1;
        }
      });
      return !!changed;
    }

    /**
     * Handling '(De-) Select all'-clicks
     */
    $selectAll.on('click', function () {
      if ( unCheckAll() ) {
        allChecked = !allChecked;
        $selectAll.text(allChecked ? InputfieldCheckboxesConfig.deSelectAll : InputfieldCheckboxesConfig.selectAll);
      }
    });

    /**
     * Handling checkbox state changes.
     */
    $checkboxes.on('change', function () {
      var allCheckedLen = $.grep($checkboxes, function (item) {
        return item.checked;
      }).length;
      if ( allCheckedLen === $checkboxes.length ) {
        allChecked = true;
        $selectAll.text(InputfieldCheckboxesConfig.deSelectAll);
        return;
      }
      allChecked = false;
      $selectAll.text(InputfieldCheckboxesConfig.selectAll);
    });

  });

});