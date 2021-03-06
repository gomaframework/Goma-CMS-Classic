/**
 * The base goma-javascript-library to load other JS-Modules from the server and get some base functionallity in JavaScript.
 *
 * @author	Goma-Team
 * @license	GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package	Goma\JS-Framework
 * @version	2.1.6
 */

// goma-framework
if (goma === undefined) {
	var goma = {};
}

if (window.console === undefined) {
	window.console = {log: function(){;}};
}

//$.touchPunch.setAutoAssign(false);

// some regular expressions
var json_regexp = /^\(?\{/,
    html_regexp = new RegExp("<body");

    if (goma.ui === undefined) {
	goma.ui = (function ($) {

		'use strict';

		var run_regexp = /\/[^\/]*(script|raw)[^\/]+\.js/,
			prefixes = ["webkit", "moz", "ms", "o"],

			/**
			 * this code loads external plugins on demand, when it is needed, just call gloader.load("pluginName"); before you need it
			 * you must register the plugin in PHP
			 * we stop execution of JavaScript while loading
			 */
			gloaded = {"-": true},

			loadScriptAsync = function (comp) {
				if (gloaded[comp] == null)
				{
					gloaded[comp] = $.ajax({
						cache: true,
						noRequestTrack: true,
						url: BASE_SCRIPT + "gloader/" + comp + ".js?v2b8",
						dataType: "script"
					});
					return gloaded[comp];
				} else {
					if(gloaded[comp] === true) {
						return $.Deferred().resolve().promise();
					} else {
						return gloaded[comp];
					}
				}
			},

            CheckForDomUpdate = function() {
                RetinaReplace();
                checkForEndlessContainer();
                checkForTags();
            },

			checkForTags = function() {
                for (var query in goma.ui.tags) {
                    if (goma.ui.tags.hasOwnProperty(query)) {
                        var file = goma.ui.tags[query];
                        if (file != null) {
                            if ($(query + ":not(.g-file-loaded)").length > 0) {
                                $(query).addClass("g-file-loaded");
                                goma.ui.loadAsync("ajaxLoader").done(function () {
                                    goma.ui.ajaxloader.loadAndRunSingleJSFileIfNeeded(file, null);
                                });
                                delete goma.ui.tags[query];
                            }
                        }
                    }
                }
			},

			RetinaReplace = function () {
                if (goma.ui.getDevicePixelRatio() > 1.5) {
                    $("img").each(function () {
                        var $this = $(this),
                            img = new Image();

                        if ($this.attr("data-retined") !== "complete" && $this.attr("data-retina") && $this.width() !== 0 && $this.height() !== 0) {
                            if (goma.ui.IsImageOk($(this).get(0))) {
                                img.onload = function () {
                                    $this.css("width", $this.width());
                                    $this.css("height", $this.height());
                                    $this.attr("src", $this.attr("data-retina"));
                                    img = null;
                                };
                                img.src = $this.attr("data-retina");
                                $this.attr("data-retined", "complete");
                            }
                        }
                    });
                }
			},

			checkForEndlessContainer = function() {
                $(".endless-wrapper").each(function(){
                    if(!$(this).hasClass("endless-appended")) {
                        $(this).addClass("endless-appended");

                        goma.ui.loadAsync("endlessScrolling").done(function () {
                            console.log("add");
                            var opts = {
                                baseElement: $(this),
                                endlessElement: ".endless-end"
                            };
                            if ($(this).css("overflow") == "auto" || $(this).css("overflow") == "scroll") {
                                opts.scrollElement = $(this);
                            } else if ($(this).attr("data-scroll-element") && $($(this).attr("data-scroll-element")).length > 0) {
                                opts.scrollElement = $($(this).attr("data-scroll-element"));
                            } else if($(this).parents(".goma-flex-box").length > 0) {
                                opts.scrollElement = $(this).parents(".goma-flex-box").eq(0);
							}

                            console.log(opts);

                            if ($(this).attr("data-wrapper")) {
                                opts.urlWrapperElement = $(this).attr("data-wrapper");
                            } else {
                                var wrapper = "";
                                var classList = $(this).attr('class').split(/\s+/);
                                $.each(classList, function(index, item) {
                                    if(item != "endless-appended") {
                                        wrapper += "." + item;
                                    }
                                });

                                opts.urlWrapperElement = wrapper;
							}

                            new endlessScroller(opts);
                        }.bind(this));
                    }
                });
            },

			flexBoxes = [],

			/**
			 * this is the algorythm to calculate the 100% size of a box.
			 */
			updateFlexHeight = function ($container, setHeight) {
				var maxHeight,
					inFloat = false;

				if ($container === undefined || $container.html() === undefined) {
					return false;
				}

				if ($container.css("display") == "inline") {
					throw new Error("inline-elements are not allowed for flex-boxing.");
					return false;
				}

				if(!$container.hasClass("goma-flex-box")) {
                    $container.addClass("goma-flex-box");
                }

				var scroll = $container.scrollTop();

				$container.css("height", "");
				maxHeight = updateFlexHeight($container.parent(), false);

				// first make sure that the parent element is a Flex-Box
				if (!maxHeight) {
					$container.css({"display": ""});
					$container.css("height", "100%");
					return $container.height();
				}

				if ($container.attr("id") === undefined) {
					$container.attr("id", randomString(10));
				}

				$container.css({"display": ""});

				// now calulate other elements' height
				$container.parent().find(" > *").each(function () {
					if ((inFloat && $(this).css("clear") == "both") || $(this).css("clear") == inFloat) {
						inFloat = false;
					}

					if ($(this).attr("id") != $container.attr("id") && $(this).css("float").toLowerCase() != "left" && $(this).css("float").toLowerCase() != "right" && !inFloat && $(this).css("display").toLowerCase() != "none" && $(this).get(0).tagName.toLowerCase() != "td" && $(this).css("position").toLowerCase() != "absolute" && $(this).css("position").toLowerCase() != "fixed") {
						maxHeight = maxHeight - $(this).outerHeight(true);
					}

					if ($(this).attr("id") == $container.attr("id") && $(this).css("float") != "none") {
						inFloat = $(this).css("float");
					}
				});

				// calculate the padding and border
				maxHeight = maxHeight - ($container.outerHeight(true) - $container.height());

				if(setHeight !== false && ($container.hasClass("flexbox") || $container.css("overflow").toLowerCase() == "auto" || $container.css("overflow").toLowerCase() == "scroll" || $container.css("overflow").toLowerCase() == "hidden"))
					$container.css("height", maxHeight);
				$container.scrollTop(scroll);

				return maxHeight;
			};

		$(function () {
			$.extend(goma.ui, {
				/**
				 * this area is by default used to place content loaded via Ajax
				 */
				mainContent: $("#content").length ? $("#content") : $("body"),

				/**
				 * this area is by default used to place containers from javascript
				 */
				DocRoot: ($(".documentRoot").length === 1) ? $(".documentRoot") : $("body")
			});

            CheckForDomUpdate();
            // add retina-updae-event
            document.addEventListener && document.addEventListener("DOMContentLoaded", CheckForDomUpdate, false);
            if (/WebKit/i.test(navigator.userAgent)) {
                setInterval(function () {
                    /loaded|complete/.test(document.readyState) && requestAnimationFrame(CheckForDomUpdate);
                }, 250);
            }

			$(window).bind('beforeunload', goma.ui.fireUnloadEvents);

			$(window).resize(function () {
				var i;
				for ( i in flexBoxes) {
					updateFlexHeight($(flexBoxes[i]));
				}
			});
		});


		// build module
		return {

			JSFiles: [],
			JSIncluded: [],
			CSSFiles: [],
			CSSIncluded: [],

			/**
			 * defines if we are in backend
			 */
			is_backend: false,
			tags: {},
            checkForTags: checkForTags,

			/**
			 * registers a flex-box. A flex-box is a box, which have to be as high as the window minus some elements around.
			 * it calculates the correct elements around automatically.
			 *
			 * @param jquery-object
			 */
			addFlexBox: function ($container) {
				flexBoxes.push($container);
				updateFlexHeight($($container));
			},

			registerTags: function(tags) {
				this.tags = tags;
			},

			/**
			 * fires an update for flex-boxes
			 */
			updateFlexBoxes: function () {
				$(window).resize();
			},

			/**
			 * sets the main-content where to put by default content from ajax-requests
			 *
			 *@name setMainContent
			 *@param jQuery-Object | string (CSS-Path)
			 */
			setMainContent: function (node) {
				if ($(node).length > 0) {
					goma.ui.mainContent = $(node);
				}
			},

            loadHammer: function() {
                return goma.ui.loadAsync("hammer").done(function(){
                    window.hammer = $(document.body).hammer({
                        stop_browser_behavior: {
                            userSelect: ""
                        }
                    });
                });
            },

			/**
			 * enables css-transforms on a specified element with specified time and easing.
			 */
			enableCSSAnimation: function(elem, time, easing) {
				if(time === undefined)
					time = 300;

				if(easing == undefined)
					easing = "linear";

				var elem = $(elem);

				elem.css("transition", "all "+Math.round(time)+"ms "+easing);
				for(i in prefixes) {
					elem.css("-" + prefixes[i] + "-transition", "all "+Math.round(time)+"ms "+easing);
				}
			},

			/**
			 * enables css-transforms on a specified element.
			 */
			disableCSSAnimation: function(elem) {
				var elem = $(elem);

				elem.css("transition", "");
				for(i in prefixes) {
					elem.css("-" + prefixes[i] + "-transition", "");
				}
			},

			/**
			 * returns the main-content as jQuery-Object
			 */
			getMainContent: function () {
				return goma.ui.mainContent;
			},

            triggerContentLoaded: function() {
                var DOMContentLoaded_event = document.createEvent("Event");
                DOMContentLoaded_event.initEvent("DOMContentLoaded", true, true);
                window.document.dispatchEvent(DOMContentLoaded_event);
            },

			ajax: function (destination, options, unload, hideLoading) {
				if (hideLoading === undefined) {
					hideLoading = false;
				}

				var node = ($(destination).length > 0) ? $(destination) : goma.ui.getMainContent(),
					deferred = $.Deferred(),
					data;

				node.addClass("loading");

				if(options.showLoading) {
					node.html('<div class="loading">'+lang("loading")+'</div>');
				}

				if (unload !== false) {
					data = goma.ui.fireUnloadEvents(node);
					if (typeof data == "string") {
						if (!confirm(lang("unload_lang_start") + data + lang("unload_lang_end"))) {
							setTimeout(function () {
								deferred.reject("unload");
							}, 1);
							return deferred.promise();
						}
					}
				}

				if (!hideLoading) {
					goma.ui.setProgress(5).done(function () {
						goma.ui.setProgress(15, true);
					});

				}

				if (options.pushToHistory) {
					if (typeof HistoryLib.push == "function") {
						setTimeout(function () {
							HistoryLib.push(options.url);
						}, 30);
					}
				}

				$.ajax(options).done(function (r, c, a) {
					if (goma.ui.progress !== undefined) {
						goma.ui.setProgress(50);
					}

					goma.ui.renderResponse(r, a, node, undefined, false, true).done( function() {
						goma.ui.triggerContentLoaded();

						deferred.resolve(r,c,a);
						node.removeClass("loading");
						if (goma.ui.progress !== undefined) {
							goma.ui.setProgress(100);
						}
						goma.ui.updateFlexBoxes();

						var event = jQuery.Event( "updatehtml" );
						$(window).trigger(event);
					}).fail(deferred.reject);
				}).fail(function(a){
					node.addClass("failed").removeClass("loading");
					// try find out why it has failed
					if (a.textStatus == "timeout") {
						destination.prepend('<div class="error">Error while fetching data from the server: <br /> The response timed out.</div>');
					} else if (a.textStatus == "abort") {
						destination.prepend('<div class="error">Error while fetching data from the server: <br /> The request was aborted.</div>');
					} else {
						destination.prepend('<div class="error">Error while fetching data from the server: <br /> Failed to fetch data from the server.</div>');
					}

					deferred.reject(a);
				});

				return deferred.promise();
			},

			/**
			 * updates page and replaces all normal images with retina-images if defined in attribute data-retina of img-tag
			 */
			updateRetina: function () {
				RetinaReplace();
			},

			/**
			 * fires unload events and returns perfect result for onbeforeunload event
			 */
			fireUnloadEvents: function (node) {
				node = ($(node).length > 0) ? $(node) : goma.ui.getContentRoot();
				var event = jQuery.Event("onbeforeunload"),
					r = true;

				$(".g-unload-handler").each(function () {
					if ($(this).parents(node)) {
						$(this).trigger(event);
						if (typeof event.result == "string") {
							r = event.result;
						}
					}
				});

				if (node.hasClass("g-unload-handler")) {
					node.trigger(event);
					if (typeof event.result == "string") {
						r = event.result;
					}
				}
				if (r !== true) {
					return r;
				}
			},

			/**
			 * binds unload-event on specfic html-node
			 *
			 * @param string - selector for event-binding
			 * @param object - data //optional
			 * @param function - handler
			 */
			bindUnloadEvent: function (select, data, handler) {
				$(select).addClass("g-unload-handler");
				$(select).on("onbeforeunload", data, handler);
			},

			/**
			 * removes unbind-handler from specific object
			 *
             * @param string - selector
			 * @param function - handler to remove - optional
			 */
			unbindUnloadEvent: function (select, handler) {
				$(select).off("onbeforeunload", handler);
			},

			/**
			 * for loading data
			 * sets data loaded
			 */
			setLoaded: function (mod) {
				gloaded[mod] = true;
			},

			/**
			 * loading-script async
			 *
			 * @param string - mod
			 * @param function - fn
			 */
			loadAsync: loadScriptAsync,

			/**
			 * some base-roots in DOM
			 */
			getContentRoot: function () {
				return goma.ui.mainContent;
			},
			getDocRoot: function () {
				return goma.ui.DocRoot;
			},

			/**
			 * sets a progress of a async action as loading bar
			 *
			 *@name setProgress
			 */
			setProgress: function (percent, slowp) {
                var deferred = $.Deferred();
                goma.ui.loadAsync("ajaxLoader").done(function(){
                    goma.ui.ajaxloader.setProgress(percent, slowp).done(deferred.resolve).fail(deferred.reject);
                });
                return deferred.promise();
			},

            loadSubscribers: [],

			/**
			 * registers to the progress-event.
			 */
			onProgress: function (fn) {
				if (typeof fn == "function") {
					goma.ui.loadSubscribers.push(fn);
				}
			},

			/**
			 * global ajax renderer
			 */
			renderResponse: function (html, xhr, node, object, checkUnload, progress) {
                var deferred = $.Deferred();
                goma.ui.loadAsync("ajaxLoader").done(function(){
                    goma.ui.ajaxloader.renderResponse(html, xhr, node, object, checkUnload, progress).done(deferred.resolve).fail(deferred.reject);
                });
                return deferred.promise();
			},

			/**
			 * css and javascript-management
			 */

			/**
			 * register a resource loaded
			 */
			registerResource: function (type, file) {
				goma.ui.registerResources(type, [file]);
			},

			/**
			 * register resources loaded
			 */
			registerResources: function (type, files) {
				var i;
				switch(type) {
					case "css":

						for (i in files) {
                            if(files.hasOwnProperty(i)) {
                                goma.ui.CSSFiles[files[i]] = "";
                                goma.ui.CSSIncluded[files[i]] = true;
                            }
						}
						break;
					case "js":

						for (i in files) {
                            if(files.hasOwnProperty(i)) {
                                goma.ui.JSIncluded[files[i]] = true;
                            }
						}
						break;
				}
			},

			loadResources: function (request, progress) {
                var deferred = $.Deferred();
                goma.ui.loadAsync("ajaxLoader").done(function(){
                    goma.ui.ajaxloader.loadResources(request, progress).done(deferred.resolve).fail(deferred.reject);
                });
                return deferred.promise();
			},

			/**
			 * this method can only be called after loadResources
			 */
			runResources: function (request) {
				var js = request.getResponseHeader("X-JavaScript-Load"),
				//base_uri = request.getResponseHeader("x-base-uri"),
					jsfiles = js.split(";"),
					i,
					file;

				if (js != null) {
					for (i in jsfiles) {
                        if(jsfiles.hasOwnProperty(i)) {
                            file = jsfiles[i];
                            if (run_regexp.test(file) && goma.ui.JSFiles[file] !== undefined && goma.ui.JSIncluded[file] !== true) {
                                eval_global(goma.ui.JSFiles[file]);
                            }
                        }
					}
				}
			},

			// Helper Functions
			getDevicePixelRatio: function () {
				if (window.devicePixelRatio === undefined) { return 1; }
				return window.devicePixelRatio;
			},

			/**
			 * checks if a img were loaded correctly
			 *
			 *@name isImageOK
			 */
			IsImageOk: function (img) {
				// During the onload event, IE correctly identifies any images that
				// weren???t downloaded as not complete. Others should too. Gecko-based
				// browsers act like NS4 in that they report this incorrectly.
				if (!img.complete) {
					return false;
				}

				// However, they do have two very useful properties: naturalWidth and
				// naturalHeight. These give the true size of the image. If it failed
				// to load, either of these should be zero.

				if (img.naturalWidth !== undefined && img.naturalWidth == 0) {
					return false;
				}

				// No other way of checking: assume it???s ok.
				return true;
			},

			/**
			 * binds an action to ESC-Button when pressed while specific element
			 *
			 *@name bindESCAction
			 *@param node
			 *@param function
			 */
			bindESCAction: function (node, fn) {
				var f = fn;
				$(node).keydown(function (e) {
					var code = e.keyCode ? e.keyCode : e.which;
					if (code == 27) {
						f();
					}
				});
			}

		};
	})(jQuery);

	var gloader = {load: goma.ui.load, loadAsync: goma.ui.loadAsync};
}

if (goma.ENV === undefined) {
	goma.ENV = (function () {
		return {
			"jsversion": "2.0"
		};
	})();
}

// prevent from being executed twice
if (window.loader === undefined) {

	window.loader = true;

	// shuffle
	var array_shuffle = function (array) {
		var tmp, rand, i;
		for (i = 0; i < array.length; i++) {
			rand = Math.floor(Math.random() * array.length);
			tmp = array[i];
			array[i] = array[rand];
			array[rand] =tmp;
		}
		return array;
	};

	// put methods into the right namespace
	(function ($, w) {

		// some browsers don't like this =D
		"use strict";

		$.fn.inlineOffset = function () {
			var el = $('<i/>').css('display','inline').insertBefore(this[0]),
				pos = el.offset();

			el.remove();
			return pos;
		};

		$(function () {

			/**
			 * ajaxfy is a pretty basic and mostly by PHP-handled Ajax-Request, we get back mostly javascript, which can be executed
			 */
			$(document).on("click", "a[rel=ajaxfy], a.ajaxfy", function ()
			{
				var $this = $(this),
					_html = $this.html(),
					$container = $this.parents(".record").attr("id");

				$this.html("<img src=\"system/images/16x16/ajax-loader.gif\" alt=\"loading...\" />");

				$.ajax({
					url: $this.attr("href"),
					data: {"container": $container, "ajaxfy": true},
					dataType: "html",
					headers: {
						accept: "text/javascript; charset=utf-8"
					}
				}).done(function (html, textStatus, jqXHR) {
					eval_script(html, jqXHR);
					$this.html(_html);
				}).fail(function (jqXHR) {
					eval_script(jqXHR.responseText, jqXHR);
					$this.html(_html);
				});
				return false;
			});

			/**
			 * ui-ajax is the class for loading data over goma.ui.ajax and rendering it into an element.
			 */
			$(document).on("click", "a[rel=ui-ajax], a.ui-ajax", function ()
			{
				var $this = $(this);
				var destination = $this.attr("data-destination") ? $this.attr("data-destination") : undefined;
				$this.addClass("loading");
				goma.ui.ajax(destination, {
					pushToHistory: (!$(this).hasClass("no-history")),
					url: $this.attr("href"),
					data: {"ajaxfy": true}
				}).done(function () {
					$this.removeClass("loading");
				});
				return false;
			});

			// new dropdownDialog, which is very dynamic and greate
			$(document).on("click", "a[rel*=dropdownDialog], a.dropdownDialog, a.dropdownDialog-left, a.dropdownDialog-right, a.dropdownDialog-center, a.dropdownDialog-bottom", function ()
			{
				var $this = $(this);
				goma.ui.loadAsync("dropdownDialog").done(function () {
					var options = {
						uri: $this.attr("data-href") ? $this.attr("data-href") : $this.attr("href")
					};
					if ($this.attr("rel") == "dropdownDialog[fixed]" || $this.hasClass("dropdownDialog-fixed"))
						options.position = "fixed";
					if ($this.attr("rel") == "dropdownDialog[left]" || $this.hasClass("dropdownDialog-left"))
						options.position = "left";
					else if ($this.attr("rel") == "dropdownDialog[center]" || $this.hasClass("dropdownDialog-center"))
						options.position = "center";
					else if ($this.attr("rel") == "dropdownDialog[right]" || $this.hasClass("dropdownDialog-right"))
						options.position = "right";
					else if ($this.attr("rel") == "dropdownDialog[bottom]" || $this.hasClass("dropdownDialog-bottom"))
						options.position = "bottom";

					$this.dropdownDialog(options);
				});

				return false;
			});

			/**
			 * addon for z-index
			 * every element with class="windowzindex" is with this plugin
			 * it makes the clicked one on top
			 */
			$(document).on('click', ".windowzindex", function () {
				$(".windowzindex").parent().css('z-index', 900);
				$(this).parent().css("z-index", 901);
			});
			if (!Modernizr.placeholder) {
				goma.ui.loadAsync("placeholderPolyfill");
			}

			// scroll fix
			$(document).on("click", "a", function () {
				if ($(this).attr("href") && $(this).attr("href").substring(0,1) == "#") {
					scrollToHash($(this).attr("href").substr(1));
					return false;
				} else if (typeof $(this).attr("data-anchor") == "string" && $(this).attr("data-anchor") != "") {
					scrollToHash($(this).attr("data-anchor"));
					return false;
				}
			});

			// scroll to right position
			if ($("#frontedbar").length == 1) {
				if (location.hash != "") {
					scrollToHash(location.hash.substring(1));
				}
			}

		});

		// SOME GLOBAL METHODS

		// language
		var lang = [];

		/**
		 * returns language-data.
		 *
		 *@name lang
		 */
		w.lang = function (name, _default) {
			if (typeof BASE_SCRIPT == "undefined")
				return false;

			if (lang[name.toUpperCase()] == null) {
				return (typeof _default == "undefined") ? _default : name;
			} else {
				return lang[name.toUpperCase()];
			}
		};

		/**
		 * sets language-data.
		 */
		w.setLang = function(data) {
			lang = data;
		};

		/**
		 * gets language array.
		 * @returns {Array}
		 */
		w.getLang = function() {
			return lang;
		};

		/**
		 * starts a indexing for search.
		 */
		w.startIndexing = function() {
			$.ajax({
				url: BASE_SCRIPT + "system/indexSearch"
			}).done(function(){
				startIndexing();
			});
		};

		/**
		 * returns the root of the document
		 */
		w.getDocRoot = function () {
			return goma.ui.getDocRoot();
		};

		w.preloadLang = function () {

		};

		// some response handlers
		w.eval_script = function (html, ajaxreq, object) {
			return goma.ui.renderResponse(html, ajaxreq, undefined, object, false);
		};

		w.renderResponseTo = function (html, node, ajaxreq, object, unload) {
			return goma.ui.renderResponse(html, ajaxreq, node, object, unload);
		};

		w.LoadAjaxResources = function (request) {
			return goma.ui.loadResources(request);
		};

		w.RunAjaxResources = function (request) {
			return goma.ui.runResources(request);
		};


		/**
		 * if you have a search-field in a widget in a form, users should can press enter without submitting the form
		 * use this method to make this happen ;)
		 *
		 *@name unbindFormFormSubmit
		 *@param node
		 */
		w.unbindFromFormSubmit = function (node) {
			// first make sure it works!
			var active = false;
			$(node).focus(function () {
				active = true;
			});

			$(node).blur(function () {
				active = false;
			});

			$(node).parents("form").bind("formsubmit", function () {
				if (active) {
					return false;
				}
			});

			$(node).parents("form").bind("submit", function () {
				if (active) {
					return false;
				}
			});

			// second use a better method, just if the browser support it
			$(node).keydown(function (e) {
				var code = e.keyCode ? e.keyCode : e.which;
				if (code == 13) {
					return false;
				}
			});
		};

		/**
		 * if you have a dropdown and you want to close it on click on the document, but not on the dropdown, use this function
		 *
		 * @name CallonDocumentClick
		 * @param fn
		 * @param array - areas, which aren't calling this function (css-selectors). Attentien: These selectors need to exist!
		 */
		w.CallonDocumentClick = function (call, exceptions) {
			var fn = call,
				mouseover = false,
				timeout,
				i,


				// function if we click or tap on an exception
				exceptionFunc = function (e) {
					clearTimeout(timeout);
					mouseover = true;
					timeout = setTimeout(function () {
						mouseover = false;
					}, 300);
				},

				// function if we click anywhere
				mouseDownFunc = function (e) {
					setTimeout(function () {
						if (mouseover === false) {
							fn(e);
						}
					}, 10);
				};

			var exceptionEvents = ["mouseup", "mousedown", "touchend", "touchstart"];
			if (exceptions) {
				for (i in exceptions) {
					if(exceptions.hasOwnProperty(i)) {
                        for (var eventI in exceptionEvents) {
                            $(exceptions[i]).on(exceptionEvents[eventI], exceptionFunc);
                            $(exceptions[i]).each(function () {
                                $(this).get(0).addEventListener(exceptionEvents[eventI], exceptionFunc, true);
                            })
                        }
                    }
				}
			}
			// init mouseover-events
			$(window).on("mouseup", mouseDownFunc);
			$(window).on("touchend", mouseDownFunc);
			$("iframe").each(function () {
				try {
					var w = $(this).get(0).contentWindow;
					if (w) {
						$(w).on("mouseup", mouseDownFunc);
						$(w).on("touchend", mouseDownFunc);
					}
				} catch(e) {}
			});
		};
		w.callOnDocumentClick = w.CallonDocumentClick;

		// jQuery Extensions

		// @url http://stackoverflow.com/questions/955030/remove-css-from-a-div-using-jquery
		//this parse style & remove style & rebuild style. I like the first one.. but anyway exploring..
		$.fn.extend
		({
			removeCSS: function (cssName) {
				return this.each(function () {

					return $(this).attr('style',

						$.grep($(this).attr('style').split(";"),
							function (curCssName) {
								if (curCssName.toUpperCase().indexOf(cssName.toUpperCase() + ':') <= 0)
									return curCssName;
							}).join(";"));
				});
			}
		});


		// save settings of last ajax request
		w.request_history = [];
		w.event_history = [];

		$.ajaxPrefilter( function ( options, originalOptions, jqXHR ) {
			if (originalOptions.noRequestTrack == null) {
				var data = originalOptions;
				jqXHR.always(function () {
					w.request_history.push(data);
				});

				if (typeof originalOptions.silence == "undefined" || originalOptions.silence != true)
					if (originalOptions.type == "post" && originalOptions.async != false) {
						jqXHR.fail(function () {
							if (jqXHR.textStatus == "timeout") {
								console.log && console.log('Error while saving data to the server: \nThe response timed out.\n\n' + originalOptions.url);
							} else if (jqXHR.textStatus == "abort") {
								console.log && console.log('Error while saving data to the server: \nThe request was aborted.\n\n' + originalOptions.url);
							} else {
								console.log && console.log('Error while saving data to the server: \nFailed to save data on the server.\n\n' + originalOptions.url);
							}
						});
					} else {
						jqXHR.fail(function () {

							if (jqXHR.textStatus == "timeout") {
								console.log && console.log('Error while fetching data from the server: \nThe response timed out.\n\n' + originalOptions.url);
							} else if (jqXHR.textStatus == "abort") {
								console.log && console.log('Error while fetching data from the server: \nThe request was aborted.\n\n' + originalOptions.url);
							} else {
								console.log && console.log('Error while fetching data from the server: \nFailed to fetch data from the server.\n\n' + originalOptions.url);
							}
						});
					}
			}

			jqXHR.setRequestHeader("X-Referer", location.href);
			jqXHR.setRequestHeader("X-Requested-With", "XMLHttpRequest");
			if (goma.ENV.is_backend)
				jqXHR.setRequestHeader("X-Is-Backend", 1);
		});

		w.event_history = [];
		$.orgajax = $.ajax;
		$.ajax = function (url, options) {

			var w = window;

			var jqXHR = $.orgajax.apply(this, [url, options]);

			if (typeof options != "undefined" && options.noRequestTrack == null || url.noRequestTrack == null) {
				var i = w.event_history.length;
				w.event_history[i] = {done: [], fail: [], always: []};

				jqXHR._done = jqXHR.done;
				jqXHR.done = function (fn) {
					w.event_history[i]["done"].push(fn);
					return jqXHR._done(fn);
				}

				jqXHR._fail = jqXHR.fail;
				jqXHR.fail = function (fn) {
					w.event_history[i]["fail"].push(fn);
					return jqXHR._fail(fn);
				}

				jqXHR._always = jqXHR.always;
				jqXHR.always = function (fn) {
					w.event_history[i]["always"].push(fn);
					return jqXHR._always(fn);
				}
			}

			return jqXHR;
		};

		/* API to run earlier Requests with a bit different options */
		w.runLastRequest = function (data) {
			return w.runPreRequest(0, data);
		};
		w.runPreRequest = function (i, data) {
			var a = self.request_history.length - 1 - parseInt(i);
			var options = $.extend(self.request_history[a], data);
			if (self.request_history[a].data != null && typeof self.request_history[a].data != "string" && typeof data.data == "object") {
				options.data = $.extend(self.request_history[a].data, data.data);
			}
			var jqXHR = $.ajax(options);
			for (var i in w.event_history[a]["done"]) {
				jqXHR.done(w.event_history[a]["done"][i]);
			}
			for (var i in w.event_history[a]["always"]) {
				jqXHR.always(w.event_history[a]["always"][i]);
			}
			for (var i in w.event_history[a]["fail"]) {
				jqXHR.fail(w.event_history[a]["fail"][i]);
			}
			return jqXHR;
		}

	})(jQuery, window);

	// trim
	// thanks to @url http://www.somacon.com/p355.php
	String.prototype.trim = function () {
		return this.replace(/^\s+|\s+$/g,"");
	};
	String.prototype.ltrim = function () {
		return this.replace(/^\s+/,"");
	};
	String.prototype.rtrim = function () {
		return this.replace(/\s+$/,"");
	};

	function randomString(string_length) {
		var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
		var randomstring = '';
		for (var i=0; i<string_length; i++) {
			var rnum = Math.floor(Math.random() * chars.length);
			randomstring += chars.substring(rnum,rnum+1);
		}
		return randomstring;
	}

	function is_string(input) {
		return (typeof(input) == 'string');
	}


	/**
	 *@link http://msdn.microsoft.com/en-us/library/ms537509(v=vs.85).aspx
	 */
	function getInternetExplorerVersion()
	// Returns the version of Internet Explorer or a -1
	// (indicating the use of another browser).
	{
		var rv = -1; // Return value assumes failure.
		if (navigator.appName == 'Microsoft Internet Explorer')
		{
			var ua = navigator.userAgent;
			var re  = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
			if (re.exec(ua) != null)
				rv = parseFloat( RegExp.$1 );
		}
		return rv;
	}

	function getFirefoxVersion()
	{
		var rv = -1; // if not found
		var ua = navigator.userAgent;
		var regexp_firefox = /Firefox/i;
		if (regexp_firefox.test(ua)) {
			var re  = new RegExp("Firefox/([0-9]{1,}[\.0-9]{0,})");
			if (re.exec(ua) != null)
				rv = parseFloat( RegExp.$1 );
		}
		return rv;
	}

	/**
	 * cookies, thanks to @url http://www.w3schools.com/JS/js_cookies.asp
	 */
	function setCookie(c_name,value,exdays)
	{
		var exdate=new Date();
		exdate.setDate(exdate.getDate() + exdays);
		var c_value=encodeURIComponent(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString()) + "; path=/";
		document.cookie=c_name + "=" + c_value;
	}

	function getCookie(c_name)
	{
		var i,x,y,ARRcookies=document.cookie.split(";");
		for (i=0;i<ARRcookies.length;i++)
		{
			x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
			y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
			x=x.replace(/^\s+|\s+$/g,"");
			if (x==c_name)
			{
				return decodeURIComponent(y);
			}
		}
	}

	function isIDevice() {
		return /(iPad|iPhone|iPod)/.test(navigator.userAgent);
	}

	function isiOS5() {
		return isIDevice() && navigator.userAgent.match(/AppleWebKit\/(\d*)/)[1]>=534;
	}

	function isJSON(content) {
		return json_regexp.test(content);
	}

	// patch for IE eval
	function eval_global(codetoeval) {
		try {
			if (window.execScript)
				window.execScript(codetoeval); // execScript doesn???t return anything
			else
				window.eval(codetoeval);
		} catch(e) {
            console.log && console.log(codetoeval);
			console.log && console.log(e);
			throw e;
		}
	}

	// parse JSON
	function parseJSON(str) {
		if (str.substring(0, 1) == "(") {
			str = str.substr(1);
		}

		if (str.substr(str.length - 1) == ")") {
			str = str.substr(0, str.length -1);
		}

		return $.parseJSON(str);
	}

	function microtime (get_as_float) {
		// Returns either a string or a float containing the current time in seconds and microseconds
		//
		// version: 1109.2015
		// discuss at: http://phpjs.org/functions/microtime
		// +   original by: Paulo Freitas
		// *     example 1: timeStamp = microtime(true);
		// *     results 1: timeStamp > 1000000000 && timeStamp < 2000000000
		var now = new Date().getTime() / 1000;
		var s = parseInt(now, 10);

		return (get_as_float) ? now : (Math.round((now - s) * 1000) / 1000) + ' ' + s;
	}

	function str_repeat (input, multiplier) {
		// Returns the input string repeat mult times
		//
		// version: 1109.2015
		// discuss at: http://phpjs.org/functions/str_repeat
		// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		// +   improved by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
		// *     example 1: str_repeat('-=', 10);
		// *     returns 1: '-=-=-=-=-=-=-=-=-=-='
		return new Array(multiplier + 1).join(input);
	}

    var scrollToHash = function (hash, recalculate) {
        var scrollPosition,
            hashJqueryById = $("#" + hash);
        if (hashJqueryById.length > 0) {
            scrollPosition = hashJqueryById.offset().top - 10;
        } else if ($("a[name="+hash+"]").length > 0) {
            scrollPosition = $("a[name="+hash+"]").offset().top - 10;
        } else {
            scrollPosition = 0;
        }

        scrollPosition = Math.round(Math.max(0, scrollPosition));

        var stuckElements = $(".is_stuck.goma_is_fixed");
        if(stuckElements.length > 0) {
            stuckElements.each(function(){
                scrollPosition -= $(this).outerHeight(false);
            });
        }

        if(recalculate !== undefined) {
            if(recalculate < scrollPosition) {
                return;
            }
        }

        var scroll = $(window).scrollTop();
        window.location.hash = hash;
        $(window).scrollTop(scroll);

        $("html, body").animate({
            "scrollTop": scrollPosition
        }, 200, "swing", function(){
            if(recalculate === undefined) {
                scrollToHash(hash, scrollPosition);
            }
        });
    };

	var now = function () {
		return Math.round(+new Date()/1000);
	};

	/**
	 * returns a string like 2 seconds ago
	 *
	 *@name ago
	 *@param int - unix timestamp
	 */
	var ago = function (time) {
		var diff = now() - time;
		if (diff < 60) {
			return lang("ago.seconds", "about %d seconds ago").replace("%d", Math.round(diff));
		} else if (diff < 90) {
			return lang("ago.minute", "about one minute ago");
		} else {
			diff = diff / 60;
			if (diff < 60) {
				return lang("ago.minutes", "about %d minutes ago").replace("%d", Math.round(diff));
			} else {
				diff = diff / 60;
				if (Math.round(diff) == 1) {
					return lang("ago.hour", "about one hour ago");
				} else if (diff < 24) {
					return lang("ago.hours", "%d hours ago").replace("%d", Math.round(diff));
				} else {
					diff = diff / 24;
					if (Math.round(diff * 10) <= 11) {
						return lang("ago.day", "about one day ago");
					} else {
						// unsupported right now
						return false;
					}
				}
			}
		}
	}

	setInterval(function () {
		$(".ago-date").each(function () {
			if ($(this).attr("data-date")) {
				$(this).html(ago($(this).attr("data-date")));
			}
		});
	}, 1000);
}

function isTouchDevice(){
	return true == ("ontouchstart" in window || window.DocumentTouch && document instanceof DocumentTouch);
}

var measurePerformance = function(timeout) {
	var data = [];
	var myTimeout = timeout === undefined ? 2000 : timeout;
	var measurePerformanceCycle = function(i) {
		var current = i;
		$.ajax({
			url: location.href
		}).done(function(response, status, jqXHR){
			data.push(parseFloat(jqXHR.getResponseHeader("x-time")));
			if(current != 19) {
				console.log(current + " done in " + jqXHR.getResponseHeader("x-time") + "s");
				setTimeout(function(){
					measurePerformanceCycle(current + 1);
				}, myTimeout);
			} else {
				console.log(data);
				var sum = 0;
				for(var i in data) {
					if(data.hasOwnProperty(i)) {
						sum += data[i];
					}
				}
				var mean = sum / data.length;
				console.log("mean: " + mean)
			}
		});
	};
	measurePerformanceCycle(0);
};
