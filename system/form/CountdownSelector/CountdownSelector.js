var gCountdownSelectorField = function (fieldid) {
    $('.countdown_wrapper_' + fieldid + ' .countdown_input_wrapper i').click(function () {
        handleClickAction($(this));
    });
    $('.countdown_wrapper_' + fieldid + ' .countdown_input_wrapper input').change(function () {
        handleChangeAction($(this));
    });

    var fields = ["seconds", "minutes", "hours", "days"];
    var fieldsjQuery = [$(".countdown_wrapper_" + fieldid + " .countdown_seconds"), $(".countdown_wrapper_" + fieldid + " .countdown_minutes"), $(".countdown_wrapper_" + fieldid + " .countdown_hours"), $(".countdown_wrapper_" + fieldid + " .countdown_days")];

    function getFieldsKey(value) {
        for (var i = 0; i < fields.length; i++) {
            if (fields[i] === value) {
                return i
            }
        }
        return "false";
    }

    function sub(field) {
        var fieldkey = getFieldsKey(field);
        var val = Number(fieldsjQuery[fieldkey].val());
        var newval = (val === 0) ? ((field === 'hours') ? 23 : 59) : val - 1;
        fieldsjQuery[fieldkey].val(newval);
    }

    function canSub(field) {
        var fieldkey = getFieldsKey(field);
        var val = fieldsjQuery[fieldkey].val();
        if (val > 0) {
            sub(field);
            return true;
        }
        if (fieldkey === (field.length - 1)) {
            if (fieldsjQuery[fieldkey].val() > 0) {
                sub(field);
                return true;
            } else {
                return false;
            }
        }
        if (canSub(fields[fieldkey + 1])) {
            sub(field);
            return true;
        }
        return false;
    }

    function add(field) {
        var fieldkey = getFieldsKey(field);
        var val = Number(fieldsjQuery[fieldkey].val());
        if ((field === "hours" && val === 23) || (field !== "hours" && field !== "days" && val === 59)) {
            fieldsjQuery[fieldkey].val(0);
            add(fields[(Number(fieldkey) + 1)]);
            return true;
        }

        fieldsjQuery[fieldkey].val(val + 1);
        return true;
    }

    function handleChangeAction() {
        for (var i = 0; i < (fields.length - 1); i++) {
            var breakval = (i === 0 || i === 1) ? 59 : 23;
            var val = fieldsjQuery[i].val();
            if (val > breakval) {
                var priorcount = Math.floor(val / (Number(breakval) + 1));
                var leftcount = val % (Number(breakval) + 1);
                fieldsjQuery[i].val(leftcount);
                fieldsjQuery[i + 1].val(Number(fieldsjQuery[i + 1].val()) + priorcount);
            }
        }
        updateInput();
    }

    function handleClickAction(item) {
        var dir = (item.hasClass('fa-caret-up'));
        var field = item.parent()[0].className.split(/\s+/);
        field = field[0];
        if (dir) {
            add(field);
        } else {
            canSub(field);
        }
        updateInput();
    }

    function updateInput() {
        var val = 0;
        val += Number(fieldsjQuery[0].val());
        val += Number(fieldsjQuery[1].val()) * 60;
        val += Number(fieldsjQuery[2].val()) * 60 * 60;
        val += Number(fieldsjQuery[3].val()) * 24 * 60 * 60;
        $("#" + fieldid).val(val);
    }
    handleChangeAction();
};
