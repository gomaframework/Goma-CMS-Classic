/**
  *@package goma
  *@link http://goma-cms.org
  *@license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
  *@author Goma-Team
  * last modified: 05.06.2011
*/
(function($){
	$.fn.gtabs = function(options) {
		var defaults = {
			tabelement: " > ul a, > ul input, > ul button",
			activeClass: "active",
			contentelement: "> div, > .tab-wrapper > div",
			tabidentifier: "id",
			removeSuffix: true,
			ajaxSupport: true
		};

		var info = options ? $.extend(defaults, options) : defaults;

		this.each(function(){
			var tabs = $(this);
			tabs.find(info.tabelement).click(function(){
				var oldtab = tabs.find(info.contentelement).filter("." + info.activeClass);
				var tabFinder = $(this).attr(info.tabidentifier);
				if(info.removeSuffix) {
					tabFinder = tabFinder.substring(0, tabFinder.lastIndexOf("_"));
				}

				var newtab = $("#" + tabFinder);
				
				oldtab.removeClass(info.activeClass);
				oldtab.css("height", "");
				
				tabs.find(info.tabelement).filter("." + info.activeClass).removeClass(info.activeClass);
				$(this).addClass(info.activeClass);
				newtab.addClass(info.activeClass);
				
				if($(this).hasClass("ajax") && info.ajaxSupport) {
					var tabElement = $(this);
					$.ajax({
						url: $(this).attr("href"),
						dataType: "json",
						success: function(obj) {
							if(typeof obj != "object") {
								newtab.html(obj);
							} else {
								newtab.html(obj.content);
								tabElement.html(obj.title);
							}
						}
					});
				}
				
				return false;
			});

			if(tabs.find(info.tabelement).filter("." + info.activeClass).length == 0) {
				tabs.find(info.tabelement).eq(0).click();
			}
		});
	}
})(jQuery);

var checkForTabExistence = function() {
	$("gtabs:not(.gtabs-rendered)").each(function(){
		$(this).addClass("gtabs-rendered");
		$(this).gtabs({
			tabelement: " > ul > li",
			activeClass: "active",
			contentelement: "> div, > .tab-wrapper > div",
			tabidentifier: "for",
			removeSuffix: false,
			ajaxSupport: false
		});
	});
};

document.addEventListener && document.addEventListener("DOMContentLoaded", checkForTabExistence, false);
$(function(){
	checkForTabExistence();
});
