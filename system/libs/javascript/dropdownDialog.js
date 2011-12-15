/**
  *@package goma framework
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2011  Goma-Team
  * last modified: 06.11..2011
*/

self.dropdownDialogs = [];
// put the data into the right namespace
(function($){
	var counter = 0;
	var elems = [];
	var dropdowns = [];
	
	/**
	 * the constructor
	*/ 
	window.dropdownDialog = function(uri, elem, position, options) {
		
		this.elem = $(elem);
		if(this.elem.length == 0)
			return false;
		
		this.setPosition(position);
		this.uri = uri;
		this.triangle_position = "center";
		this.closeButton = true;
		this.html = "";
		
		var i;
		// set options
		if(typeof options != "undefined") {
			for(i in options) {
				this[i] = options[i];
			}
		}
			

		this.id = randomString(20);
		this.Init();
		
		this.show(uri);
		
		
		return this;
	};
	
	dropdownDialog.prototype = {
		checkEdit: function() {

			if(this.dropdown.find("> div > .content > div").html() != this.html) {
				this.html = this.dropdown.find("> div > .content > div").html();
				this.definePosition(this.position);
			}
		},
		Init: function() {
			
			// first validate id
			if(this.elem.attr("id") == undefined) {
				this.elem.attr("id", "link_dropdown_dialog_" + counter);
				counter++;
			}
			
			// second check if an dialog for this element doesnt exist, and if not, create the element
			if($("#dropdownDialog_" + this.elem.attr("id")).length == 0) {
				$("body").append('<div id="dropdownDialog_'+this.elem.attr("id")+'" class="dropdownDialog"></div>');
				this.dropdown = $("#dropdownDialog_" + this.elem.attr("id"));
				this.dropdown.append('<div><div class="content"></div></div>');
				this.dropdown.css({
					display: "none",
					"position": "absolute"
				});
				var loading = true;
			} else {
				this.dropdown = $("#dropdownDialog_" + this.elem.attr("id"));
				var loading = false;
			}
			
			
			dropdowns[this.dropdown.attr("id")] = this;
			elems[this.elem.attr("id")] = this;
			self.dropdownDialogs[this.id] = this;
			
			// autohide
			if(!this.elem.hasClass("noAutoHide") && this.autohide != false) {
				var that = this;
				CallonDocumentClick(function(){
					that.hide();
				}, [this.dropdown, this.elem]);
			}
			
			if(this.elem.hasClass("noIEAjax")) {
				if($.browser.msie && getInternetExplorerVersion() < 9) {
					location.href = this.uri;
				}
			}
			
			
			
			if(loading)
				this.setLoading();
		},
		/**
		 * defines the position of the dropdown
		 *
		 *@name definePosition
		 *@access public
		 *@param string - position: if to set this.position
		*/
		definePosition: function(position) {
			if(position != null) {
	 			this.setPosition(position);
			}
			
			if(this.elem.css("position") == "fixed") {
				throw "dropdownDialog does not support elements with position:fixed yet";
				return false;
			}
			if(this.elem.css("display") == "none") {
				throw "dropdownDialog does not support elements with display:none yet";
				return false;
			}
			
			// get position which is logical
			var elemtop = this.elem.offset().top;
			var elemleft = this.elem.offset().left;
			var elemheight = this.elem.outerHeight();
			var elemwidth = this.elem.outerWidth();
			var elemright = $(document).width() - elemleft;
			
			if(this.position == "auto") {
				if(elemleft < 100 && elemtop > 100) {
					position = "right";
				} else if(elemright < 100 && elemtop > 100) {
					position = "left";
				} else {
					if(elemtop > ($(window).height() * 0.7)) {
						position = "bottom";
					} else {
						position = "top";
					}
				}
			} else {
				position = this.position;
			}
			
			// add position as class
			this.dropdown.find(" > div").attr("class", "");
			this.dropdown.find(" > div").addClass("position_" + position);
			
			// now move dropdown
			this.moveDropdown(position);
			
		},
		/**
		 * validates and sets the position
		*/
		setPosition: function(position) {
 			switch(position) {
 				case "left":
 					this.position = "left";
 					break;
 				case "center":
 				case "top":
 					this.position = "top";
 					break;
 				case "right":
 					this.position = "right";
 					break;
 				case "bottom":
 					this.position = "bottom";
 					break;
 				default:
 					this.position = "auto";
 			}
	 		
		},
		/**
		 * moves the dropdown to the right place cause of position
		*/ 
		moveDropdown: function(position) {
			
			// first get position of element
			var elemtop = this.elem.offset().top;
			var elemleft = this.elem.offset().left;
			
			var elemheight = this.elem.outerHeight();
			var elemwidth = this.elem.outerWidth();

			
			this.dropdown.find(" > div > img").remove();
			
			// preserve display
			var display = (this.dropdown.css("display") == "block");
			this.dropdown.css({"display": "block", top: "-1000px"});
			
			switch(position) {
				case "bottom":
				case "top":
				case "center":
					
					var positionTop = elemtop + elemheight - 2;
					var positionLeft = elemleft - (this.dropdown.find(" > div > .content").width() / 2) + (elemwidth / 2) - 3;
					var contentwidth = this.dropdown.find(" > div > .content").outerWidth();
					this.dropdown.find(" > div > .content").css("width", this.dropdown.find(" > div > .content").width()); // force width
					this.dropdown.css("display", "none");
					
					// check if this is logical
					if(contentwidth + positionLeft > $(document).width()) {
						this.triangle_position = "right";
						var positionLeft = elemleft + elemwidth - contentwidth;
					}
					if(positionLeft < 0) {
						this.triangle_position = "left";
						var positionLeft = elemleft - 10;
					}
						
					this.dropdown.css({
						top: positionTop,
						left: positionLeft,
						right: "auto",
						bottom: "auto"
					});
				break;
				case "left":
					var positionTop = elemtop - (this.dropdown.find(" > div > .content").height() / 2) + (elemheight / 2);
					var contentWidth = this.dropdown.find(" > div > .content").outerWidth();
					this.dropdown.find(" > div > .content").css("width", this.dropdown.find(" > div > .content").width()); // force width
					var positionRight = elemleft + 2 - contentWidth;
					
					this.dropdown.css({
						display: "none",
						top: positionTop,
						left: positionRight,
						right: "auto",
						bottom: "auto"
					});
				break;
				case "right":
					var positionTop = elemtop - (this.dropdown.find(" > div > .content").height() / 2) + (elemheight / 2);
					var positionLeft = elemleft + elemwidth - 2;
					this.dropdown.css({
						"display": "none",
						top: positionTop,
						left: positionLeft,
						right: "auto",
						bottom: "auto"
					});
				break;
			}
			
			// now set the triangle
			this.dropdown.find(" > div").prepend('<img class="position_'+this.triangle_position+'" src="system/templates/images/dropdownDialog/triangle_white_'+position+'.png" alt="" />');
			if(display)
				this.dropdown.css("display", "block");
			else
				this.dropdown.fadeIn("fast");
		},
		/**
		 * sets the dropdown in loading state
		*/ 
		setLoading: function() {
			this.dropdown.css("display", "block");
			this.closeButton = false;
			this.setContent('<img src="system/templates/css/images/loading_big.gif" alt="loading" style="display: block;margin: auto;" />');
			this.closeButton = true;
		},
		/**
		 * sets the content
		 *
		 *@name setContent
		 *@access public
		*/ 
		setContent: function(content) {
			this.dropdown.find(" > div > .content").css("width", ""); // unlock width
			// check if string or jquery object
			if(typeof content == "string")
				this.dropdown.find(" > div > .content").html('<div>' + content + '</div>');
			else {
				this.dropdown.find(" > div > .content").html('');
				$(content).wrap("<div></div>").appendTo(this.dropdown.find(" > div > .content"));
			}
			// close-button
			this.dropdown.find(" > div  > .content > .close").remove();
			if(!this.elem.hasClass("hideClose") && this.closeButton)
				this.dropdown.find(" > div > .content > div").prepend('<a class="close" href="javascript:;"></a>');
			
			// closing over elements in dropdown
			var that = this;
			this.dropdown.find(".close, *[name=cancel]").click(function(){
				that.hide();
				return false;
			});
			
			// if is shown also now, we we'll move it to the right position
			if(this.dropdown.css("display") != "none")
				this.definePosition(this.position);

		},
		/**
		 * shows a specific uri in the dropdown
		*/
		show: function(uri) {
			if(this.dropdown.css("display") == "block" && this.dropdown.attr("name") == uri) {
				return false;
			}
			this.setLoading();
			this.dropdown.attr("name", uri);
			var i;
			for(i in this.players) {
				if(this.players[i].regexp.test(this.uri)) {
					return this.players[i].method(this, this.uri);
				}
			}
			
			return this.player_ajax(this.uri);
		},
		removeHelper: function() {
			this.dropdown.remove();
		},
		remove: function() {
			dropdowns[this.dropdown.attr("id")] = null;
			elems[this.elem.attr("id")] = null;
			self.dropdownDialogs[this.id] = null;
			var that = this;
			this.dropdown.fadeOut("fast", function(){
				that.removeHelper();
			});
		},
		hide: function() {
			this.remove(); // better solution
		},
		player_html: function(uri) {
			this.setContent($(uri));
		},
		player_img: function(uri) {
			var href = uri;
			var preloader = new Image();
			var that = this;
			preloader.onload = function(){
				var height = preloader.height;
				var width = preloader.width;
				var sv = width / height;
				var dheight = $(window).height() - 300;
				var dwidth = $(window).width() - 400;
				if(height > dheight ){
					var height = dheight;
					var width = height * sv;
				}
				that.setContent('<img src="'+href+'" alt="'+href+'" height="'+height+'" width="'+width+'" />');
			}
			preloader.onerror = function(){
				that.setContent('<h3>Connection error!</h3> <br /> Please try again later!');
			}
			preloader.src = href;
		},
		player_ajax: function(uri) {
			var that = this;
			if(uri.indexOf("?") == -1) {
				uri += "?dropdownDialog=1&dropElem=" + this.id;
			} else {
				uri += "&dropdownDialog=1&dropElem=" + this.id;
			}
			$.ajax({
				url: uri,
				type: "get",
				error: function(jqXHR, textStatus, errorThrown) {
					if(textStatus == "timeout") {
						that.setContent('Error when fetching data from the server: <br /> The response timed out.');
					} else if(textStatus == "abort") {
						that.setContent('Error when fetching data from the server: <br /> The request was aborted.');
					} else {
						that.setContent('Error when fetching data from the server: <br /> Failed to fetch data from the server.');
					}
				},
				dataType: "html",
				success: function(html, textStatus, jqXHR) {
					
					LoadAjaxResources(jqXHR);
					var content_type = jqXHR.getResponseHeader("content-type");
					if(content_type == "text/x-json") {
						var data = eval_global('('+html+')');
						var html = data.content;
						if(data.position != null) {
							that.position = data.position;
						}
						if(data.closeButton != null) {
							that.closeButton = data.closeButton;
						}
						
						if(typeof data.exec != "undefined") {
							eval_global('(' + data.exec + ')').call(that);
						}
					} else if(content_type == "text/javascript") {
						var method = eval_global('(function() { ' + html + '})');
						method.call(this);
						
					}
					that.setContent(html);
					RunAjaxResources(jqXHR);
				}
			});
		},
		players: [
			{
				"regexp": /^#/,
				"method": function(obj, uri) {
					obj.player_html(uri);
				}
			},
			{
				"regexp": /^.*\.(img|png|jpg|gif|bmp)$/i,
				"method": function(obj,uri) {
					obj.player_img(uri);
				}
			}
		]
	};
	
	dropdownDialog.get = function(elem) {
		if(typeof dropdowns[elem] != "undefined") {
			return dropdowns[elem];
		} else if(typeof elems[elem] != "undefined") {
			return elems[elem];
		} else if(typeof self.dropdownDialogs[elem] != "undefined") {
			return self.dropdownDialogs[elem];
		} else {
			return false;
		}
	};
	
	// jQuery-Extension
	$.fn.extend({ 
        dropdownDialog: function(options) {
        	if(typeof options == "string")
        		options = {uri: options};
        	
        	var defaults = {
        		"uri": "",
        		"position": null
        	};
        	var o = $.extend(defaults, options);
        	
        	var that = this;
        	var obj = {
        		instances: [],
        		hide: function() {
        			for(i in obj.instances) {
        				obj.instances[i].hide();
        			}
        		},
        		remove: function() {
        			for(i in obj.instances) {
        				obj.instances[i].remove();
        			}
        		}
        	}
        	this.each(function(){
				var instance = new dropdownDialog(o.uri, this, o.position);
				obj.instances.push(instance);
			});
			
			return obj;
			
        }
    });
    
})(jQuery);