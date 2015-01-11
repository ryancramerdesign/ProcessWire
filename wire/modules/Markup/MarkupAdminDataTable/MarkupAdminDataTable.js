$(document).ready(function() {
    $table = $("table.AdminDataTableSortable")
    $table.tablesorter();

    var lastChecked = null;
    $table.find('input[type=checkbox]').click(function(e) {
        var $checkboxes = $table.find('input[type=checkbox]');
        if(!lastChecked) {
            lastChecked = this;
            return;
        }
        if(e.shiftKey) {
            var start = $checkboxes.index(this);
            var end = $checkboxes.index(lastChecked);
            $checkboxes.slice(Math.min(start,end), Math.max(start,end)+ 1).attr('checked', lastChecked.checked);
        }
        lastChecked = this;
    });
}); 