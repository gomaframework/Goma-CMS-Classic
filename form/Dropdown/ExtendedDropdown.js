/**
 * This is the javascript part of the extended dropdown in PHP.
 *
 * @package Goma\Form\Dropdown
 *
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author Goma-Team
 */

if (goma === undefined) {
    goma = {};
}

if (goma.form === undefined) {
    goma.form = {};
}

goma.form.ExtendedDropdown = function (form, field) {
    this.field = field;
    this.form = form;
    this.select = $("#" + field.id);
    this.field.extendedDropdown = this;

    if (this.field.allowDrag) {
        goma.ui.loadAsync("sortable").done(function () {
            this.init();
        }.bind(this));
    } else {
        this.init();
    }

    return this;
};

/**
 * initializer.
 */
goma.form.ExtendedDropdown.prototype.init = function () {
    var options = {
        plugins: ['infinite_scroll', 'remove_button'],
        valueField: "valueRepresentation",
        searchField: "searchInfo",
        options: this.field.selectedItems,
        items: this.field.selectedItemValues,
        loadThrottle: 100,
        render: {
            option: this.renderOption.bind(this),
            item: this.renderItem.bind(this),
            option_create: this.renderItemAdd.bind(this)
        },
        load: this.loadFromServer.bind(this),
        maxItems: this.field.maxItemsCount,
        hideSelected: true,
        create: this.field.allowCreate ? this.create.bind(this) : false,
        preload: true
    };

    if (this.field.allowDrag) {
        options.plugins.push("drag_drop");
    }

    $.extend(options, this.field.customOptions);

    if (this.field.customJS) {
        var method = new Function("options", "form", "field", this.field.customJS);

        var returnValue = method.call(options, this.form, this.field, this);
        if (returnValue) {
            options = returnValue;
        }
    }

    var $select = this.select.selectize(options);
    this.selectize = $select[0].selectize;
    this.field.disable = this.selectize.disable.bind(this.selectize);
    this.field.enable = this.selectize.enable.bind(this.selectize);

    this.field.setValue = this.selectize.setValue.bind(this.selectize);
    this.field.getValue = this.selectize.getValue.bind(this.selectize);
    this.field.getPossibleValuesAsync = function (query) {
        var deferred = $.Deferred();

        this.loadFromServer(query === undefined ? "" : query, function (data) {
            if (data) {
                var items = [];
                for (var i in data) {
                    if (data.hasOwnProperty(i)) {
                        items.push(data[i].valueRepresentation);
                    }
                }
                deferred.resolve(items);
            } else {
                deferred.reject();
            }
        });

        return deferred.promise();
    }.bind(this);
    this.totalCount = null;
    this.totalCountQuery = null;
    this.perPage = 50;
};

/**
 * loads data from server.
 *
 * @param query
 * @param callback
 * @returns {*}
 */
goma.form.ExtendedDropdown.prototype.loadFromServer = function (query, callback) {
    var queryJSON = query ? JSON.parse(query) : {search: query, page: 1};
    var page = queryJSON.page || 1;

    if (this.totalCountQuery === null || this.totalCountQuery !== queryJSON.search ||
        this.totalCount === null || this.totalCount > ( (page - 1) * this.perPage)) {
        this.totalCountQuery = queryJSON.search;
        $.ajax({
            url: this.field.externalUrl + "/search/" + encodeURIComponent(queryJSON.search) + "?page=" + page + "&perPage=" + this.perPage,
            type: "GET",
            dataType: "json"
        }).fail(function () {
            callback();
        }).done(function (data) {
            if (data.total_count === null) {
                if (data.items.length < 50) {
                    this.totalCount = 50;
                }
            } else {
                this.totalCount = data.total_count;
            }

            callback(data.items);
        }.bind(this));
    } else {
        callback();
    }
};

/**
 * creates a new input.
 * @param input
 * @param callback
 */
goma.form.ExtendedDropdown.prototype.create = function (input, callback) {
    if (!input.length) return callback();
    $.ajax({
        url: this.field.externalUrl + "/create/",
        type: "POST",
        data: {input: input},
        dataType: "json"
    }).fail(function () {
        callback();
    }).done(function (data) {
        if (data.hasOwnProperty("error")) {
            alert(data.error)
        } else {
            data.wasCreated = true;
            callback(data);
        }
    });
};

/**
 * method is called by selectize to render one option in dropdown
 * @param item
 * @param escape
 * @returns string
 */
goma.form.ExtendedDropdown.prototype.renderOption = function (item, escape) {
    return '<div class="option">' + item.optionRepresentation + '</div>';
};

/**
 * method is called by selectize to render one selected item in input.
 * @param item
 * @param escape
 * @returns string
 */
goma.form.ExtendedDropdown.prototype.renderItem = function (item, escape) {
    return '<div class="item">' + item.inputRepresentation + '</div>';
};

/**
 * method is called by selectize to render add item.
 *
 * @param data
 * @param escape
 */
goma.form.ExtendedDropdown.prototype.renderItemAdd = function(data, escape) {
    if(this.field.customOptions["createTemplate"] !== undefined) {
        return '<div class="create">' + this.field.customOptions["createTemplate"].replace('$input', '<strong>' + escape(data.input) + "</strong>") + '</div>';
    }

    return '<div class="create">' + lang("form_dropdown_add_input").replace('$input', '<strong>' + escape(data.input) + "</strong>") + '</div>';
};

/**
 * gets data values.
 * @param item
 * @param escape
 * @returns string
 */
goma.form.ExtendedDropdown.prototype.getDataValues = function () {
    var items = [];
    for (var itemIndex in this.selectize.items) {
        if (this.selectize.items.hasOwnProperty(itemIndex)) {
            items.push(this.selectize.options[this.selectize.items[itemIndex]]);
        }
    }
    return items;
};
