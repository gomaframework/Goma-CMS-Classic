var initAjaxSubmitbutton = function(id, divId, formObject, field, url, appendix) {
    var button = $("#" + id);
    var container = $("#" + divId);
    var form = $("#" + formObject.id);

    /**
     * submits form after checking formsubmit and beforesubmit event.
     * returns promise which has failed when event returned false.
     *
     * @returns {*}
     */
    field.submit = function() {
        var eventb = jQuery.Event("beforesubmit");
        button.trigger(eventb);
        if ( eventb.result === false ) {
            return $.Deferred().reject().promise();
        }
        var event = jQuery.Event("formsubmit");
        form.trigger(event);
        if ( event.result === false ) {
            return $.Deferred().reject().promise();
        }

        return field.doSubmit();
    };

    /**
     * forces submit by ignoring formsubmit and beforesubmit event.
     * returns promise when submission was finished.
     *
     * @returns {*}
     */
    field.doSubmit = function() {
        button.css("display", "none");
        container.append("<img src=\"system/images/16x16/loading.gif\" alt=\"loading...\" class=\"loading\" />");
        $("body").css("cursor", "wait");

        goma.ui.updateFlexBoxes();
        var deferred = $.Deferred();
        $.ajax({
            url: url + appendix,
            type: "post",
            data: form.serialize() + "&" + encodeURIComponent($(button).attr("name")) + "=" + encodeURIComponent($(button).val()),
            dataType: "html",
            headers: {
                accept: "text/javascript; charset=utf-8"
            }
        }).always(function(){
            $(document.body).css("cursor", "default").css("cursor", "auto");
            container.find(".loading").remove();
            button.css("display", "inline");

            var eventb = jQuery.Event("ajaxresponded");
            form.trigger(eventb);

            goma.ui.updateFlexBoxes();
        }).done(function(script, textStatus, jqXHR){
            goma.ui.loadResources(jqXHR).done(function(){
                try {
                    var errorsRaised = false;
                    form.on("errorsraised.ajax", function() {
                        errorsRaised = true;
                    });
                    var method = new Function("field", "form", script);
                    var r = method.call(form.get(0), field, formObject);
                    RunAjaxResources(jqXHR);

                    goma.ui.updateFlexBoxes();

                    form.off("errorsraised.ajax");

                    if(errorsRaised) {
                        deferred.reject(jqXHR, textStatus, "FormError");
                    } else {
                        deferred.resolve(script, textStatus, jqXHR)
                    }

                    return r;
                } catch(e) {
                    alert(e);
                    deferred.reject(jqXHR, textStatus, e);
                }
            });
        }).fail(function(jqXHR, textStatus, errorThrown) {
            alert("There was an error while submitting your data, please check your Internet Connection or send an E-Mail to the administrator");

            goma.ui.updateFlexBoxes();

            deferred.reject(jqXHR, textStatus, errorThrown);
        });

        return deferred.promise();
    };

    button.click(function(){
        field.submit();
        return false;
    });
};
