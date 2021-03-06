/**
 * ajax fileupload
 *
 * thanks to https://github.com/pangratz/dnd-file-upload/blob/master/jquery.dnd-file-upload.js
 *@package goma
 *@link http://goma-cms.org
 *@license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 *@author Goma-Team
 * last modified: 13.05.2015
 * $Version 2.0.9
 */

var AjaxUpload = function(DropZone, options) {
    this.DropZone = $(DropZone);
    this.url = location.href;
    this.ajaxurl = location.href;
    this.multiple = false;
    this.id = randomString(10);
    this.max_size = -1;
    this.allowed_types = true;

    for(var i in options) {
        if(options[i] !== undefined) {
            this[i] = options[i];
        }
    }
    this.loading = false;

    var $this =  this;


    // bind events on document to stop the browser showing the file, if the user does not hit the dropzone
    $(document).on("dragenter", function(event){
        $this.dragEnterDocument(event);
        return $this._dragInDocument(event);
    });

    $(document).on("dragleave", function(event){
        return $this._dragLeaveDocument(event);
    });

    $(document).on("dragover", function(event){
        return $this._dragInDocument(event);
    });

    if(document.addEventListener) {
        document.addEventListener("drop", function(event){
            return $this._dragLeaveDocument(event);
        });
    }

    // now bind events to dropzone
    this.DropZone.on("dragenter", function(event){
        return $this._dragEnter(event);
    });
    this.DropZone.on("dragover", function(event){
        return $this._dragOver(event);
    });

    if(this.DropZone.get(0).addEventListener) {
        this.DropZone.get(0).addEventListener("drop", function(ev) {
            return $this._drop(ev);
        });
    }

    // browse-button for old-browser-fallback via iFrame
    if(typeof this.browse !== "undefined") {
        this.browse = $(this.browse);
        this.placeBrowseHandler();

        this.browse.on("click", function(e){
            $("#" + $this.id + "_uploadForm").find(".fileSelectInput").click();
        });
    }

    return this;
};

