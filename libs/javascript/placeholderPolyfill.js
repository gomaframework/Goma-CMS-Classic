// html5 placeholder
$("input").each(
    function () {
        if (($(this).attr("type") == "text" || $(this).attr("type") == "search") && ($(this).val()=="" || $(this).val() == $(this).attr("placeholder")) && $(this).attr("placeholder")!="") {

            $(this).val($(this).attr("placeholder"));
            $(this).css("color", "#999");

            $(this).focus(function () {
                if ($(this).val()==$(this).attr("placeholder")) {
                    $(this).val("");
                }
                $(this).css("color", "");
            });
            $(this).blur(function () {
                if ($(this).val()=="") {
                    $(this).val($(this).attr("placeholder"));
                    $(this).css("color", "#999");
                }
            });

        }
    }
);
