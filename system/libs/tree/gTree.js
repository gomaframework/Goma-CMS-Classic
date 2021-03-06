/**
 * @package		Goma\Tree-Lib
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version		1.0.1
 */
function tree_bind(tree) {
	$.contextMenu('html5');
	
	var node = tree;
	tree = null;
	node.find(".goma-tree").removeClass("goma-tree");
	node.find(".hitarea").unbind("click");
	node.find(".hitarea").click(function(){
		var hitarea = $(this);
		var li = hitarea.parent();
		var link = hitarea.find("a");
		
		if(link.length == 1 && link.attr("href") != "" && li.find("ul").length == 0) {
			li.append("<ul class='loading'><li class=\"load\"><span class=\"tree_wrapper node-area\"><img src=\"system/images/16x16/loading.gif\" alt=\"\" /> Loading...</span></li></ul>");
			
			li.removeClass("collapsed").addClass("expanded");
			hitarea.removeClass("collapsed").addClass("expanded");
			setCookie(hitarea.attr("data-cookie"), 1);
			
			// get data via ajax
			$.ajax({
				url: link.attr("href")
			}).done(function(html) {
				li.find(" > ul").removeClass("loading").slideUp(0);
				li.find(" > ul").html(html);
				li.find(" > ul").slideDown("fast");
				
				tree_bind(li.find(" > ul"));
				
				node.trigger("treeupdate", [li]);
			});
		}  else if($(this).hasClass("expanded")) {
			li.find(" > ul").slideUp("fast");
			li.addClass("collapsed").removeClass("expanded");
			hitarea.addClass("collapsed").removeClass("expanded");
			
			setCookie(hitarea.attr("data-cookie"), 0);
		}  else if($(this).hasClass("collapsed")) {
			li.find(" > ul").slideDown("fast");
			li.removeClass("collapsed").addClass("expanded");
			hitarea.removeClass("collapsed").addClass("expanded");
			
			setCookie(hitarea.attr("data-cookie"), 1);
		}
				
		return false;
	});
	
	var manageMarkByUrl = function(url){
		node.find("li a").each(function(){
			var a = $(this);
			var li = a.parent().parent();
			var path = a.attr("href");
			if(path.length > url.length) {
				if(path.substr(path.length - url.length) == url) {
					li.addClass("marked");
				} else {
					li.removeClass("marked");
				}
			} else if(path.length < url.length) {
				if(url.substr(url.length - path.length) == path) {
					li.addClass("marked");
				} else {
					li.removeClass("marked");
				}
			} else {
				if(url == path) {
					li.addClass("marked");
				} else {
					li.removeClass("marked");
				}
			}
		});
	};
	
	gloader.loadAsync("history").done(function(){
		HistoryLib.bind(manageMarkByUrl, true);
	});

	manageMarkByUrl(location.pathname);
}


$(function(){
	$(".goma-tree").each(function(){
		if($(this).hasClass("goma-tree"))
			tree_bind($(this));
	});
});
