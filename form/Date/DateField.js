var gDateField = function(field, args, form) {
    var container = $("#" + field.divId);
    var cancelButton = container.find(".clear-date");
    var fieldElement = $('#' + field.id);
    if(isTouchDevice() && args.singleDatePicker) {
        fieldElement.val(moment(fieldElement.val(), args.locale.format).format().substring(0, args.timePicker ? 19 : 10));
        fieldElement.prop("type", args.timePicker ? "datetime-local" : "date");
        fieldElement.change(function() {
            cancelButton.removeClass("disabled");
        });
        container.addClass("native-datepicker");
    } else {
        fieldElement.daterangepicker(args);

        fieldElement.on('apply.daterangepicker', function (ev, picker) {
            if (args.singleDatePicker) {
                $(this).val(picker.startDate.format(args.locale.format));
            } else {
                $(this).val(picker.startDate.format(args.locale.format) + args.locale.seperator + picker.endDate.format(args.locale.format));
            }
            cancelButton.removeClass("disabled");
            return false;
        });
    }

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
