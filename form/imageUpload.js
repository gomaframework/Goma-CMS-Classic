/**
 * The JS for field sets.
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package Goma\Form
 * @version 1.1
 */
function ImageUploadController(field, updateUrl, options) {
    if(field.fileUpload === undefined) {
        throw new Error("Could not initialize ImageUploadController, it depends on fileUpload.");
    }

    this.updateUrl = updateUrl;
    this.field = field;

    field.imageUpload = this;

    this.widget = $("#" + this.field.id + "_widget");
    this.widget.find(".buttons .cancel").click(this.hideCrop.bind(this));
    this.widget.find(".buttons .save").click(this.saveCrop.bind(this));

    this.super = this.field.fileUpload;
    this.fieldElement = this.super.fieldElement;

    this.registerEventHandler();
    this.updateCropArea({
        status: this.field.upload != null,
        file: this.field.upload
    });

    this.aspectRatio = field.aspect;

    if(typeof options === "object") {
        for(var i in options) {
            if(options.hasOwnProperty(i)) {
                this[i] = options[i];
            }
        }
    }

    this.super.actions.find(".crop").click(this.cropButtonClicked.bind(this));

    if(this.fieldElement.find(".fileupload-tabs").length > 0) {
        this.fieldElement.find(".fileupload-tabs").gtabs();
    }

    this.fieldElement.find(".crop-button").click(function(){
        if(this.field.upload != null) {
            setTimeout(this.placeCropButton.bind(this), 100);
        }
    }.bind(this));

    $(window).resize(this.updateFactorsBasedOnHTML.bind(this));

    return this;
}

