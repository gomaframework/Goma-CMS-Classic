/**
 * The JS for forms.
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package Goma\Form
 * @version 1.0
 */
if (typeof goma == "undefined")
    var goma = {};

(function ($) {
    goma.form = function (id, leave_check, fields, errors) {
        if (!this instanceof goma.form)
            return new goma.form(id, leave_check, fields, errors);

        // register in global scope
        window[id] = this;

        goma.form.garbageCollect();

        var that = this;

        this.leave_check = leave_check;
        this.fields = fields;
        this.errors = errors;
        this.onReadyCallbacks = [];
        this.loadOnReadyStatus = false;

        this.id = id;
        this.form = $("#" + id);
        this.form.attr("action", this.form.attr("action") + location.hash);

        this.form.bind("formsubmit", function () {
            that.form.addClass("leave_check");
        });

        var button = false;
        this.form.find("button[type=submit], input[type=submit]").click(function () {
            button = true;
            that.form.removeClass("leave_check");
        });

        this.form.submit(function () {
            that.form.find(".err").slideUp();

            if (button == false) {
                setTimeout(function () {
                    that.form.find(".default_submit").click();
                }, 100);
                return false;
            }
            var eventb = jQuery.Event("beforesubmit");
            that.form.trigger(eventb);
            if (eventb.result === false) {
                return false;
            }

            var event = jQuery.Event("formsubmit");
            that.form.trigger(event);
            if (event.result === false) {
                return false;
            }

            that.form.removeClass("leave_check");
            button = false;
        });

        $("#" + id + " input.default_submit").click(function () {
            $("#" + id + " > .actions  button[type=submit]").each(function () {
                if ($(this).attr("name") != "cancel" && !$(this).hasClass("cancel")) {
                    $(this).click();
                    return false;
                }
            });
            return false;
        });

        goma.ui.bindUnloadEvent(this.form, function () {
            return that.unloadEvent();
        });

        this.runScripts(fields);

        goma.form._list[id.toLowerCase()] = this;

        // let init and then check for leave-check
        setTimeout(function () {
            that.form.find("select, input, textarea").change(function () {
                that.form.addClass("leave_check");
            });

            that.form.find("select, input, textarea").keydown(function () {
                that.form.addClass("leave_check");
            });
        }, 500);

        return this;
    };

    goma.form.prototype.runScripts = function (fields, parent) {
        for (var i in fields) {
            if (fields.hasOwnProperty(i)) {
                if (parent != null) {
                    fields[i]["parent"] = parent;
                }

                fields[i].form = this;
                fields[i].getValue = function () {
                    return $("#" + this.id).val();
                }.bind(fields[i]);

                fields[i].setValue = function (value) {
                    $("#" + this.id).val(value);
                    return this;
                }.bind(fields[i]);

                var field = $("#" + fields[i].id);
                fields[i].on = field.on.bind(field);
                fields[i].off = field.off.bind(field);

                fields[i].disable = function () {
                    $("#" + this.id).prop("disabled", true);
                    return this;
                }.bind(fields[i]);

                fields[i].enable = function () {
                    $("#" + this.id).prop("disabled", false);
                    return this;
                }.bind(fields[i]);

                fields[i].findFieldByName = function(name, fields) {
                    return this.form.findFieldByName(
                        name,
                        fields ? fields : (this.children !== null ? this.children : [])
                    )
                }.bind(fields[i]);

                fields[i].getPossibleValuesAsync = function () {
                    var deferred = $.Deferred();

                    deferred.resolve("*");

                    return deferred.promise();
                };

                if (fields[i]["js"]) {
                    try {
                        var method = new Function("field", "fieldIndex", "form", fields[i]["js"]);

                        method.call(this, fields[i], i, this);
                    } catch (e) {
                        console.log(fields[i]);
                        throw e;
                    }
                }

                if (fields[i]["children"]) {
                    this.runScripts(fields[i]["children"], fields[i]);
                }
            }
        }
    };

    goma.form.prototype.errorsRaised = function () {
        var event = jQuery.Event("errorsraised");
        this.form.trigger(event);
    };

    goma.form.prototype.setLeaveCheck = function (bool) {
        if (bool) {
            this.form.addClass("leave_check");
        } else {
            this.form.removeClass("leave_check");
        }
    };

    goma.form.prototype.getLeaveCheck = function () {
        return this.form.hasClass("leave_check");
    };

    goma.form.prototype.unloadEvent = function () {
        if (this.leave_check) {
            if (this.form.hasClass("leave_check")) {
                return lang("unload_not_saved").replace('\n', "\n");
            }
        }

        return true;
    };

    goma.form.prototype.findFieldByName = function (name, fields) {
        fields = fields !== undefined ? fields : this.fields;

        if (name.indexOf(".") !== -1) {
            var names = name.split(".");
            var currentField = {children: fields};
            for (var a in names) {
                if (names.hasOwnProperty(a)) {
                    if (currentField != null && currentField.children !== undefined) {
                        currentField = this.findFieldByName(names[a], currentField.children);
                    } else {
                        return null;
                    }
                }
            }
            return currentField;
        } else {
            for (var i in fields) {
                if (fields.hasOwnProperty(i)) {
                    if (fields[i].name && fields[i].name.toLowerCase() == name.toLowerCase()) {
                        return fields[i];
                    }

                    if (fields[i].children !== undefined) {
                        var fieldInChildren = this.findFieldByName(name, fields[i].children);
                        if (fieldInChildren != null) {
                            return fieldInChildren;
                        }
                    }
                }
            }

            return null;
        }
    };

    goma.form.prototype.onReady = function (callback) {
        if (typeof callback === "function") {
            this.onReadyCallbacks.push(callback);
            this.loadOnReady();
        }
    };

    goma.form.prototype.loadOnReady = function () {
        if (!this.loadOnReadyStatus) {
            this.loadOnReadyStatus = true;
            $this = this;
            $(document).ready(function () {
                for (var i = 0; i < $this.onReadyCallbacks.length; i++) {
                    $this.onReadyCallbacks[i]();
                }
            });
        }
    };

    goma.form._list = {};
    goma.form.garbageCollect = function () {
        for (var i in goma.form._list) {
            if (goma.form._list.hasOwnProperty(i)) {
                if ($("#" + goma.form._list[i].id).length == 0) {
                    delete goma.form._list[i];
                }
            }
        }
    };

    $.fn.gForm = function () {
        goma.form.garbageCollect();

        if (this.length > 0 && this.get(0).tagName.toLowerCase() === "form") {
            if (typeof goma.form._list[this.attr("id").toLowerCase()] !== "undefined") {
                return goma.form._list[this.attr("id").toLowerCase()];
            } else {
                return new goma.form(this.attr("id").toLowerCase());
            }
        }

        return false;
    };
})(jQuery);
