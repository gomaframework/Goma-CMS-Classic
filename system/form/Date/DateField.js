var gDateField = function(field, args, form) {
    var cancelButton = $("#" + field.divId).find(".clear-date");
    var fieldElement = $('#' + field.id);
    fieldElement.daterangepicker(args);

    fieldElement.on('apply.daterangepicker', function(ev, picker) {
        if(args.singleDatePicker) {
            $(this).val(picker.startDate.format(args.locale.format));
        } else {
            $(this).val(picker.startDate.format(args.locale.format) + args.locale.seperator + picker.endDate.format(args.locale.format));
        }
        cancelButton.removeClass("disabled");
        return false;
    });

    cancelButton.click(function(){
        fieldElement.val("");
        cancelButton.addClass("disabled");
        return false;
    });
    fieldElement.on('cancel.daterangepicker', function(ev, picker) {
        return false;
    });

    if(!fieldElement.val()) {
        cancelButton.addClass("disabled");
    }
};