AjaxUpload.prototype = {
    uploadRateRefreshTime: 500,
    usePut: true,
    useSlice: false,
    sliceSize: 2048000, // ~2MB

    frames: [],
    name: "file",

    queue: [],
    currentIndex: 0,

    // events

    // document events
    dragInDocument: function(ev) {
        this.DropZone.addClass("fileupload-drag");
    },
    dragLeaveDocument: function(ev) {
        this.DropZone.removeClass("fileupload-drag");
        this.DropZone.removeClass("fileupload-drag-over");
    },
    dragEnterDocument: function(ev) {

    },

    // dropzone events
    dragEnter: function(ev) {
        this.DropZone.addClass("fileupload-drag-over");
    },
    dragOver: function(ev) {
        this.DropZone.addClass("fileupload-drag-over");
    },

    newFilesDropped: function() {

    },

    // upload-handlers
    /**
     * called when upload started
     *
     *@name uploadStarted
     */
    uploadStarted: function(index, upload) {

    },

    /*
     * progress and speed
     */

    /**
     * called when progress was updated
     *
     *@name progressUpdate
     *@param int - fileindex
     *@param file - file
     *@param int - current progress in percent
     */
    progressUpdate: function(index, file, currentProgress) {

    },

    /**
     * called when speed was updated
     *
     *@name speedUpdate
     *@param int - fileIndex
     *@param file - file
     *@param int - current progress in percent
     */
    speedUpdate: function(index, file, currentSpeed) {

    },

    /**
     * always called regardless of an error
     */
    always: function(time, index, upload) {

    },

    /**
     * if succeeded
     */
    done: function(response, index, upload) {

    },

    // error-handlers
    errTooManyFiles: function() {

    },

    fail: function(status, response, index, upload) {

    },

    failSize: function(index) {

    },

    failExt: function(index) {

    },

    /**
     * called on cancel
     */
    cancel: function(index) {

    },

    /**
     * PRIVATE METHODS, YOU SHOULD NOT REDECLARE THEM
     */

    /**
     * when the user drags a file over the document
     *
     *@name _dragInDocument
     *@access public
     */
    _dragInDocument: function(event) {
        if(!this.loading) {
            this.dragInDocument(event);
        }

        event.stopPropagation();
        event.preventDefault();
        return false;
    },

    /**
     * when a user leaves the document with the file
     *
     *@name _dragLeaveDocument
     */
    _dragLeaveDocument: function(event) {
        if(!this.loading) {
            this.dragLeaveDocument(event);
        }

        event.stopPropagation();
        event.preventDefault();
        return false;
    },

    /**
     * when the user drags the file in the dropzone
     *
     *@name _dragEnter
     */
    _dragEnter: function(event) {
        if(!this.loading) {
            this.dragEnter(event);
            this.dragOver(event);
        }

        event.stopPropagation();
        event.preventDefault();
        return false;
    },

    /**
     * when the user drags the file within the dropzone
     *
     *@name _dragOver
     */
    _dragOver: function(event) {
        if(!this.loading) {
            this.dragOver(event);
        }

        event.stopPropagation();
        event.preventDefault();
        return false;
    },

    /**
     * when the user drops the file
     *
     *@name _drop
     */
    _drop: function(event) {
        try {
            if (this.multiple || !this.loading) {
                this.dragLeaveDocument();
                this.newFilesDropped();
                var dt = event.dataTransfer;
                var files = dt.files;
                this.transferAjax(files);
            }
        } catch(e) {
            this._fail(e, e, -1, null);
            setTimeout(function(){
                throw e
            });
        }

        event.stopPropagation();
        event.preventDefault();
        return false;
    },

    /**
     * is always called when transfer is completed, regardless whether succeeded or not
     *
     *@name _complete
     */
    _complete: function(uploadInst, fileIndex, upload) {
        var now = new Date().getTime();
        var timeDiff = now - upload.downloadStartTime;

        if(this.browse) {
            this.browse.removeAttr("disabled");
        }

        if(this.queue[fileIndex]) {
            this.queue[fileIndex].loading = false;
            this.queue[fileIndex].loaded = true;
        }

        this.loading = false;
        this.always(timeDiff, fileIndex, upload);
    },

    /**
     * progress-handler
     */
    _progress: function(event, upload) {
        if (event.lengthComputable) {
            var percentage = Math.round((event.loaded * 100) / event.total);

            // update for percentage only every percent
            if (upload.currentProgress != percentage) {

                // log(this.fileIndex + " --> " + percentage + "%");

                upload.currentProgress = percentage;
                this.progressUpdate(upload.fileIndex, upload.fileObj, upload.currentProgress);
            }

            // update speed
            var elapsed = new Date().getTime();
            var diffTime = elapsed - upload.currentStart;
            if (diffTime >= this.uploadRateRefreshTime) {
                var diffData = event.loaded - upload.startData;
                var speed = diffData / diffTime; // in KB/sec

                this.speedUpdate(upload.fileIndex, upload.fileObj, speed);

                return elapsed;
            }
        }
    },

    /**
     * called when request succeeded
     */
    _success: function(response, fileIndex, upload) {
        this.done(response, fileIndex, upload);
    },

    /**
     * called when failed.
     */
    _fail: function(status, response, fileIndex, upload, xhr) {
        this.fail(status, response, fileIndex, upload, xhr);
    },

    /**
     * transfer methods
     */

    /**
     * ajax upload
     *
     * @param files
     */
    transferAjax: function(files) {
        if(!this.multiple) {
            if(files.length > 1) {
                this.errTooManyFiles();
                return false;
            }
        }

        if(this.loading && !this.multiple) {
            return false;
        }

        for ( var i = 0; i < files.length; i++) {
            var file = files[i];

            var _xhr = this.generateXHR(i, file);

            this.queue[_xhr.fileIndex] = {
                send: function() {
                    this.xhr.send();
                },
                abort: function() {
                    return this.xhr.abort();
                },
                fileIndex: _xhr.fileIndex,
                xhr: _xhr,
                upload: _xhr
            };

            this.loading = true;
            if(!this.multiple) {
                this.hideBrowseHandler();
            }
        }

        this.processQueue();
    },

    /**
     * generates the XML-HTTP-Request
     */
    generateXHR: function(i, file) {
        var _this = this;

        var customUrl = this.ajaxurl;
        if(customUrl.indexOf("?") === -1) {
            customUrl += "?slice=" + randomString(10);
        } else {
            customUrl += "&slice=" + randomString(10);
        }

        return new slicedAjaxUpload(customUrl, file, this.useSlice ? this.sliceSize : -1, {
            usePut: this.usePut,
            fileIndex: i + this.queue.length,
            currentStart: new Date().getTime(),
            currentProgress: 0,
            startData: 0,
            downloadStartTime: new Date().getTime(),
            progress: function(event) {
                var currentStart = _this._progress(event, this);
                if(currentStart) {
                    this.startData = event.loaded;
                    this.currentStart = currentStart;
                }
            },
            done: function(data, xhr, uploader) {
                _this._complete(xhr, uploader.fileIndex, uploader);
                _this._success(xhr.responseText, uploader.fileIndex, uploader);
            },
            fail: function(status, data, xhr, uploader) {
                _this._complete(xhr, uploader.fileIndex, uploader);
                _this._fail(xhr.status, xhr.responseText, uploader.fileIndex, uploader, xhr);
            }
        });
    },

    /**
     * starts the upload
     */
    processQueue: function() {
        for(var i in this.queue) {
            if(!this.queue[i].loading && !this.queue[i].loaded) {
                if(this.checkFileExt(this.queue[i].upload.file.name)) {
                    if(this.max_size === -1 || typeof this.queue[i].upload.file.size === "undefined" || this.queue[i].upload.file.size <= this.max_size) {
                        this.queue[i].loading = true;
                        this.queue[i].send();
                        this.uploadStarted(i, this.queue[i].upload);
                    } else {
                        this.abort(i);
                        this.failSize(i);
                    }
                } else {
                    this.abort(i);
                    this.failExt(i);
                }
            }
            this.placeBrowseHandler();
        }
    },

    /**
     * checks the file-extension.
     */
    checkFileExt: function(name) {
        if(this.allowed_types === true) {
            return true;
        }

        var regexp = new RegExp("\.("+this.allowed_types.join("|")+")$", "i");
        return name.match(regexp);
    },

    /**
     * transports the files of a specific formfield via iframe
     *
     *@name transportFrame
     */
    transportFrame: function(field) {
        if(this.loading) {
            return false;
        }


        var form = $(field).parents("form");

        form.attr("id", "").css("display", "none");

        var $this = this;
        if(field.files) {
            form.remove();

            this.hideBrowseHandler();

            // we can upload through the file-handler, yeah :)
            this.transferAjax(field.files);
            return true;
        } else {
            this.loading = true;

            var iframe = randomString(10);

            var upload = {};
            upload.downloadStartTime = new Date().getTime();
            upload.fileIndex = this.queue.length;
            var val = $(field).val();
            val = val.substring(val.lastIndexOf("\\") + 1, val.length);
            val = val.substring(val.lastIndexOf("/") + 1, val.length);
            upload.fileName = val;

            $("body").append('<iframe name="'+iframe+'" id="frame_'+iframe+'" frameborder="0" height="1" width="1" src="about:blank;"></iframe>');
            $(field).parents("form").attr("target", iframe);

            this.hideBrowseHandler();

            this.queue[upload.fileIndex] = {
                send: function() {
                    form.submit();
                },
                abort: function() {
                    form.remove();
                },
                fileIndex: upload.fileIndex,
                upload: upload
            };

            var frame = document.getElementById("frame_" + iframe);

            var testing = function(){
                var d = this.getDocFromFrame(frame);
                $this._complete(null, this, upload.fileIndex, upload);
                $this._success(d.body.innerHTML, upload.fileIndex);

                form.remove();

                $this.loading = false;
            };

            if(getInternetExplorerVersion() === -1) {
                frame.onload = testing;
            } else {
                frame.attachEvent("onload", testing);
            }

            this.processQueue();
        }
    },

    /**
     * returns document from frame.
     */
    getDocFromFrame: function(frame) {
        if (frame.contentDocument) {
            return frame.contentDocument;
        } else if (frame.contentWindow) {
            return frame.contentWindow.document;
        } else {
            return window.frames[frame.name].document;
        }

    },

    /**
     * browse-button-implementation
     */

    /**
     * placed the file-input over the browse-button
     *
     *@name placeBrowseHandler
     */
    placeBrowseHandler: function() {
        if(this.loading) {
            this.hideBrowseHandler();
        }

        if(typeof this.browse === "undefined") {
            return false;
        }

        var $this = this;

        // now create the form
        var $form = $("#" + this.id + "_uploadForm");
        if($form.length == 0) {
            $("body").append('<form id="' + this.id+'_uploadForm" style="position: absolute; left: -500px;z-index: 900;" method="post" action="'+this.url+'" enctype="multipart/form-data"><input name="file" style="font-size: 200px;float: right;" type="file" class="fileSelectInput" /></form>');
            $form = $("#" + this.id + "_uploadForm");
            $form.find(".fileSelectInput").change(function(){
                $this.transportFrame(this);
            });

            if(this.multiple) {
                $form.find(".fileSelectInput").attr("multiple", "multiple");
                $form.find(".fileSelectInput").attr("name", "file[]");
            }
        }

        if(!$this.loading) {
            $this.browse.removeAttr("disabled");
        } else {
            $this.browse.attr("disabled", "disabled");
        }

        $form.css("display", "block");
        $form.css({
            top: "-100px",
            left: "auto",
            width: "50px",
            height: "50px",
            overflow: "hidden"
        });
        $form.fadeTo(0, 0);
    },

    /**
     * places the file-input out of the document
     *
     *@name hideBrowseHandler
     */
    hideBrowseHandler: function() {
        if(typeof this.browse === "undefined") {
            return false;
        }

        this.browse.attr("disabled", "disabled");
    },

    /**
     * aborts the upload(s)
     *
     *@name abort
     */
    abort: function(fileIndex) {
        if(typeof fileIndex === "undefined") {
            for(var i in this.queue) {
                this.abortIndex(i);
            }

            this.queue = [];

            this.loading = false;
        } else {
            this.abortIndex(fileIndex);
        }
    },

    /**
     * aborts specific index.
     */
    abortIndex: function(index) {
        if(typeof this.queue[index] !== "undefined") {
            this.queue[index].abort();
            this.cancel(index);
            this._complete(null, this, index, this.queue[index].upload);
            this.queue[index].loading = false;
            this.queue[index].loaded = true;
        }
    }
};

