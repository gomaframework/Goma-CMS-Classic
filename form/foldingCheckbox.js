var form_initFoldingCheckbox = function(field, id){
    console.log(field);
    var fieldDiv = $("#" + field.divId);
    var fieldInput = $("#" + field.id);
    var updateView = function(speed) {
        if(fieldInput.prop("checked")) {
            fieldDiv.find(".fields-of-folding-checkbox").slideDown(speed === undefined ? 300 : speed)
        } else {
            fieldDiv.find(".fields-of-folding-checkbox").slideUp(speed === undefined ? 300 : speed);
        }
    };

    fieldInput.on("change", function(){
        updateView();
    });

    updateView(0);
};
