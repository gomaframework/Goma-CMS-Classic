/**
 * This is the javascript part of select.
 *
 * @package Goma\Form\Select
 *
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author Goma-Team
 */

if(goma === undefined) {
    goma = {};
}

if(goma.form === undefined) {
    goma.form = {};
}

/**
 *
 * @param form
 * @param field
 * @returns {goma.form.Select}
 * @constructor
 */
goma.form.Select = function(form, field) {
    this.form = form;
    this.field = field;
    this.div = $("#" + field.divId);
    this.allowSelectize = this.div.hasClass("allowSelectize");

    var htmlField = this.div.find("> .select-wrapper > select");
    field.on = htmlField.on.bind(htmlField);
    field.off = htmlField.off.bind(htmlField);

    field.disable = this.disable.bind(this);
    field.enable = this.enable.bind(this);
    field.getValue = this.getValue.bind(this);
    field.setValue = this.setValue.bind(this);
    field.getPossibleValuesAsync = this.getPossibleValuesAsync.bind(this);

    field.clearOptions = this.clearOptions.bind(this);
    field.addOption = this.addOption.bind(this);
    field.removeOption = this.removeOption.bind(this);
    field.setOptions = this.setOptions.bind(this);

    this.init();

    return this;
};


/**
 *
 */
goma.form.Select.prototype.init = function() {
    if(this.allowSelectize) {
        var $select = this.div.find("> .select-wrapper > select").selectize();
        this.field.selectize = this.selectize = $select[0].selectize;

        this.div.find("> .select-wrapper").addClass("selectize").removeClass("non-selectize");
        this.div.addClass("selectize");

        this.div.find("> label").click(this.openSelectize.bind(this));
    }
};

/**
 * opens dropdown if selectize
 */
goma.form.Select.prototype.openSelectize = function() {
    if(this.selectize !== undefined) {
        this.selectize.open();
    }
};

/**
 * disables select field
 */
goma.form.Select.prototype.disable = function() {
    if(this.selectize) {
        this.selectize.disable();
    } else {
        this.div.find("> .select-wrapper select").prop("disabled", true);
    }
};

/**
 * enables select field
 */
goma.form.Select.prototype.enable = function() {
    if(this.selectize) {
        this.selectize.enable();
    } else {
        this.div.find("> .select-wrapper select").prop("disabled", false);
    }
};

/**
 * gets value
 * @returns String
 */
goma.form.Select.prototype.getValue = function() {
    return this.div.find("> .select-wrapper select").val();
};

/**
 * sets value
 *
 * @param value String
 */
goma.form.Select.prototype.setValue = function(value) {
    if(this.selectize) {
        this.selectize.setValue(value);
    } else {
        var option = this.div.find("> .select-wrapper select option[value=\"" + value + "\"]");
        if(option.length === 1 && !option.prop("disabled")) {
            this.div.find("> .select-wrapper select option:selected").prop("selected", false);
            option.prop("selected", true);
            this.div.find("> .select-wrapper select").change();
        }
    }
};

/**
 * gets values
 *
 * @returns {JQueryPromise<String[]>}
 */
goma.form.Select.prototype.getPossibleValuesAsync = function() {
    var deferred = $.Deferred();

    var values = [];
    if(this.selectize) {
        $.each(this.selectize.options, function(item){
            values.push(item);
        });
    } else {
        this.div.find("> .select-wrapper select option").each(function () {
            if (!$(this).prop("disabled")) {
                values.push($(this).attr("value"));
            }
        });
    }

    deferred.resolve(values);

    return deferred.promise();
};

/**
 * removes a option by value.
 */
goma.form.Select.prototype.removeOption = function(value) {
    if(this.selectize) {
        this.selectize.removeOption(value);
        this.selectize.refreshOptions(false);
    } else {
        var option = this.div.find("> .select-wrapper select option[value=\"" + value + "\"]");
        if(option.length === 1) {
            option.remove();
        }
    }
};

/**
 * removes all options
 */
goma.form.Select.prototype.clearOptions = function() {
    if(this.selectize) {
        this.selectize.clearOptions();
        this.selectize.refreshOptions(false);
    } else {
        this.div.find("> .select-wrapper select option").remove();
    }
};

/**
 * add an option
 */
goma.form.Select.prototype.addOption = function(value, text, noRefresh) {
    if(this.selectize) {
        this.selectize.addOption({
            value: value,
            text: text
        });
        if(noRefresh !== false) {
            this.selectize.refreshOptions();
        }
    } else {
        var option = this.div.find("> .select-wrapper select option[value=\"" + value + "\"]");
        if(option.length === 0) {
            $("<option></option>").attr("value", value).text(text).appendTo(
                this.div.find("> .select-wrapper select")
            );
        }
    }
};


/**
 * sets options.
 * @param options array on form of {text: "text", value: "val"}
 */
goma.form.Select.prototype.setOptions = function(options) {
    this.clearOptions();
    for(var i in options) {
        if(options.hasOwnProperty(i)) {
            this.addOption(options[i].value, options[i].text, false);
        }
    }

    if(this.selectize) {
        this.selectize.refreshOptions();
    }
};
