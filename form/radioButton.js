initRadioButton = function(field, divid) {
    var div = $("#" + divid);
    var htmlFields = div.find("> .inputHolder > .option > input");
    field.on = htmlFields.on.bind(htmlFields);
    field.off = htmlFields.off.bind(htmlFields);

    field.disable = function() {
        div.find("> .inputHolder > .option > input").prop("disabled", true);
        return this;
    };

    field.enable = function() {
        div.find("> .inputHolder > .option > input").prop("disabled", false);

        return this;
    };

    field.getValue = function() {
        return div.find("> .inputHolder > .option > input[type=radio]:checked").attr("value");
    };
    field.setValue = function(value) {
        var radio = div.find("> .inputHolder > .option > input[value=\"" + value + "\"]");
        if (radio.length == 1 && radio.parent().css("display") != "none") {
            div.find("> .inputHolder > .option > input[type=radio]:checked").prop("checked", false);
            radio.prop("checked", true);
            radio.change();
        }
        return this;
    };
    field.getPossibleValuesAsync = function() {
        var deferred = $.Deferred();

        setTimeout(function(){
            var values = [];
            div.find("> .inputHolder > .option > input[type=radio]").each(function () {
                if ($(this).parent().css("display") != "none") {
                    values.push($(this).attr("value"));
                }
            });
            deferred.resolve(values);
        });

        return deferred.promise();
    };
};
