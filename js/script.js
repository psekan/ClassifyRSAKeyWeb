$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip();
    $('.clickAble').click(function(){
        $('.clickAble').removeClass("success");
        var val = $(this).attr("data-group");
        $("td[data-group="+val+"], th[data-group="+val+"]").addClass("success");
        $("#correct").val(val);
    });
});
