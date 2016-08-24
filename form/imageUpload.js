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

    if(typeof options == "object") {
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
            this.placeCropButton();
        }
    }.bind(this));

    return this;
}

ImageUploadController.prototype = {
    internalLeft: null,
    internalTop: null,
    internalWidth: null,
    internalHeight: null,
    jcropInstance: null,
    factor: 1,
    aspectRatio: null,
    internalSize: null,
    saveCropTimeout: null,
    initingJCrop: false,

    updateCropArea: function(data) {
        if (this.jcropInstance != null) {
            this.jcropInstance.destroy();
            this.jcropInstance = null;
        }

        if(data == null || !data.status) {
            this.super.actions.find(".crop").hide();
            this.fieldElement.find(".crop-button").hide();
            if(this.fieldElement.find(".crop-button").hasClass("active")) {
                this.fieldElement.find(".preview-button").click();
            }
        } else {
            this.placeCropButton();
        }
    },

    placeCropButton: function() {
        this.super.actions.find(".crop").show();
        this.fieldElement.find(".crop-button").show();

        if(this.fieldElement.find(".crop-button").length > 0 && this.fieldElement.find(".crop-button").hasClass("active")) {
            this.initCropArea(
                {
                    width: this.fieldElement.find(".crop-wrapper > img").width(),
                    height: this.fieldElement.find(".crop-wrapper > img").height()
                },
                this.fieldElement.find(".crop-wrapper > img"),
                this.field.upload.orgImageSize.width
            );
        }
    },

    initCropArea: function(size, image, imageWidth) {
        if(this.initingJCrop) {
            return;
        }

        this.initingJCrop = true;
        if (this.jcropInstance != null) {
            this.jcropInstance.destroy();
            this.jcropInstance = null;
        }

        var $this = this,
            options = {
                onChange: $this.updateCoords.bind($this),
                onSelect: $this.updateCoords.bind($this),
                onRelease: function() {
                    if($this.aspectRatio != null) {
                        $this.jcropInstance.setSelect($this.setSelectForAspect(size));
                    }
                }
            };

        this.internalSize = size;

        $this.factor = size.width / imageWidth;

        var upload = $this.field.upload, thumbSelectionW = size.width, thumbSelectionH = size.height, y = 0, x = 0;

        if (this.aspectRatio != null) {
            options.aspectRatio = this.aspectRatio;
        }

        if (upload.thumbLeft != 50 || upload.thumbTop != 50 || upload.thumbWidth != 100 || upload.thumbHeight != 100) {
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
            });
        } else

        if (this.aspectRatio != null && thumbSelectionW / thumbSelectionH != this.aspectRatio) {
            options.setSelect = this.setSelectForAspect(size);
        }

        if(this.aspectRatio != null) {
            options.aspectRatio = this.aspectRatio;
        }

        image.Jcrop(options, function () {
            $this.jcropInstance = this;
            $this.initingJCrop = false;
        });
    },

    setSelectForAspect: function(size) {
        var thumbSelectionW = size.width, thumbSelectionH = size.height, y = 0, x = 0;
        if (thumbSelectionW / thumbSelectionH > this.aspectRatio) {
            x = (size.width - thumbSelectionH * this.aspectRatio) / 2;
            thumbSelectionW = thumbSelectionH * this.aspectRatio;
        } else {
            y = (size.height - size.width / this.aspectRatio) / 2;
            thumbSelectionH = size.width / this.aspectRatio;
        }

        this.updateCoords({
            h: thumbSelectionH,
            w: thumbSelectionW,
            x: x,
            y: y
        });

        return [
            x, y, x + thumbSelectionW, y + thumbSelectionH
        ];
    },

    cropButtonClicked: function() {
        var $this = this;

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

    updateCoords: function(data) {
        this.internalHeight = data.h / this.factor;
        this.internalWidth = data.w  / this.factor;
        this.internalLeft = data.x  / this.factor;
        this.internalTop = data.y  / this.factor;

        this.fieldElement.find(".ThumbLeftValue").val(this.internalLeft);
        this.fieldElement.find(".ThumbTopValue").val(this.internalTop);
        this.fieldElement.find(".ThumbHeightValue").val(this.internalHeight);
        this.fieldElement.find(".ThumbWidthValue").val(this.internalWidth);

        if(data.w == 0 || data.h == 0) {
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

        clearTimeout(this.saveCropTimeout);
        if(this.fieldElement.find(".crop-button").length > 0) {
            this.saveCropTimeout = setTimeout(this.saveCrop.bind(this, true), 200);
        }
    },

    hideCrop: function() {
        if(this.jcropInstance != null) {
            this.jcropInstance.destroy();
        }

        this.widget.fadeOut("fast");
        return false;
    },

    saveCrop: function(updatePreviewOnly) {
        var $this = this;

        if(this.jcropInstance != null && updatePreviewOnly !== true) {
            this.jcropInstance.destroy();
        }
        this.widget.fadeOut("fast");

        $.ajax({
            url: this.updateUrl,
            type: "post",
            data: {
                thumbLeft: this.internalLeft,
                thumbTop: this.internalTop,
                thumbWidth: this.internalWidth,
                thumbHeight: this.internalHeight,
                useSource: this.field.upload.sourceImage != null
            },
            datatype: "json"
        }).done(function(data){
            console.log(updatePreviewOnly);
            if(updatePreviewOnly === true) {
                if(data.file.imageHeight400) {
                    $this.super.formelement.find(".image-preview-img").css({
                        width: "",
                        height: ""
                    }).attr({
                        "src": data.file.imageHeight400,
                        "data-retina": null
                    });
                    $this.super.formelement.find(".upload-link").attr("href", data.file.path);
                }
            } else {
                $this.super.uploader.updateFile(data);
            }
        }).fail(function(jqxhr){
            var data = $.parseJSON(jqxhr.responseText);
            if(data.error) {
                alert(data.class + ": " + data.code + " " + data.errstring);
            } else {
                alert(jqxhr.responseText);
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
