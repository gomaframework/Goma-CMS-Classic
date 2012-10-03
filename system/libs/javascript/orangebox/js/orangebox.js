/*
 * version: 1.0 Beta4
 * package: OrangeBox
 * author: David Paul Hamilton - http://orangebox.davidpaulhamilton.net
 * copyright: Copyright (c) 2011 David Hamilton / DavidPaulHamilton.net All rights reserved.
 * license: GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */
var ob_fadeInTime = 200;
var ob_fadeOutTime = 200;
var ob_overlayOpacity = 0.9;
var ob_maxVideoHeight = 390;
var ob_maxVideoWidth = 640;
var ob_preloaderDelay = 600;
var ob_unsupportedMedia_Message = "Unsupported Media";
var ob_fileNotFound_Message = "File Not Found";
var ob_showDots = true;
var ob_showNav = false;
var ob_showClose = true;
var ob_showTitle = false;
var ob_keyboardNavigation = true;
var ob_contentBorderWidth = 4;
var ob_contentMinHeight = 100;
var ob_contentMinWidth = 200;
var ob_imageBorderWidth = 1;
var ob_inineWidth = 0.75;
var ob_inineHeight = 0;
var ob_maxImageHeight = 0.75;
var ob_maxImageWidth = 0.75;
var ob_iframeWidth = 0.75;
var ob_iframeHeight = 0.75;
if (typeof(orangebox) !== 'undefined') {
    ob_fadeInTime = parseFloat(orangebox.ob_fadeInTime);
    ob_fadeOutTime = parseFloat(orangebox.ob_fadeOutTime);
    ob_overlayOpacity = parseFloat(orangebox.ob_overlayOpacity);
    ob_maxVideoHeight = parseFloat(orangebox.ob_maxVideoHeight);
    ob_maxVideoWidth = parseFloat(orangebox.ob_maxVideoWidth);
    ob_preloaderDelay = parseFloat(orangebox.ob_preloaderDelay);
    ob_unsupportedMedia_Message = orangebox.ob_unsupportedMedia_Message;
    ob_fileNotFound_Message = orangebox.ob_fileNotFound_Message;
    ob_showDots = orangebox.ob_showDots;
    ob_showNav = orangebox.ob_showNav;
    ob_showClose = orangebox.ob_showClose;
    ob_showTitle = orangebox.ob_showTitle;
    ob_keyboardNavigation = orangebox.ob_keyboardNavigation;
    ob_contentBorderWidth = parseFloat(orangebox.ob_contentBorderWidth);
    ob_contentMinHeight = parseFloat(orangebox.ob_contentMinHeight);
    ob_contentMinWidth = parseFloat(orangebox.ob_contentMinWidth);
    ob_imageBorderWidth = parseFloat(orangebox.ob_imageBorderWidth);
    ob_inineWidth = parseFloat(orangebox.ob_inineWidth);
    ob_inineHeight = parseFloat(orangebox.ob_inineHeight);
    ob_maxImageWidth = parseFloat(orangebox.ob_maxImageWidth);
    ob_maxImageHeight = parseFloat(orangebox.ob_maxImageHeight);
    ob_iframeWidth = parseFloat(orangebox.ob_iframeWidth);
    ob_iframeHeight = parseFloat(orangebox.ob_iframeHeight);
}(function ($) {
    $.fn.extend({
        orangebox: function () {
            return this.each(function () {
                $(this).click(function (e) {
                    var modalTitle = $('<div id="ob_title"></div>');
                    var modalClose = '<div title="close" id="ob_close"></div>';
                    var modalNavRight = $('<a class="ob_nav" id="ob_right"><span id="ob_right-ico"></span></a>');
                    var modalNavLeft = $('<a class="ob_nav" id="ob_left"><span id="ob_left-ico"></span></a>');
                    var modalContent = $('<div id="ob_content"></div>');
                    var overlay = $('<div id="ob_overlay"></div>');
                    var modalWindow = $('<div id="ob_window"></div>');
                    var modalContainer = $('<div id="ob_container"></div>');
                    var modalFloat = $('<div id="ob_float"></div>');
                    var dotnav = $('<ul id="ob_dots"></ul>');
                    var ob_load = $('<div id="ob_load"></div>');
                    var t;
                    var imageType = [".jpg", ".png", ".jpeg", ".bmp", ".gif"];
                    var quicktimeType = [".mov", ".mp4", ".m4v"];
                    var lightboxlink = 'a[rel=lightboxlink]';
                    var mainhref = $(this).attr("href");
                    var maintitle = $(this).attr("title");
                    var rel = $(this).attr('rel');
                    var height;
                    var width;
                    var galleryItems = new Array();
                    var titles = new Array();
                    var currentIndex;
                    var progress = null;
                    var s;
                    var content;
                    var gallery;
                    overlay.css({
                        "opacity": ob_overlayOpacity,
                        "min-width": $(window).width(),
                        "min-height": $(window).height()
                    });
                    modalContent.css({
                        "border-width": ob_contentBorderWidth,
                        "min-height": ob_contentMinHeight,
                        "min-width": ob_contentMinWidth
                    });
                    e.preventDefault();
                    if (typeof document.body.style.maxHeight === "undefined") {
                        $("body", "html").css({
                            height: "100%",
                            width: "100%"
                        });
                    }
                    t = setTimeout(function () {
                        $("body").append(ob_load);
                    }, ob_preloaderDelay);

                    function checkContentType(itemhref) {
                        if (itemhref === "ob_hidden_set") {
                            return "ob_hidden_set";
                        }
                        if (itemhref.match(/\?iframe$/)) {
                            return "iframe";
                        }
                        if (itemhref.match(/\.(?:jpg|jpeg|bmp|png|gif)$/)) {
                            return "image";
                        }
                        if (itemhref.match(/\.(?:mov|mp4|m4v)(\?.{6,}\&.{6,})?$/)) {
                            return "quicktime";
                        }
                        if (itemhref.match(/\.swf(\?.{6,}\&.{6,})?$/)) {
                            return "flash";
                        }
                        if (itemhref.match(/^http:\/\/\w{0,3}\.?youtube\.\w{2,3}\/watch\?v=[\w\-]{11}/)) {
                            return "youtube";
                        }
                        if (itemhref.match(/^http:\/\/\w{0,3}\.?vimeo\.com\/\d{1,10}/)) {
                            return "vimeo";
                        }
                        if (itemhref.match(/^#/)) {
                            return "inline";
                        }
                        return;
                    }
                    if (rel.substring(8)) {
                        gallery = rel.substring(rel.indexOf("[") + 1, rel.indexOf("]"));
                    }
                    if (gallery) {
                        var arrayID = 0;
                        var objectMatch = 'a[rel*=\'lightbox[' + gallery + ']\']';
                        $(objectMatch).each(function () {
                            var itemhref = $(this).attr("href");
                            var title = $(this).attr("title");
                            var contentType = checkContentType(mainhref);
                            var inGallery = jQuery.inArray(itemhref, galleryItems);
                            if (contentType && inGallery === -1 && itemhref !== "ob_hidden_set") {
                                galleryItems[arrayID] = itemhref;
                                titles[arrayID] = title;
                                if (ob_showDots) {
                                    if (arrayID === 0) {
                                        dotnav.append('<li class="current" id="ob_dot' + arrayID + '"></li>');
                                    } else {
                                        dotnav.append('<li id="ob_dot' + arrayID + '"></li>');
                                    }
                                }
                                arrayID++;
                            }
                        });
                        if ($('#ob_gallery').length > 0) {
                            $('#ob_gallery li.' + gallery).each(function () {
                                var itemhref = $(this).attr("id");
                                var title = $(this).attr("title");
                                var contentType = checkContentType(mainhref);
                                var inGallery = jQuery.inArray(itemhref, galleryItems);
                                if (contentType && inGallery === -1) {
                                    galleryItems[arrayID] = itemhref;
                                    titles[arrayID] = title;
                                    if (ob_showDots) {
                                        dotnav.append('<li id="ob_dot' + arrayID + '"></li>');
                                    }
                                    arrayID++;
                                }
                            });
                        }
                    }
                    function navigate(href, title) {
                        clearTimeout(t);
                        t = setTimeout(function () {
                            $("body").append(ob_load);
                        }, ob_preloaderDelay);
                        modalWindow.fadeOut(ob_fadeOutTime, function () {
                            content.empty();
                            modalTitle.empty().remove();
                            modalContent.empty().remove();
                            modalWindow.empty().remove();
                            modalContainer.empty();
                            var contentType = checkContentType(href);
                            if (contentType === "iframe") {
                                showiFrame(href, title);
                            } else if (contentType === "image") {
                                showImage(href, title);
                            } else if (contentType === "inline") {
                                showInline(href, title);
                            } else if (contentType === "quicktime") {
                                showVideo(href, title, contentType);
                            } else if (contentType === "youtube") {
                                showVideo(href, title, contentType);
                            } else if (contentType === "vimeo") {
                                showVideo(href, title, contentType);
                            } else if (contentType === "flash") {
                                showVideo(href, title, contentType);
                            }
                        });
                    }
                    function navigateHandler(index) {
                        if (galleryItems[index]) {
                            var href = galleryItems[index];
                            var title = titles[index];
                            currentIndex = index;
                            navigate(href, title);
                        } else {
                            progress = null;
                        }
                    }
                    function showNavigation(currenthref) {
                        for (var i = 0; i < galleryItems.length; i++) {
                            if (galleryItems[i] == currenthref) {
                                currentIndex = i;
                            };
                        }
                        if (ob_showDots) {
                            modalWindow.append(dotnav);
                            dotnav.find("li").click(function () {
                                if (!$(this).hasClass('current')) {
                                    var id = $(this).attr('id');
                                    var newid = id.substr(6);
                                    dotnav.find("li").removeClass('current');
                                    $(this).addClass('current');
                                    navigateHandler(newid);
                                }
                            });
                        }
                        if (ob_showNav) {
                            modalWindow.append(modalNavRight).append(modalNavLeft);
                            if (galleryItems[currentIndex + 1]) {
                                modalNavRight.show();
                            } else {
                                modalNavRight.hide();
                            };
                            if (galleryItems[currentIndex - 1]) {
                                modalNavLeft.show();
                            } else {
                                modalNavLeft.hide();
                            };
                            modalNavLeft.click(function (e) {
                                e.stopPropagation();
                                navigateHandler(currentIndex - 1);
                            });
                            modalNavRight.click(function (e) {
                                e.stopPropagation();
                                navigateHandler(currentIndex + 1);
                            });
                        }
                    }
                    function handleEscape(e) {
                        if (progress == null) {
                            progress = "running";
                            if (e.keyCode == 27) {
                                modalHide();
                            } else if (e.keyCode == 37) {
                                navigateHandler(currentIndex - 1);
                            } else if (e.keyCode == 39) {
                                navigateHandler(currentIndex + 1);
                            } else {
                                progress = null;
                            };
                        };
                    }
                    function modalHide() {
                        $(document).unbind("keydown", handleEscape);
                        var remove = function () {
                            $(this).remove().empty();
                        };
                        clearTimeout(t);
                        ob_load.remove();
                        overlay.fadeOut(ob_fadeOutTime, remove);
                        modalContainer.fadeOut(ob_fadeOutTime, remove);
                    }
                    $("body").append(overlay.click(function () {
                        modalHide();
                    }));
                    $("body").append(modalContainer.click(function () {
                        modalHide();
                    }));
                    if (ob_keyboardNavigation) {
                        $(document).keydown(handleEscape);
                    }
                    overlay.show(ob_fadeInTime);

                    function setModalProperties() {
                        var objectHeight = content.outerHeight();
                        var objectWidth = content.outerWidth();
                        var windowHeight = objectHeight + (ob_contentBorderWidth * 2);
                        var windowWidth = objectWidth + (ob_contentBorderWidth * 2);
                        if (windowHeight < ob_contentMinHeight) {
                            windowHeight = ob_contentMinHeight + (ob_contentBorderWidth * 2);
                        };
                        if (windowWidth < ob_contentMinWidth) {
                            windowWidth = ob_contentMinWidth + (ob_contentBorderWidth * 2);
                        };
                        modalContainer.css({
                            "margin-top": $(window).scrollTop()
                        });
                        modalWindow.css({
                            "height": windowHeight,
                            "width": windowWidth
                        });
                        modalFloat.css({
                            "margin-bottom": -$('#ob_window').outerHeight() / 2
                        });
                        modalWindow.click(function (e) {
                            e.stopPropagation();
                        });
                        $(lightboxlink).click(function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            navigate($(this).attr('href'), $(this).attr('title'))
                        });
                        if (ob_showDots) {
                            var current = '#ob_dot' + currentIndex;
                            dotnav.find("li").removeClass('current');
                            $(current).addClass('current');
                        };
                        progress = null;
                    }
                    function buildit(title, href) {
                        clearTimeout(t);
                        ob_load.remove();
                        modalTitle.append('<h3>' + title + '</h3>');
                        modalContent.append(content);
                        modalWindow.append(modalContent);
                        if (ob_showClose) {
                            modalWindow.append(modalClose);
                        };
                        if (ob_showTitle) {
                            modalWindow.append(modalTitle);
                        };
                        if (galleryItems.length > 1) showNavigation(href);
                        modalContainer.append(modalFloat).append(modalWindow);
                        modalWindow.fadeIn(ob_fadeInTime);
                        $("#ob_close").click(function () {
                            modalHide();
                        });
                        setModalProperties();
                    }
                    function throwError(type) {
                        var message;
                        if (type == 1) {
                            message = ob_unsupportedMedia_Message;
                        } else if (type == 2) {
                            message = ob_fileNotFound_Message;
                        };
                        content = $('<div id="ob_error">' + message + '</div>');
                        clearTimeout(t);
                        ob_load.remove();
                        modalContent.empty().append(content).css('min-height', '0');
                        modalWindow.empty().append(modalContent);
                        if (ob_showClose) {
                            modalWindow.append(modalClose);
                        };
                        modalContainer.empty().append(modalFloat).append(modalWindow);
                        modalWindow.fadeIn(ob_fadeInTime);
                        $("#ob_close").click(function () {
                            modalHide();
                        });
                        setModalProperties();
                    }
                    function showiFrame(href, title) {
                        var args = 'height="100%" width="100%" frameborder="0" hspace="0" scrolling="auto"';
                        var newhref = href.replace(/\?iframe$/, '');
                        content = $('<iframe id="ob_iframe" ' + args + ' src="' + newhref + '"></iframe>');
                        if (ob_iframeWidth > 1) {
                            content.css({
                                "width": ob_iframeWidth
                            });
                        } else if (ob_iframeWidth > 0) {
                            content.css({
                                "width": $(window).width() * ob_iframeWidth
                            });
                        }
                        if (ob_iframeHeight > 1) {
                            content.css({
                                "height": ob_iframeHeight
                            });
                        } else if (ob_iframeHeight > 0) {
                            content.css({
                                "height": $(window).height() * ob_iframeHeight
                            });
                        }
                        buildit(title, href);
                    }
                    function showInline(href, title) {
                        content = $('<div id="ob_inline">' + $(href).html() + '</div>');
                        if (ob_inineWidth > 1) {
                            content.css({
                                "width": ob_inineWidth
                            });
                        } else if (ob_inineWidth > 0) {
                            content.css({
                                "width": $(window).width() * ob_inineWidth
                            });
                        }
                        if (ob_inineHeight > 1) {
                            content.css({
                                "height": ob_inineHeight
                            });
                        } else if (ob_inineHeight > 0) {
                            content.css({
                                "height": $(window).height() * ob_inineHeight
                            });
                        }
                        buildit(title, href);
                    }
                    function showVideo(href, title, contentType) {
                        var idIndex;
                        var ID;
                        var heightIndex = href.indexOf("height=") + 7;
                        var widthIndex = href.indexOf("width=") + 6;
                        var ampIndex = href.indexOf("&");
                        var ratio = 0;
                        if (href.indexOf("height=") >= 0 && href.indexOf("width=") >= 0) {
                            if (widthIndex > ampIndex) {
                                height = href.substring(heightIndex, ampIndex);
                                width = href.substring(widthIndex);
                            } else if (widthIndex < ampIndex) {
                                height = href.substring(heightIndex);
                                width = href.substring(widthIndex, ampIndex);
                            };
                            if (height > ob_maxVideoHeight) {
                                ratio = ob_maxVideoHeight / height;
                                width = width * ratio;
                                height = ob_maxVideoHeight;
                            };
                        } else {
                            width = ob_maxVideoWidth;
                            height = ob_maxVideoHeight;
                        };
                        var paramStart = '<div><object width="' + width + '" height="' + height + '"><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><param name="movie" value="';
                        var paramEnd = 'type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' + width + '" height="' + height + '"></embed></object></div>';
                        if (contentType == "youtube") {
                            idIndex = href.indexOf("?v=") + 3;
                            if (href.indexOf("&") >= 0) {
                                ID = href.substring(idIndex, ampIndex);
                            } else {
                                ID = href.substring(idIndex);
                            };
                            content = $(paramStart + 'http://www.youtube.com/v/' + ID + '?fs=1&hl=en_US&rel=0&autoplay=1"></param><embed src="http://www.youtube.com/v/' + ID + '?fs=1&hl=en_US&rel=0&autoplay=1"' + paramEnd);
                        } else if (contentType == "vimeo") {
                            idIndex = href.indexOf("vimeo.com/") + 10;
                            ID = href.substring(idIndex);
                            content = $(paramStart + 'http://vimeo.com/moogaloop.swf?clip_id=' + ID + '&server=vimeo.com&show_title=0&show_byline=0&show_portrait=0&color=ff9933&fullscreen=1&autoplay=1&loop=0"></param><embed src="http://vimeo.com/moogaloop.swf?clip_id=' + ID + '&server=vimeo.com&show_title=0&show_byline=0&show_portrait=0&color=ff9933&fullscreen=1&autoplay=1&loop=0"' + paramEnd);
                        } else if (contentType == "quicktime") {
                            content = $('<div><object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" codebase="http://www.apple.com/qtactivex/qtplugin.cab" height="' + height + '" width="' + width + '"><param name="src" value="' + href + '"><param name="type" value="video/quicktime"><param name="autoplay" value="true"><embed src="' + href + '" height="' + height + '" width="' + width + '" autoplay="true"  type="video/quicktime" pluginspage="http://www.apple.com/quicktime/download/" scale="tofit"></object></div>');
                        } else if (contentType == "flash") {
                            content = $('<div><embed flashVars="playerVars=autoPlay=yes" src="' + href + '" wmode="transparent" pluginspage="http://www.macromedia.com/go/getflashplayer" allowFullScreen="true" allowScriptAccess="always" width="' + width + '" height="' + height + '" type="application/x-shockwave-flash"></embed></div>');
                        };
                        content.css({
                            "width": width,
                            "height": height
                        });
                        buildit(title, href);
                    }
                    function showImage(href, title) {
                        var img = new Image();
                        content = $(img);
                        content.load(function () {
                            var maxHeight = 0;
                            var maxWidth = 0;
                            if (ob_maxImageHeight > 1) {
                                maxHeight = ob_maxImageHeight;
                            } else if (ob_maxImageHeight > 0) {
                                maxHeight = $(window).height() * ob_maxImageHeight;
                            }
                            if (ob_maxImageWidth > 1) {
                                maxWidth = ob_maxImageWidth;
                            } else if (ob_maxImageWidth > 0) {
                                maxWidth = $(window).width() * ob_maxImageWidth;
                            }
                            var minHeight = parseInt(modalContent.css('min-height'));
                            var minWidth = parseInt(modalContent.css('min-width'));
                            var ratio = 0;
                            width = img.width;
                            height = img.height;
                            if (height > maxHeight) {
                                ratio = maxHeight / height;
                                width = width * ratio;
                                height = maxHeight;
                            }
                            if (width > maxWidth) {
                                ratio = maxWidth / width;
                                height = height * ratio;
                                width = maxWidth;
                            }
                            if (height < minHeight) {
                                var imageTopMargin = (minHeight / 2) - (height / 2);
                                content.css({
                                    "margin-top": imageTopMargin
                                });
                            }
                            if (width < minWidth) {
                                var imageLeftMargin = (minWidth / 2) - (width / 2);
                                content.css({
                                    "margin-left": imageLeftMargin
                                });
                            }
                            content.css({
                                "height": parseInt(height),
                                "width": parseInt(width),
                                "border-width": ob_imageBorderWidth
                            });
                            buildit(title, href);
                        }).error(function () {
                            throwError(2);
                        }).attr({
                            src: href
                        });
                    }
                    var contentType = checkContentType(mainhref);
                    if (contentType === "ob_hidden_set") {
                        mainhref = galleryItems[0];
                        maintitle = titles[0];
                        contentType = checkContentType(mainhref);
                    }
                    if (contentType === "iframe") {
                        showiFrame(mainhref, maintitle);
                    } else if (contentType === "image") {
                        showImage(mainhref, maintitle);
                    } else if (contentType === "inline") {
                        showInline(mainhref, maintitle);
                    } else if (contentType === "quicktime") {
                        showVideo(mainhref, maintitle, contentType);
                    } else if (contentType === "youtube") {
                        showVideo(mainhref, maintitle, contentType);
                    } else if (contentType === "vimeo") {
                        showVideo(mainhref, maintitle, contentType);
                    } else if (contentType === "flash") {
                        showVideo(mainhref, maintitle, contentType);
                    } else {
                        throwError(1);
                    }
                });
            });
        }
    });
})(jQuery);