ImageUploadController.prototype = {
    internalLeft: null,
    internalTop: null,
    internalWidth: null,
    internalHeight: null,
    jcropInstance: null,
    factorX: 1,
    factorY: 1,

    /**
     * aspect ratio x to y
     */
    aspectRatio: null,
    internalSize: null,
    saveCropTimeout: null,
    initialUpdate: true,
    initCropAreaDeferred: null,

    factoredAspectRatio: function() {
        if(this.aspectRatio == null) {
            return null;
        }

        console.log(this.factorX / this.factorY * this.aspectRatio);
        return this.factorX / this.factorY * this.aspectRatio;
    },

    updateCropArea: function(data) {
        if(data === null || !data.status) {
            this.super.actions.find(".crop").hide();
            this.fieldElement.find(".crop-button").hide();
            if(this.fieldElement.find(".crop-button").hasClass("active")) {
                this.fieldElement.find(".preview-button").click();
            }
            this.initialUpdate = false;
        } else {
            this.placeCropButton().done(function(){
                if(this.fieldElement.find(".crop-button").length > 0 && !this.initialUpdate && !this.fieldElement.find(".crop-button").hasClass("active")) {
                    this.fieldElement.find(".crop-button").click();
                }
                this.initialUpdate = false;
            }.bind(this)).fail(function(){
                alert("Error loading image.");
            });
        }
    },

    placeCropButton: function() {
        var deferred = $.Deferred();

        this.super.actions.find(".crop").show();
        this.fieldElement.find(".crop-button").show();

        this.fieldElement.find(".crop-wrapper").css("height", "auto");

        if(this.fieldElement.find(".crop-button").length > 0 && this.fieldElement.find(".crop-button").hasClass("active")) {
            var cropImage = this.fieldElement.find(".crop-wrapper > img").get(0);
            var image = new Image();
            image.onload = function () {
                this.initCropArea(
                    {
                        width: this.fieldElement.find(".crop-wrapper > img").width(),
                        height: this.fieldElement.find(".crop-wrapper > img").height()
                    },
                    this.fieldElement.find(".crop-wrapper > img")
                ).done(deferred.resolve).fail(deferred.reject);
            }.bind(this);
            image.onerror = function() {
                console.log(arguments);
                deferred.reject();
            };
            image.src = cropImage.src;
        } else {
            deferred.resolve();
        }

        return deferred.promise();
    },

    /**
     * updates factorX and Y based on new HTML image size after window resize.
     */
    updateFactorsBasedOnHTML: function() {
        if(this.fieldElement.find(".crop-wrapper > img").length > 0 && this.field.upload != null) {
            this.setFactors({
                width: this.fieldElement.find(".crop-wrapper > img").width(),
                height: this.fieldElement.find(".crop-wrapper > img").height()
            }, {
                width: this.field.upload.orgImageSize.width,
                height: this.field.upload.orgImageSize.height
            });
        }
    },

    /**
     * updates factorX and factorY property.
     *
     * @param size size of HTML image
     * @param imageSize size of real image
     */
    setFactors: function(size, imageSize) {
        this.factorX = size.width / imageSize.width;
        this.factorY = size.height / imageSize.height;

        console.log && console.log({x: this.factorX, y: this.factorY});
    },

    initCropArea: function(size, image) {
        if(this.initCropAreaDeferred != null) {
            if(this.initCropAreaDeferred.state() === "pending") {
                return this.initCropAreaDeferred.promise();
            }
        }

        this.initCropAreaDeferred = $.Deferred();

        this.safeDestoryCrop().done(function() {
            this.fieldElement.find(".crop-wrapper").css("height", size.height + "px");

            var $this = this,
                options = {
                    boxWidth: size.width,
                    boxHeight: size.height,
                    onChange: $this.updateCoords.bind($this),
                    onSelect: $this.updateCoords.bind($this),
                    onRelease: function() {
                        if($this.factoredAspectRatio() !== null) {
                            $this.jcropInstance.setSelect($this.setSelectForAspect(size));
                        }
                    }
                };

            this.internalSize = size;

            var upload = $this.field.upload, thumbSelectionW = size.width, thumbSelectionH = size.height, y = 0, x = 0;

            this.updateFactorsBasedOnHTML();

            if (this.factoredAspectRatio() !== null) {
                options.aspectRatio = this.factoredAspectRatio();
            }

            if (upload.thumbLeft !== 50 || upload.thumbTop !== 50 || upload.thumbWidth !== 100 || upload.thumbHeight !== 100) {
                thumbSelectionW = upload.thumbWidth / 100 * size.width;
                thumbSelectionH = upload.thumbHeight / 100 * size.height;
                y = (size.height - thumbSelectionH) * upload.thumbTop / 100;
                x = (size.width - thumbSelectionW) * upload.thumbLeft / 100;

                options.setSelect = [
                    x, y, x + thumbSelectionW, y + thumbSelectionH
                ];

                this.updateCoords({
                    h: thumbSelectionH,
                    w: thumbSelectionW,
                    x: x,
                    y: y
                }, false);
            } else if (this.factoredAspectRatio() !== null && thumbSelectionW / thumbSelectionH !== this.factoredAspectRatio()) {
                options.setSelect = this.setSelectForAspect(size);
            }

            var img = new Image();
            img.onload = function() {
                image.Jcrop(options, function () {
                    $this.jcropInstance = this;
                    $this.initCropAreaDeferred.resolve();
                });
            };
            img.onerror = this.initCropAreaDeferred.reject;
            img.src = image.attr("src");
        }.bind(this));

        return this.initCropAreaDeferred.promise();
    },

    /**
     * automatically determines correct position of crop controls for aspect ratio.
     *
     * @param size
     * @returns {[null,null,null,null]}
     */
    setSelectForAspect: function(size) {
        var thumbSelectionW = size.width, thumbSelectionH = size.height, y = 0, x = 0;
        if(thumbSelectionW / thumbSelectionH !== this.factoredAspectRatio()) {
            console.log("not in aspect");
            if (thumbSelectionW / thumbSelectionH > this.factoredAspectRatio()) {
                x = (size.width - thumbSelectionH * this.factoredAspectRatio()) / 2;
                thumbSelectionW = thumbSelectionH * this.factoredAspectRatio();
            } else {
                y = (size.height - size.width / this.factoredAspectRatio()) / 2;
                thumbSelectionH = size.width / this.factoredAspectRatio();
            }
        }

        this.updateCoords({
            h: thumbSelectionH,
            w: thumbSelectionW,
            x: x,
            y: y
        }, false);

        return [
            x, y, x + thumbSelectionW, y + thumbSelectionH
        ];
    },

    cropButtonClicked: function() {
        var $this = this;

        if(this.fieldElement.find(".crop-button").hasClass("active")) {
            return;
        }

        this.widget.fadeIn("fast");

        this.widget.find(".loading").show(0);
        this.widget.find(".image").hide(0);

        var src = this.field.upload.sourceImage != null ? this.field.upload.sourceImage : this.field.upload.path,
            image = new Image();

        image.onload = function () {
            $this.widget.find(".image img").attr("src", src);
            $this.widget.find(".loading").hide(0);
            $this.widget.find(".image").show(0);

            var size = $this.getSize(image);

            $this.widget.find(".image img").attr({
                height: size.height,
                width: size.width
            });

            $this.initCropArea(size, $this.widget.find(".image img"), image.width);
        };
        image.onerror = function() {
            alert("Error loading image.");
        };

        image.src = src;

        return false;
    },

    /**
     * calculates maximum possible height and width for widget.
     * @param image
     * @returns {*}
     */
    getSize: function(image) {
        var maxWidth = this.widget.find(".image").width();
        var maxHeight = this.widget.height() - this.widget.find(".buttons").outerHeight() - 32;

        if(image.width >= maxWidth || image.height >= maxHeight) {
            if(maxWidth / image.width * image.height >= maxHeight) {
                return {
                    height: maxHeight,
                    width: maxHeight / image.height * image.width
                };
            } else {
                return {
                    width: maxWidth,
                    height: maxWidth / image.width * image.height
                };
            }
        } else {
            return {
                width: image.width,
                height: image.height
            };
        }
    },

    /**
     * Update internal coordinates and saves to server if save is not false.
     * @param data Coordinates: h, w, x, y based on HTML image
     * @param save set to false to not save to server. Default: true
     */
    updateCoords: function(data, save) {
        console.log(data);

        this.internalHeight = data.h / this.factorY;
        this.internalWidth = data.w  / this.factorX;
        this.internalLeft = data.x  / this.factorX;
        this.internalTop = data.y  / this.factorY;

        this.fieldElement.find(".ThumbLeftValue").val(this.internalLeft);
        this.fieldElement.find(".ThumbTopValue").val(this.internalTop);
        this.fieldElement.find(".ThumbHeightValue").val(this.internalHeight);
        this.fieldElement.find(".ThumbWidthValue").val(this.internalWidth);

        if(data.w === 0 || data.h === 0) {
            this.field.upload.thumbWidth = this.field.upload.thumbHeight = 100;
            this.field.upload.thumbLeft = this.field.upload.thumbTop = 50;
        } else {
            this.field.upload.thumbWidth = Math.min(this.internalWidth / this.field.upload.orgImageSize.width * 100, 100);
            this.field.upload.thumbHeight = Math.min(this.internalHeight / this.field.upload.orgImageSize.height * 100, 100);
            this.field.upload.thumbLeft = this.field.upload.orgImageSize.width - this.internalWidth > 1 ?
                Math.min(Math.max(this.internalLeft / (this.field.upload.orgImageSize.width - this.internalWidth) * 100, 0), 100) : 50;
            this.field.upload.thumbTop = this.field.upload.orgImageSize.height - this.internalHeight > 1 ?
                Math.min(Math.max(this.internalTop / (this.field.upload.orgImageSize.height - this.internalHeight) * 100, 0), 100) : 50;
        }

        if(save !== false) {
            clearTimeout(this.saveCropTimeout);
            if (this.fieldElement.find(".crop-button").length > 0) {
                this.saveCropTimeout = setTimeout(this.saveCrop.bind(this, true), 200);
            }
        }
    },

    hideCrop: function() {
        this.safeDestoryCrop();

        this.widget.fadeOut("fast");
        return false;
    },

    safeDestoryCrop: function() {
        var deferred = $.Deferred();

        if(this.jcropInstance != null) {
            this.jcropInstance.destroy();
            this.jcropInstance = null;
            setTimeout(deferred.resolve, 100);
        } else {
            setTimeout(deferred.resolve, 10);
        }

        return deferred.promise();
    },

    saveCrop: function(updatePreviewOnly) {
        var $this = this;

        $this.super.formelement.find(".image-preview-img").parent().addClass("loading").css("height", "400px");

        if(updatePreviewOnly !== true) {
            this.safeDestoryCrop();
        }
        this.widget.fadeOut("fast");

        var useSource = null;
        if(this.field.upload.sourceImage != null) {
            if(this.field.upload.sourceImage === true) {
                useSource = "may";
            } else {
                useSource = "true";
            }
        }

        if(!useSource) {
            // if saveCrop is fired a second time, while response is not here, yet, the new image has a sourceImage.
            this.field.upload.sourceImage = true;
        }

        console.log(this);

        $.ajax({
            url: this.updateUrl,
            type: "post",
            data: {
                thumbLeft: this.internalLeft,
                thumbTop: this.internalTop,
                thumbWidth: this.internalWidth,
                thumbHeight: this.internalHeight,
                useSource: useSource
            },
            datatype: "json"
        }).done(function(data){
            if(updatePreviewOnly === true) {
                if(data.file.imageHeight400) {
                    var image = new Image();
                    image.onload = function() {
                        $this.super.formelement.find(".image-preview-img").parent().css("height", "").removeClass("loading");
                        $this.super.formelement.find(".image-preview-img").css({
                            width: "",
                            height: ""
                        }).attr({
                            "src": data.file.imageHeight400,
                            "data-retina": null
                        });
                    };
                    image.src = data.file.imageHeight400;
                    $this.field.upload = data.file;
                    $this.super.formelement.find(".upload-link").attr("href", data.file.path);
                    $this.super.destInput.val(data.file.realpath);

                    $this.fieldElement.find(".ThumbLeftValue").val(-1);
                    $this.fieldElement.find(".ThumbTopValue").val(-1);
                    $this.fieldElement.find(".ThumbHeightValue").val(-1);
                    $this.fieldElement.find(".ThumbWidthValue").val(-1);
                }
            } else {
                $this.super.uploader.updateFile(data);
            }
        }).fail(function(jqxhr){
            try {
                var data = $.parseJSON(jqxhr.responseText);
                if (data.error) {
                    alert(data.class + ": " + data.code + " " + data.errstring);
                } else {
                    alert(jqxhr.responseText);
                }
            } catch(e) {
                alert(e);
                console.log(e);
            }
        });

        return false;
    },

    registerEventHandler: function() {
        var $this = this;

        var oldUpdateFile = this.field.fileUpload.uploader.updateFile;

        this.super.uploader.updateFile = function(data) {
            oldUpdateFile.apply(this, arguments);
            $this.updateCropArea(data);
        };
    }
};
