$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip();
    $('.clickAble').click(function(){
        $('.clickAble').removeClass("table-success");
        var val = $(this).attr("data-group");
        $("td[data-group="+val+"], th[data-group="+val+"]").addClass("table-success");
        $("#correct").val(val);
    });
});
