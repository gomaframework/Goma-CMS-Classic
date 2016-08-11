var multiFormFieldController = function(element, options) {
    this.element = $(element);

    for(var i in options) {
        if(options.hasOwnProperty(i)) {
            this[i] = options[i];
        }
    }

    this.init();

    return this;
};

multiFormFieldController.prototype = {
    sortable: false,
    deletable: false,

    init: function() {
        var _this = this;

        this.updateOrder();

        if(this.sortable) {
            this.element.find(".part-sort-button").css("cursor", "move").show();

            gloader.loadAsync("sortable").done(function(){
               this.element.sortable({
                    opacity: 0.6,
                    helper: 'clone',
                    handle: ".part-sort-button",
                    placeholder: 'placeholder',
                    revert: true,
                    tolerance: 'pointer',
                    cancel: "a, img, .actions",
                    start: function(event, ui) {
                        $(".placeholder")
                            .css({'width' : ui.item.width(), 'height': ui.item.height()})
                            .attr("class", ui.item.attr("class") + " placeholder");
                    },
                    update: function() {
                        this.updateOrder();
                    }.bind(this),
                    distance: 10,
                    items: " > .clusterformfield"
                });
            }.bind(this));
        } else {
            this.element.on("click", ".part-sort-button", function(){
                return false;
            });
        }

        if(this.deletable) {
            this.element.find(".part-delete-button").show();

            this.element.find(".part-delete-button").click(function(){
                var cluster = $(this).parent().parent().parent();

                _this.hideCluster(cluster, true);

                return false;
            });
            this.element.find(".undo-template a.undo").click(function(){
                var cluster = $(this).parent().parent();

                _this.undo(cluster);

                return false;
            });
        }

        this.element.find("input[name*=__shoulddeletepart]").each(function(){
            if($(this).val() == 1) {
                var cluster = $(this).parent().parent().parent();
                _this.hideCluster(cluster);
            }
        });
    },

    updateOrder: function() {
        var i = 0;
        this.element.find(".form-component").each(function(){
            $(this).attr("order", i);
            $(this).find("input[name*=__sortpart]").val(i);
            i++;
        });
    },

    hideCluster: function(cluster, animated) {
        cluster.addClass("part-hidden");
        cluster.find("input[name*=__shoulddeletepart]").val(1);

        if(cluster.find(".form_field_headline:visible").length > 0) {
            cluster.find(".undo-template .headline").css("display", "");
            cluster.find(".undo-template .headline .text").text(cluster.find(".form_field_headline:visible input").val());
        } else {
            cluster.find(".undo-template .headline").css("display", "none");
        }

        if(animated) {
            cluster.find(" > div").not(".undo-template").slideUp("fast");
            cluster.find(".undo-template").slideDown("fast", function(){
                this.updateOrder();
            }.bind(this));
        } else {
            cluster.find(" > div").not(".undo-template").css("display", "none");
            cluster.find(".undo-template").css("display", "block");

            this.updateOrder();
        }
    },

    undo: function(cluster) {
        cluster.removeClass("part-hidden");
        cluster.find(" > div").slideDown("fast");
        cluster.find(".undo-template").slideUp("fast");
        cluster.find("input[name*=__shoulddeletepart]").val(0);

        this.updateOrder();
    }
};