var slicedAjaxUpload = function(url, file, sliceSize, options) {
    this.url = url;
    this.file = file;
    this.sliceSize = sliceSize > 0 ? sliceSize : file.size;

    for(var i in options) {
        if(options.hasOwnProperty(i)) {
            this[i] = options[i];
        }
    }

    this.initSliceMethod();
    this.init();

    return this;
};

slicedAjaxUpload.prototype = {
    usePut: true,
    name: "file",
    progress: null,
    fail: null,
    done: null,

    downloadStartTime: new Date().getTime(),
    currentStart: new Date().getTime(),

    _currentXHR: null,

    _slices: [],

    init: function() {
        this._slices = [];
        for(var start = 0; start < this.file.size; start += this.sliceSize) {
            this._slices.push({
                start: start,
                end: Math.min(start + this.sliceSize, this.file.size),
                done: false
            });
        }

        if(this._slices.length === 0) {
            this._slices.push({
                start: 0,
                end: this.file.size,
                done: false
            });
        }
    },

    initSliceMethod: function() {
        if ('mozSlice' in this.file) {
            this.slice_method = 'mozSlice';
        }
        else if ('webkitSlice' in this.file) {
            this.slice_method = 'webkitSlice';
        }
        else {
            this.slice_method = 'slice';
        }
    },

    alreadyDoneSlices: function() {
        var a = 0;
        for(var i in this._slices) {
            if(this._slices.hasOwnProperty(i)) {
                if(this._slices[i].done) {
                    a++;
                } else {
                    break;
                }
            }
        }
        return a;
    },

    alreadyDoneFileSize: function() {
        return this.alreadyDoneSlices() * this.sliceSize;
    },

    _sendNextJunk: function() {
        var next = this.alreadyDoneSlices();

        if(this._slices.hasOwnProperty(next)) {
            var xhr = new XMLHttpRequest();
            var upload = xhr.upload;
            upload.slice = next;
            upload.uploader = this;

            // add listeners
            upload.addEventListener("progress", function(event){
                if(this.progress != null && event.lengthComputable) {
                    this.progress.apply(this, [{
                        total: this.file.size,
                        loaded: this.alreadyDoneFileSize() + event.loaded,
                        lengthComputable: true,
                        timeStamp: event.timeStamp,
                        type: "gomaprogress"
                    }]);
                }
            }.bind(this), false);

            if(this.usePut) {
                xhr.open("PUT", this.url);

                xhr.setRequestHeader("content-type", "application/octet-stream");
            } else {
                xhr.open("POST", this.url);
            }

            xhr.setRequestHeader("X-File-Name", this.file.name.replace(/[^\w\.\-]/g, '-'));
            xhr.setRequestHeader("X-File-Size", this.file.size.toString());
            xhr.setRequestHeader("Cache-Control", "no-cache");
            xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            xhr.setRequestHeader("Content-Range", "bytes " + this._slices[next].start + " - " + this._slices[next].end);

            xhr.onreadystatechange = function (event) {
                try {
                    if (xhr.readyState === 4 && xhr.responseText !== "" && xhr.status === 200) {
                        var data = JSON.parse(xhr.responseText);
                        this.upload.uploader._slices[this.upload.slice].done = data.status == 2 ? data.wait : true;
                        this.upload.uploader._sendNextJunk();
                    }

                    if (xhr.readyState === 4 && xhr.status !== 200) {
                        this.upload.uploader._sendFail(xhr);
                    }
                } catch(e) {
                    console.log && console.log(e);
                    this.upload.uploader._sendFail(xhr);
                }
            }.bind(xhr);

            var chunk = this._slices[next].start === 0 && this._slices[next].end === this.file.size ? this.file :
                this.file[this.slice_method](this._slices[next].start, this._slices[next].end);

            if (!this.usePut) {
                var formData = new FormData();
                formData.append(this.name, chunk, this.file.name);
                xhr.formData = formData;

                xhr.send(xhr.formData);
            } else {
                xhr.send(chunk);
            }

            this._currentXHR = xhr;
        } else {
            if(this.done) {
                this.done(this._currentXHR.responseText, this._currentXHR, this);
            }
        }
    },

    _sendFail: function(xhr) {
        if(this.fail) {
            this.fail(xhr.status, xhr.responseText, xhr, this);
        }
    },

    send: function() {
        this._sendNextJunk();
    },

    abort: function() {
        if(this._currentXHR) {
            this._currentXHR.abort();
        }
    }
};
