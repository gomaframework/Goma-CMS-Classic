/**
 * The JS for FileUpload-Fields.
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package Goma\Form
 * @version 1.1
 */
function FileUpload(form, field, formelement, url, size, types) {
	var $this = this;

	this.form = form;
	this.field = field;

	field.fileUpload = this;
	this.url = url;

	form.form.on("formsubmit", function(){
		if(this.uploader.loading) {
			if(!FileUpload.hasAsked) {
				FileUpload.hasAsked = true;
				setTimeout(function(){
					FileUpload.hasAsked = false;
				}, 300);
				return confirm(lang("leave_page_upload_confirm"));
			}
		}
	}.bind(this));

	this.fieldElement = $("#" + field.divId);

	this.formelement = $(formelement);
	this.element = $(formelement).find(".drop-area").get(0);
	this.destInput = $(formelement).find(".FileUploadValue");
	this.defaultIcon = field.defaultIcon;

	this.actions = this.fieldElement.find(".fileupload-actions");
	// the info-zone
	this.infoZone = this.fieldElement.find(".progress_info");

	// append fallback for not drag'n'drop-browsers
	this.browse = this.formelement.find(".fileSelect");

	this.deleteButton = this.formelement.find(".delete-file-button");
	this.deleteButton.click(function(){
		this.uploader.updateFile(null);
		return false;
	}.bind(this));

	if(!field.upload || !field.showDeleteButton) {
		$this.formelement.find(".delete-file-button").hide();
	}

	if(!field.upload || !field.upload.managePath) {
		$this.formelement.find(".manage-file-button").hide();
	}

	this.uploader = new AjaxUpload("#" + this.element.id, {
		url: url + "/frameUpload/",
		ajaxurl: url + "/ajaxUpload/",
		browse: this.browse,
		usePut: false,
		useSlice: true,

		max_size: size,

		allowed_types: types,

		// events
		uploadStarted: function() {
			var that = this;
			$this.infoZone.html('<div class="progressbar"><div class="progress"></div><span><img src="system/images/16x16/loading.gif" alt="Uploading.." /></span><div class="cancel"></div></div>');
			$this.infoZone.find(".cancel").click(function(){
				that.abort();
			});
			$($this.element).append('<div class="loading"></div>');
            $this.form.setLeaveCheck(true);
		},
		dragInDocument: function() {
			$($this.element).addClass("upload-active");
		},
		dragLeaveDocument: function() {
			$($this.element).removeClass("upload-active");
			$($this.element).removeClass("beforeDrop");
		},

		dragOver: function() {
			$($this.element).addClass("upload-active");
			$($this.element).addClass("beforeDrop");
		},

		/**
		 * called when the speed was updated, just for ajax-upload
		 */
		speedUpdate: function(fileIndex, file, KBperSecond) {
			var ext = "KB/s";
			KBperSecond = Math.round(KBperSecond);
			if(KBperSecond > 1000) {
				KBperSecond = Math.round(KBperSecond / 1000, 2);
				ext = "MB/s";
			}
			$this.infoZone.find("span").html(KBperSecond + ext);
		},

		/**
		 * called when the progress was updated, just for ajax-upload
		 */
		progressUpdate: function(fileIndex, file, newProgress) {
			$this.infoZone.find(".progress").stop().animate({width: newProgress + "%"}, 500);
		},

		/**
		 * event is called when the upload is done
		 */
		always: function() {
			$this.infoZone.find("span").html("100%");
			$this.infoZone.find(".progress").css("width", "100%");
			setTimeout(function(){
				$this.infoZone.find(".progressbar").slideUp("fast", function(){
					$this.infoZone.html("");
				});
			}, 1000);

			$($this.element).find(".loading").remove();
		},

		/**
		 * method which is called, when we receive the response
		 */
		done: function(html) {
			try {
				var data = eval('('+html+');');
				this.updateFile(data);
			} catch(err) {
				if(this.isAbort) {

				} else {
					$this.infoZone.html('<div class="error">An Error occured. '+err+'</div>');
				}
				throw err;
			}
		},

		updateFile: function(data) {
			if(data == null) {
				$this.formelement.find("input.FileUploadValue").val("");
				$($this.fieldElement).find(".img img").attr({
					"src": $this.defaultIcon,
					"alt": "",
					"style": ""
				});
				$this.fieldElement.find(".upload-link").removeAttr("href");
				$this.fieldElement.find(".filename").html("");
				$this.formelement.find(".delete-file-button").hide();
				$this.formelement.find(".manage-file-button").hide();
				$this.formelement.find(".image-source-preview-img, .image-preview-img").removeAttr("src");

				$this.field.upload = null;
				$this.fieldElement.find(".image-area").removeClass("with-upload");
			} else if(data.status == 0) {
				if(data.error) {
					$this.infoZone.html('<div class="error">' + data.error + '</div>');
				}
			} else {
				if(field.showDeleteButton) {
					$this.fieldElement.find(".delete-file-button").show();
				}

				if(data.file.managePath) {
					$this.fieldElement.find(".manage-file-button").attr("href", data.file.managePath).show();
				}

				$this.field.upload = data.file;
				if(data.file["icon128"]) {
					if(window.devicePixelRatio > 1.5 && data.file["icon128@2x"]) {
						this.updateIcon(data.file["icon128@2x"]);
					} else {
						this.updateIcon(data.file.icon128);
					}
				} else {
					this.updateIcon(data.file.icon);
				}
				if(data.file.path)
					$this.fieldElement.find(".upload-link").attr("href", data.file.path);
				else
					$this.fieldElement.find(".upload-link").removeAttr("href");
				$this.fieldElement.find(".filename").html(data.file.name);
				$this.destInput.val(data.file.realpath);
				$this.fieldElement.find(".image-area").addClass("with-upload");

				if(data.file.imageHeight400) {
					$this.formelement.find(".image-source-preview-img, .image-preview-img").css({
						width: "",
						height: ""
					}).attr({
						"src": data.file.imageHeight400,
						"data-retina": null
					});
				}
				if(data.file.sourceImageHeight400) {
					$this.formelement.find(".image-source-preview-img").attr("src", data.file.sourceImageHeight400);
				}
			}
		},

		updateIcon: function(icon) {
			$($this.element).find(".img img").attr("src", icon);
		},

		failSize: function(i) {
			$this.infoZone.html('<div class="error">'+lang("files.filesize_failure")+'</div>');
		},

		failExt: function() {
			$this.infoZone.html('<div class="error">'+lang("files.filetype_failure")+'</div>');
		},
		fail: function(status, response) {
			$this.infoZone.html('<div class="error">'+response+'</div>');
		}

	});

	// now hide original file-upload-field
	this.formelement.find(".no-js-fallback").css("display", "none");

	return this;
}

FileUpload.hasAsked = false;
