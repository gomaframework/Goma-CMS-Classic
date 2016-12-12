(function($){
    if(goma.ui.ajaxloader === undefined) {
        var http_regexp = /https?\:\/\//,
            load_alwaysLoad = /\/[^\/]*(data)[^\/]+\.js/,
            external_regexp = /https?\:\/\/|ftp\:\/\//,
            run_regexp = /\/[^\/]*(script|raw)[^\/]+\.js/;

            goma.ui.ajaxloader = {
            /**
             * global ajax renderer
             *
             *@name renderResponse
             *@access public
             */
            renderResponse: function (html, xhr, node, object, checkUnload, progress) {
                var deferred = $.Deferred(),
                    method;

                node = ($(node).length > 0) ? $(node) : goma.ui.getContentRoot();

                if (checkUnload !== false) {
                    var data = goma.ui.fireUnloadEvents(node);
                    if (typeof data == "string") {
                        if (!confirm(lang("unload_lang_start") + data + lang("unload_lang_end"))) {
                            setTimeout(function () {
                                deferred.reject("unload");
                            }, 1);
                            return deferred.promise();
                        }
                    }
                }

                goma.ui.loadResources(xhr, progress).done(function () {
                    if (xhr === undefined) {
                        throw new Error("xhr is not defined but required param.");
                    }

                    var content_type = xhr.getResponseHeader("content-type"),

                        regexp = new RegExp("<body"),
                        id = randomString(5);

                    if (content_type.indexOf("text/javascript") != -1) {
                        if (object !== undefined) {
                            var method = new Function(html);
                            method.call(object);
                        } else {
                            eval_global(html);
                        }
                        RunAjaxResources(xhr);
                        return true;
                    } else if (content_type == "text/x-json" && json_regexp.test(html)) {
                        RunAjaxResources(xhr);
                        return false;
                    }

                    if (regexp.test(html)) {
                        window.top[id + "_html"] = html;
                        node.html('<iframe src="javascript:document.write(top.'+id+'_html);" height="500" width="100%" name="'+id+'" frameborder="0"></iframe>');
                    } else {
                        node.html(html);
                    }

                    RunAjaxResources(xhr);

                    deferred.resolve();
                }).fail(function (err) {
                    deferred.reject(err);
                });

                return deferred.promise();
            },

            setProgress: function(percent, slowp) {
                var deferred = $.Deferred(),

                    slow = (slowp === undefined) ? false : slowp,

                    duration = (slow && percent != 100) ? 5000 : 500,
                    i;

                if ($("#loadingBar").length == 0) {
                    $("body").append('<div id="loadingBar"></div>');
                    $("#loadingBar").css("width", 0);
                }

                goma.ui.progress = percent;
                $("#loadingBar").stop().css({opacity: 1}).animate({
                    width: percent + "%"
                }, {
                    duration: duration,
                    queue: false,
                    complete: function () {
                        if (percent != 100) {
                            deferred.resolve();
                        }
                    },
                    fail: function () {
                        if (percent != 100) {
                            deferred.reject();
                        }
                    }
                });

                if (percent == 100) {
                    $("#loadingBar").animate({
                        opacity: 0
                    }, {
                        duration: 1000,
                        queue: false,
                        complete: function () {
                            $("#loadingBar").css({width: 0, opacity: 1});
                            deferred.resolve();
                        },
                        fail: function () {
                            deferred.reject();
                        }
                    });
                    goma.ui.progress = undefined;
                }

                for (i in goma.ui.loadSubscribers) {
                    goma.ui.loadSubscribers[i](percent, slow);
                }

                return deferred.promise();
            },

            loadResources: function(request, progress) {
                var deferred = $.Deferred(),

                    css = request.getResponseHeader("X-CSS-Load"),
                    js = request.getResponseHeader("X-JavaScript-Load"),
                    base_uri = request.getResponseHeader("x-base-uri"),
                //root_path = request.getResponseHeader("x-root-path"),
                    cssfiles = (css != null) ? css.split(";") : [],
                    jsfiles = (js != null) ? js.split(";") : [],

                    perProgress = Math.round((50 / (jsfiles.length + cssfiles.length))),
                    p = progress,

                    i = 0,
                // we create a function which we call for each of the files and it iterates through the files
                // if it finishes it notifies the deferred object about the finish
                    loadFile = function () {

                        // i is for both js and css files
                        // first we load js files and then css, cause when js files fail we can't show the page anymore, so no need of loading css is needed
                        if (i >= jsfiles.length) {

                            // get correct index for css-files
                            var a = i - jsfiles.length;
                            if (a < cssfiles.length) {

                                var file = cssfiles[a],
                                    loadfile = (!http_regexp.test(file)) ? base_uri + file : file;

                                // scope to don't have problems with data
                                (function (f) {
                                    var file = f;
                                    if (!external_regexp.test(file) && file != "") {
                                        if (goma.ui.CSSFiles[file] === undefined) {
                                            return $.ajax({
                                                cache: true,
                                                url: loadfile,
                                                noRequestTrack: true,
                                                dataType: "html"
                                            }).done(function (css) {
                                                // patch uris
                                                var base = file.substring(0, file.lastIndexOf("/"));
                                                //css = css.replace(/url\(([^'"]+)\)/gi, 'url(' + root_path + base + '/$2)');
                                                //css = css.replace(/url\(['"]?([^'"#\>\!\s]+)['"]?\)/gi, 'url(' + base_uri + base + '/$1)');

                                                goma.ui.CSSFiles[file] = css;
                                                goma.ui.CSSIncluded[file] = true;

                                                $("head").prepend('<style type="text/css" id="css_'+file.replace(/[^a-zA-Z0-9_\-]/g, "_")+'">'+css+'</style>');
                                            }).always(function(){
                                                if (goma.ui.progress !== undefined && p) {
                                                    goma.ui.setProgress(goma.ui.progress + perProgress);
                                                }

                                                i++;
                                                loadFile();
                                            });
                                        } else if (goma.ui.CSSIncluded[file] === undefined) {
                                            $("head").prepend('<style type="text/css" id="css_'+file.replace(/[^a-zA-Z0-9_\-]/g, "_")+'">'+CSSLoaded[file]+'</style>');
                                            goma.ui.CSSIncluded[file] = true;
                                        }
                                    } else {
                                        goma.ui.CSSFiles[file] = css;
                                        goma.ui.CSSIncluded[file] = true;
                                        if ($("head").html().indexOf(file) != -1) {
                                            $("head").prepend('<link rel="stylesheet" href="'+file+'" type="text/css" />');
                                        }
                                    }

                                    i++;
                                    loadFile();
                                })(file);
                            } else {
                                setTimeout(function () {
                                    deferred.notify("loaded");
                                }, 10);
                            }
                        } else {
                            var file = jsfiles[i],
                                loadfile = (!http_regexp.test(file)) ? base_uri + file : file;

                            if (file != "") {
                                // check for internal cache
                                // we don't load modenizr, because it causes trouble sometimes if you load it via AJAX
                                if (goma.ui.JSFiles[file] === undefined && goma.ui.JSIncluded[file] === undefined && !file.match(/modernizr\.js/)) {

                                    // we create a new scope for this to don't have problems with overwriting vars and then callbacking with false ones
                                    return (function (file) {
                                        $.ajax({
                                            cache: true,
                                            url: loadfile,
                                            noRequestTrack: true,
                                            dataType: "html"
                                        }).done(function (js) {
                                            // build into internal cache
                                            goma.ui.JSFiles[file] = js;
                                        }).always(function(){
                                            if (goma.ui.progress !== undefined && p) {
                                                goma.ui.setProgress(goma.ui.progress + perProgress);
                                            }

                                            i++;
                                            loadFile();
                                        });
                                    })(file);
                                } else {
                                    if (goma.ui.progress !== undefined && p) {
                                        goma.ui.setProgress(goma.ui.progress + perProgress);
                                    }

                                    i++;
                                    return loadFile();
                                }
                            } else {
                                i++;
                                loadFile();
                            }
                        }
                    };

                // init loading
                loadFile();


                deferred.progress(function () {
                    var i,
                        file;

                    for (i in jsfiles) {
                        file = jsfiles[i];
                        if (((!run_regexp.test(file) && goma.ui.JSIncluded[file] !== true) || load_alwaysLoad.test(file)) && goma.ui.JSFiles[file] !== undefined) {
                            goma.ui.JSIncluded[file] = true;
                            eval_global(goma.ui.JSFiles[file]);
                        }
                    }

                    setTimeout(function () {
                        deferred.resolve();
                    }, 10);

                    if (window.respond !== undefined && !respond.mediaQueriesSupported) {
                        respond.update();
                    }

                });

                return deferred.promise();
            }
        };
    }
})(jQuery);
