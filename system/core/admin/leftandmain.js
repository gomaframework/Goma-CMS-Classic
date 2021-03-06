/**
 * JavaScript for the simple two column admin-panel.
 *
 * @package     Goma\Admin\LeftAndMain
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     2.2.9
 */


var LaM_current_text = "";
var LaM_type_timeout;

(function($, w){
	$(function(){

		var oForm = $("#tree-options-form");

		// first modify out view-controller in javascript.
		goma.ui.setMainContent($("table.leftandmaintable td.main > .inner"));

		// make it visible.
		$(".leftandmaintable").css("display", "");

		// add flex-boxes
		goma.ui.addFlexBox($(".leftandmaintable .LaM_tabs .treewrapper"));
		goma.ui.addFlexBox(".left-and-main > table td.main > .inner > form > .fields");
		goma.ui.addFlexBox(".leftandmaintable .main .inner");

        var searchInput = $(".treesearch form input[type=text]");

		//! searchfield bindings
		$(".treesearch form").submit(function(){
			updateWithSearch($(this));
			return false;
		});

		searchInput.change(function(){
			updateWithSearch($(this).parent());
			return false;
		});

		oForm.submit(function(){
			updateWithSearch($(".treesearch form"), null, true);
			return false;
		});

		oForm.find("input,select,textarea").change(function(){
			oForm.submit();
		});

		setTimeout(updateSidebarToggle, 50);

		//! leftbar
		$(document).on("click touchend", ".leftbar_toggle", function(){
			if($(this).hasClass("active")) {
				$(this).removeClass("active");
				$(".leftandmaintable .left").removeClass("active");

				$(".leftandmaintable").removeClass("left-active");
			} else {
				$(this).addClass("active");
				$(".leftandmaintable .left").addClass("active");

				$(".leftandmaintable").addClass("left-active");
			}
			goma.ui.updateFlexBoxes();
			return false;
		});

		setTimeout(function(){
			if(!$(".leftbar_toggle").hasClass("active")) {
				$(".leftbar_toggle, .leftandmaintable .left").removeClass("active");
				$(".leftandmaintable").removeClass("left-active");
			} else {
				$(".leftandmaintable .left").addClass("active");
				$(".leftandmaintable").addClass("left-active");
			}
		}, 100);

		// show progress in tree
		goma.ui.onProgress(function(percent, slow) {
			if($(".leftandmaintable .LaM_tabs .treewrapper .loading").length == 1) {
				var item = $(".leftandmaintable .LaM_tabs .treewrapper .loading").parent().eq(0);
				if(item.find(".loadingBar").length == 0) {
					item.append('<div class="loadingBar"></div>');
					item.find(".loadingBar").css({
						position: "absolute",
						left: item.position().left,
						top: item.position().top + item.outerHeight() - 2,
						height: "2px"
					});
				}

				var maxWidth = item.outerWidth();

				var slow = (typeof slow == "undefined") ? false : slow;

				var duration = (slow && percent != 100) ? 5000 : 500;

				goma.ui.progress = percent;
				item.find(".loadingBar").stop().css({opacity: 1}).animate({
					width: percent / 100 * maxWidth
				}, {
					duration: duration,
					queue: false
				});

				if(percent == 100) {
					item.find(".loadingBar").animate({
						opacity: 0
					}, {
						duration: 1000,
						queue: false,
						complete: function(){
							item.find(".loadingBar").remove();
						}
					});
				}
			}
		});

		var scrollTimeout,
			optimizeScroll = function() {
				clearTimeout(scrollTimeout);
				scrollTimeout = setTimeout(function(){

					var treewrapper = $(".leftandmaintable .LaM_tabs .treewrapper"),
						scroll = treewrapper.scrollTop();
					// find optimal scroll by position of active element
					if(treewrapper.find(".marked").length == 1) {
						var pos = treewrapper.find(".marked:first").offset().top + treewrapper.find(".marked:first > span.tree-wrapper").outerHeight() - treewrapper.offset().top + scroll;

						if(treewrapper.scrollTop() > pos) {
							treewrapper.scrollTop(pos);
						} else if(treewrapper.scrollTop() + treewrapper.height() < pos) {
							treewrapper.scrollTop(pos - treewrapper.height() / 5);
						}
					}
				}, 100);
			};

		//! history
		if(getInternetExplorerVersion() > 7 || getInternetExplorerVersion() == -1) {
			goma.ui.loadAsync("history").done(function(){
				HistoryLib.bind(function(url){
					if($(".treewrapper a[href='"+url+"']").length > 0) {
						var $this = $(".treewrapper a[href='"+url+"']");
						$this.addClass("loading");
					}

					goma.ui.ajax(undefined, {
						url: url,
						data: {"ajaxfy": true}
					}).done(function(html, node, request) {
						$("#content .success, #content .error, #content .notice").hide("fast");
						$(".left-and-main .LaM_tabs > div.create ul li.active").removeClass("active");

						if(typeof $this != "undefined") {
							$this.removeClass("loading");
						}

						updateSidebarToggle();
					});

				});

				HistoryLib.bind(optimizeScroll, true);
			});
		}

		optimizeScroll();

		//! tree-events
		searchInput.keyup(function(){
			self.LaM_current_text = $(this).val();
			clearTimeout(self.LaM_type_timeout);
			self.LaM_type_timeout = setTimeout(function(){
				if(self.LaM_current_text == searchInput.val()) {
					updateWithSearch($(".treesearch form"),null, null, true);
				}
			}, 400);

			// legend-fade
			if(searchInput.val() == "") {
				$(".legend").stop().fadeTo(300, 1);
			} else {
				$(".legend").stop().fadeTo(300, 0.4);
			}
		});

		// bindings
		setTimeout(function(){
			// bind now!
			tree_bind_ajax(sort, $(".left div.tree ul"));
		}, 150);


		//! sort
		var sort = (searchInput.val() == "" || searchInput.val() == lang("search", "Search..."));

		//! legend
		$(".legend").find(":checkbox").each(function(){
			if(!$(this).prop("disabled")) {
				$(this).click(function(){
					reloadTree();
				});
			}
		});
	});

	w.reloadTree = function(fn, openid) {
		$(".treesearch form input[type=text]").val("");
		updateWithSearch($(".treesearch form"), fn, true, undefined, openid);
	};

	var active_val = "";
	function updateWithSearch($this, callback, force, notblur, openid) {
		var oForm = $("#tree-options-form");

		var fn = callback;
		var value = $this.find("input[type=text]").val();
		if(value == lang("search", "Search...")) {
			value = "";
		}

		if(force != null || value != active_val) {
			active_val = value;
		} else {
			return false;
		}

		if(value != "") {
			$this.find("input[type=text]").addClass("active");
		} else {
			$this.find("input[type=text]").removeClass("active");
		}
		if(notblur == null) {
			$this.find("input[type=text]").blur();
		}

		$this.parents(".classtree").find(".treewrapper").html("&nbsp;<img src=\"system/images/16x16/ajax-loader.gif\" alt=\"\" /> Loading...");
		var treewrapper = $this.parents(".classtree").find(".treewrapper");
		// if no search
		if(value == "") {
			var params;
			if(typeof openid != "undefined")
				params = "?edit_id=" + escape(openid);
			else
				params = "";



			$.ajax({
				url: BASE_SCRIPT + adminURI + "/updateTree/" + params,
				type: "post",
				data: oForm.serialize(),
				success: function(html, code, jqXHR) {
					renderResponseTo(html, treewrapper, jqXHR, undefined, false).done(function(){
						tree_bind(treewrapper.find(".goma-tree"));
						tree_bind_ajax(true, $(".left ul.goma-tree"));


						if(fn != null) {
							fn();
						}
					});
				}
			});

			$(".legend").stop().fadeTo(300, 1);
		} else {

			// if search
			$.ajax({
				url: BASE_SCRIPT + adminURI + "/updateTree/" + escape(value),
				type: "post",
				data: oForm.serialize(),
				success: function(html, code, jqXHR) {
					renderResponseTo(html, $this.parents(".classtree").find(".treewrapper"), jqXHR).done(function(){
						tree_bind($this.parents(".classtree").find(".treewrapper").find(".goma-tree"));
						tree_bind_ajax(false, $(".left ul.goma-tree"));

						if(fn != null) {
							fn();
						}
					});
				}
			});
		}
	}

	function tree_bind_ajax(sortable, node) {
		var oForm = $("#tree-options-form");

		// bind events to the nodes to load the content then
		node.find("a.node-area").click(function(){
			if($(this).parent().parent().hasClass("marked")) {
				// it is loaded already
				$("td.left").removeClass("active");
				$("table.leftandmaintable").removeClass("left-active");

				$(".leftbar_toggle").removeClass("active");

				goma.ui.updateFlexBoxes();

				return false;
			} else
			if($(this).parent().parent().attr("data-nodeid") != 0) {
				// no ajax in IE
				if(getInternetExplorerVersion() <= 7 && getInternetExplorerVersion() != -1) {
					return true;
				}

				LoadTreeItem($(this).parent().parent().attr("data-nodeid"));
			}
			return false;
		});

		var sortXhr;

		// bind the sort
		if(sortable && self.LaMsort) {
			gloader.loadAsync("sortable").done(function(){
				node.parent().find("ul").each(function(){
					var s = this;
					$(this).find( " > li " ).css("cursor", "move");
					$(this).sortable({
						helper: 'clone',
						items: ' > li:not(.action)',
						cursor: "move",
						axis: "y",
						cancel: " > li.action",
						update: function(event, ui)
						{
							var sortSerialized = $(s).sortable('serialize', {key: "treenode[]", attribute: "data-recordid", expression: /(.+)/});
                            if(sortXhr != null) {
                                sortXhr.abort();
                            }
                            var update = new Date().getTime();
                            sortXhr = $.ajax({
								url: BASE_SCRIPT + adminURI + "/savesort/?t=" + update,
								data: sortSerialized + "&" + oForm.serialize(),
								type: 'post',
								error: function(e)
								{
									if(e.statusText != "abort" && e.readyState > 0) {
										alert(e);
									}
								},
								success: function(html, code, jqXHR)
								{
									renderResponseTo(html, $(".left .treewrapper"), jqXHR).done(function(){;
										tree_bind($(".left .treewrapper").find(".goma-tree"));
										tree_bind_ajax(true, $(".left ul.goma-tree"));
									});
								}
							});
						},
						tolerance: 'pointer'
					});
				});
			});
		}

	}

	// bind the treeupdate, when the tree was updated
	$(function(){
		$(".left .treewrapper").bind("treeupdate", function(event, node){
			tree_bind_ajax(true, node, false);
		});
	});

	var updateSidebarToggle = function() {
		if($("table.leftandmaintable td.main > .inner .leftbar_toggle").length > 0) {
			$("table.leftandmaintable td.main > .leftbar_toggle").css("display", "none");
		} else {
			$("table.leftandmaintable td.main > .leftbar_toggle").css("display", "");
		}

		goma.ui.updateFlexBoxes();
	};

	// function to load content of a tree-item
	w.LoadTreeItem = function (id) {
		var $this = $("li[data-nodeid="+id+"] > span > a.node-area");
		if($this.length == 0 && $("li[data-recordid="+id+"] > span > a.node-area").length == 0) {
			return;
		}

		if($this.length == 0) {
			$this = $("li[data-recordid="+id+"] > span > a.node-area");
		}

		// Internet Explorer seems not to work correctly with Ajax, maybe we'll fix it later on, but until then, we will just load the whole page ;)
		if(getInternetExplorerVersion() <= 7 && getInternetExplorerVersion() != -1) {
			$this.click();
		}

		$this.addClass("loading");

		setTimeout(function(){
			goma.ui.ajax(undefined, {
				beforeSend: function() {
					if(typeof HistoryLib.push === "function") {
                        HistoryLib.push($this.attr("href"));
                    }
				},
				url: $this.attr("href"),
				data: {"ajaxfy": true}
			}).done(function(){
				if(id == 0) {
					$("td.left").addClass("active");
					$("table.leftandmaintable").addClass("left-active");
				} else {
					$("td.left").removeClass("active");
					$("table.leftandmaintable").removeClass("left-active");
				}

				$("#content .success, #content .error, #content .notice").hide("fast");
				$this.removeClass("loading");

				$(".treewrapper .loadingBar").remove();

				updateSidebarToggle();
			});
		}, 100);
	};

    goma.ui.onProgress(function(percent){
        if(percent === 100) {
            setTimeout(function(){
                $("td.left").removeClass("active");
                $("table.leftandmaintable").removeClass("left-active");
            }, 100);
        }
    });
})(jQuery, window);
