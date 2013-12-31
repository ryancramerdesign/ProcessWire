$(document).ready(function() {
        var submitted = false;
        $("#ProcessPageAdd").submit(function() {
                if(submitted) return false;
                submitted = true;
        });
});
