/**
 *@builder goma resources 1.2.5
 *@license to see license of the files, go to the specified path for the file 
*/

/* File system/form/dropdown.js */

if(self.JSLoadedResources == null) self.JSLoadedResources = [];self.JSLoadedResources["system/form/dropdown.js"] = true;

function DropDown(id,url,multiple){this.url=url;this.multiple=multiple;this.widget=$("#"+id+"_widget");this.input=$("#"+id);this.page=1;this.search="";this.timeout="";this.init();this.id=id;return this;}
DropDown.prototype={init:function(){var that=this;this.widget.disableSelection();this.widget.find(" > .field").css("cursor","pointer");this.widget.find(" > .field").click(function(){that.toggleDropDown();return false;});this.widget.find(" > input").css("display","none");this.widget.find(" > .field").css("margin-top",0);CallonDocumentClick(function(){that.hideDropDown();},[this.widget.find(" > .dropdown"),this.widget.find(" > .field"),this.widget.parent().parent().find(" > label")]);this.widget.find(" > .dropdown > .header > .pagination > span > a").click(function(){if(!$(this).hasClass("disabled")){if($(this).hasClass("left")){if(that.page!=1)
that.page--;}else{that.page++;}
that.reloadData();}});this.widget.find(" > .dropdown > .header > .search").keyup(function(){that.page=1;that.reloadData();});preloadLang(["loading","search","no_result"]);unbindFromFormSubmit(this.widget.find(" > .dropdown > .header > .search"));this.widget.find(" > .dropdown > .header > .cancel").click(function(){that.widget.find(" > .dropdown > .header > .search").val("");that.widget.find(" > .dropdown > .header > .search").keyup();that.widget.find(" > .dropdown > .header > .search").focus();});this.widget.parent().parent().find(" > label").click(function(){that.showDropDown();return false;});},setField:function(content){this.widget.find(" > .field").html(content);},setContent:function(content){this.widget.find(" > .dropdown > .content").html('<div class="animationwrapper">'+content+'</div>');},showDropDown:function(){if(this.widget.find(" > .dropdown").css("display")=="none"){this.widget.find(" > .field").addClass("active");this.widget.find(" > .dropdown").css({top:this.widget.find(" > .field").outerHeight()-2});if(is_mobile||($.browser.msie&&getInternetExplorerVersion()<9)){var fieldhtml=this.widget.find(" > .field").html();this.widget.find(" > .field").html("<img height=\"12\" width=\"12\" src=\"images/16x16/loading.gif\" alt=\"loading\" /> "+lang("loading","loading..."));var $this=this;this.reloadData(function(){$this.widget.find(" > .dropdown").fadeIn(200);$this.widget.find(" > .field").html(fieldhtml);var width=$this.widget.find(" > .field").width()+10;$this.widget.find(" > .dropdown").css({width:width});$this.widget.find(" > .dropdown .search").focus();});}else{var fieldhtml=this.widget.find(" > .field").html();this.widget.find(" > .field").html("<img height=\"12\" width=\"12\" src=\"images/16x16/loading.gif\" alt=\"loading\" /> "+lang("loading","loading..."));var $this=this;gloader.load("jquery.scale.rotate");this.reloadData(function(){$this.widget.find(" > .dropdown").css("display","block");var destheight=$this.widget.find(" > .dropdown").height();$this.widget.find(" > .field").html(fieldhtml);var width=$this.widget.find(" > .field").width()+10;$this.widget.find(" > .dropdown").css({width:width,height:destheight,"opacity":0.4});$this.widget.find(" > .dropdown").scale(0.1);$this.widget.find(" > .dropdown").animate({scale:1.05,opacity:0.9},150,function(){$this.widget.find(" > .dropdown").animate({scale:1,"opacity":0.95},100,function(){$this.widget.find(" > .dropdown").css({height:"","-moz-transform":""});$this.widget.find(" > .dropdown .search").focus();});});});}}},hideDropDown:function(){clearTimeout(this.timeout);this.widget.find(" > .dropdown").fadeOut(200);this.widget.find(" > .field").removeClass("active");},toggleDropDown:function(){if(this.widget.find(" > .dropdown").css("display")=="none"){this.showDropDown();}else{this.hideDropDown();}},reloadData:function(fn){var onfinish=fn;var that=this;var search=this.widget.find(" > .dropdown > .header > .search").val();this.setContent("<div style=\"text-align: center;\"><img src=\"images/16x16/loading.gif\" alt=\"loading\" /> "+lang("loading","loading...")+"</div>");clearTimeout(this.timeout);var that=this;if(search!=""&&search!=lang("search","search...")){this.widget.find(" > .dropdown > .header > .cancel").fadeIn(100);var timeout=200;}else{this.widget.find(" > .dropdown > .header > .cancel").fadeOut(100);var timeout=0;}
var makeAjax=function(){$.ajax({url:that.url+"/getData/"+that.page+"/",type:"post",data:{"search":search},dataType:"json",error:function(){that.setContent("Error! Please try again");if(onfinish!=null){onfinish();}},success:function(data){if(!data||data=="")
that.setContent("No data given, Your Session might be timed out.");if(data.right){that.widget.find(".dropdown > .header > .pagination > span > .right").removeClass("disabled");}else{that.widget.find(".dropdown > .header > .pagination > span > .right").addClass("disabled");}
if(data.left){that.widget.find(".dropdown > .header > .pagination > span > .left").removeClass("disabled");}else{that.widget.find(".dropdown > .header > .pagination > span > .left").addClass("disabled");}
this.value=data.value;var content="";if(data.data){content+="<ul>";i=-1;for(i in data.data){var val=data.data[i];if(typeof val=="object"){smallText=val[1];val=val[0];}
content+="<li>";if(this.value[i]||this.value[i]===0)
content+="<a href=\"javascript:;\" class=\"checked\" id=\"dropdown_"+that.id+"_"+i+"\">"+val+"</a>";else
content+="<a href=\"javascript:;\" id=\"dropdown_"+that.id+"_"+i+"\">"+val+"</a>";if(typeof smallText=="string"){content+="<span class=\"record_info\">"+smallText+"</span>";}
content+="</li>";smallText=null;}
content+="</ul>";if(i==-1)
content='<div class="no_data">'+lang("no_result","There is no data to show.")+'</div>';that.setContent(content);that.bindContentEvents();}
if(onfinish!=null){onfinish();}}});};if(timeout==0)
makeAjax();else
this.timeout=setTimeout(makeAjax,timeout);},bindContentEvents:function(){var that=this;this.widget.find(" > .dropdown > .content ul li a").click(function(){if(that.multiple){if($(this).hasClass("checked")){that.uncheck($(this).attr("id"));}else{that.check($(this).attr("id"));}}else{that.check($(this).attr("id"));}});},check:function(id){var that=this;if(this.multiple){$("#"+id).addClass("checked");var value=id.substring(10+this.id.length);this.widget.find(" > .field").html("<img height=\"12\" width=\"12\" src=\"images/16x16/loading.gif\" alt=\"loading\" /> "+lang("loading","loading..."));$.ajax({url:this.url+"/checkValue/",type:"post",data:{"value":value},error:function(){alert("Failed to check Node. Please check your Internet-Connection");},success:function(html){that.widget.find(" > .field").html(html);}});}else{this.widget.find(" > .dropdown > .content ul li a.checked").removeClass("checked");$("#"+id).addClass("checked");var value=id.substring(10+this.id.length);this.input.val(value);that.widget.find(" > .field").html("<img height=\"12\" width=\"12\" src=\"images/16x16/loading.gif\" alt=\"loading\" /> "+lang("loading","loading..."));$.ajax({url:this.url+"/checkValue/",type:"post",data:{"value":value},error:function(){alert("Failed to check Node. Please check your Internet-Connection");},success:function(html){that.widget.find(" > .field").html(html);that.hideDropDown();that.input.val(value);}});}},uncheck:function(id){var that=this;if(this.multiple){$("#"+id).removeClass("checked");var value=id.substring(10+this.id.length);that.widget.find(" > .field").html("<img height=\"12\" width=\"12\" src=\"images/16x16/loading.gif\" alt=\"loading\" /> "+lang("loading","loading..."));$.ajax({url:this.url+"/uncheckValue/",type:"post",data:{"value":value},error:function(){alert("Failed to uncheck Node. Please check your Internet-Connection");},success:function(html){that.widget.find(" > .field").html(html);}});}}}

/* File system/libs/thirdparty/iphone-checkbox/jquery/iphone-style-checkboxes.js */

self.JSLoadedResources["system/libs/thirdparty/iphone-checkbox/jquery/iphone-style-checkboxes.js"] = true;

(function(){var iOSCheckbox;var __slice=Array.prototype.slice;iOSCheckbox=(function(){function iOSCheckbox(elem,options){var key,opts,value;this.elem=$(elem);opts=$.extend({},iOSCheckbox.defaults,options);for(key in opts){value=opts[key];this[key]=value;}
this.elem.data(this.dataName,this);this.wrapCheckboxWithDivs();this.attachEvents();this.disableTextSelection();if(this.resizeHandle){this.optionallyResize('handle');}
if(this.resizeContainer){this.optionallyResize('container');}
this.initialPosition();}
iOSCheckbox.prototype.isDisabled=function(){return this.elem.is(':disabled');};iOSCheckbox.prototype.wrapCheckboxWithDivs=function(){this.elem.wrap("<div class='"+this.containerClass+"' />");this.container=this.elem.parent();this.offLabel=$("<label class='"+this.labelOffClass+"'>\n  <span>"+this.uncheckedLabel+"</span>\n</label>").appendTo(this.container);this.offSpan=this.offLabel.children('span');this.onLabel=$("<label class='"+this.labelOnClass+"'>\n  <span>"+this.checkedLabel+"</span>\n</label>").appendTo(this.container);this.onSpan=this.onLabel.children('span');return this.handle=$("<div class='"+this.handleClass+"'>\n  <div class='"+this.handleRightClass+"'>\n    <div class='"+this.handleCenterClass+"' />\n  </div>\n</div>").appendTo(this.container);};iOSCheckbox.prototype.disableTextSelection=function(){if($.browser.msie){return $([this.handle,this.offLabel,this.onLabel,this.container]).attr("unselectable","on");}};iOSCheckbox.prototype._getDimension=function(elem,dimension){if($.fn.actual!=null){return elem.actual(dimension);}else{return elem[dimension]();}};iOSCheckbox.prototype.optionallyResize=function(mode){var newWidth,offLabelWidth,onLabelWidth;onLabelWidth=this._getDimension(this.onLabel,"width");offLabelWidth=this._getDimension(this.offLabel,"width");if(mode==="container"){newWidth=onLabelWidth>offLabelWidth?onLabelWidth:offLabelWidth;newWidth+=this._getDimension(this.handle,"width")+this.handleMargin;return this.container.css({width:newWidth});}else{newWidth=onLabelWidth>offLabelWidth?onLabelWidth:offLabelWidth;return this.handle.css({width:newWidth});}};iOSCheckbox.prototype.onMouseDown=function(event){var x;event.preventDefault();if(this.isDisabled()){return;}
x=event.pageX||event.originalEvent.changedTouches[0].pageX;iOSCheckbox.currentlyClicking=this.handle;iOSCheckbox.dragStartPosition=x;return iOSCheckbox.handleLeftOffset=parseInt(this.handle.css('left'),10)||0;};iOSCheckbox.prototype.onDragMove=function(event,x){var newWidth,p;if(iOSCheckbox.currentlyClicking!==this.handle){return;}
p=(x+iOSCheckbox.handleLeftOffset-iOSCheckbox.dragStartPosition)/this.rightSide;if(p<0){p=0;}
if(p>1){p=1;}
newWidth=p*this.rightSide;this.handle.css({left:newWidth});this.onLabel.css({width:newWidth+this.handleRadius});this.offSpan.css({marginRight:-newWidth});return this.onSpan.css({marginLeft:-(1-p)*this.rightSide});};iOSCheckbox.prototype.onDragEnd=function(event,x){var p;if(iOSCheckbox.currentlyClicking!==this.handle){return;}
if(this.isDisabled()){return;}
if(iOSCheckbox.dragging){p=(x-iOSCheckbox.dragStartPosition)/this.rightSide;this.elem.prop('checked',p>=0.5);}else{this.elem.prop('checked',!this.elem.prop('checked'));}
iOSCheckbox.currentlyClicking=null;iOSCheckbox.dragging=null;return this.didChange();};iOSCheckbox.prototype.refresh=function(){return this.didChange();};iOSCheckbox.prototype.didChange=function(){var new_left;if(typeof this.onChange==="function"){this.onChange(this.elem,this.elem.prop('checked'));}
if(this.isDisabled()){this.container.addClass(this.disabledClass);return false;}else{this.container.removeClass(this.disabledClass);}
new_left=this.elem.prop('checked')?this.rightSide:0;this.handle.animate({left:new_left},this.duration);this.onLabel.animate({width:new_left+this.handleRadius},this.duration);this.offSpan.animate({marginRight:-new_left},this.duration);return this.onSpan.animate({marginLeft:new_left-this.rightSide},this.duration);};iOSCheckbox.prototype.attachEvents=function(){var localMouseMove,localMouseUp,self;self=this;localMouseMove=function(event){return self.onGlobalMove.apply(self,arguments);};localMouseUp=function(event){self.onGlobalUp.apply(self,arguments);$(document).unbind('mousemove touchmove',localMouseMove);return $(document).unbind('mouseup touchend',localMouseUp);};this.elem.change(function(){return self.refresh();});return this.container.bind('mousedown touchstart',function(event){self.onMouseDown.apply(self,arguments);$(document).bind('mousemove touchmove',localMouseMove);return $(document).bind('mouseup touchend',localMouseUp);});};iOSCheckbox.prototype.initialPosition=function(){this.offLabel.css("width","");this.handle.css("left","");this.onLabel.css("width","");this.offSpan.css("margin-right","");this.onLabel.css("width","");this.onSpan.css("margin-left","");var containerWidth,offset;containerWidth=this._getDimension(this.container,"width");this.offLabel.css({width:containerWidth-this.containerRadius});offset=this.containerRadius+1;if($.browser.msie&&$.browser.version<7){offset-=3;}
this.rightSide=containerWidth-this._getDimension(this.handle,"width")-offset;if(this.elem.is(':checked')){this.handle.css({left:this.rightSide});this.onLabel.css({width:this.rightSide+this.handleRadius});this.offSpan.css({marginRight:-this.rightSide});}else{this.onLabel.css({width:0});this.onSpan.css({marginLeft:-this.rightSide});}
if(this.isDisabled()){return this.container.addClass(this.disabledClass);}};iOSCheckbox.prototype.onGlobalMove=function(event){var x;if(!(!this.isDisabled()&&iOSCheckbox.currentlyClicking)){return;}
event.preventDefault();x=event.pageX||event.originalEvent.changedTouches[0].pageX;if(!iOSCheckbox.dragging&&(Math.abs(iOSCheckbox.dragStartPosition-x)>this.dragThreshold)){iOSCheckbox.dragging=true;}
return this.onDragMove(event,x);};iOSCheckbox.prototype.onGlobalUp=function(event){var x;if(!iOSCheckbox.currentlyClicking){return;}
event.preventDefault();x=event.pageX||event.originalEvent.changedTouches[0].pageX;this.onDragEnd(event,x);return false;};iOSCheckbox.defaults={duration:200,checkedLabel:'ON',uncheckedLabel:'OFF',resizeHandle:true,resizeContainer:true,disabledClass:'iPhoneCheckDisabled',containerClass:'iPhoneCheckContainer',labelOnClass:'iPhoneCheckLabelOn',labelOffClass:'iPhoneCheckLabelOff',handleClass:'iPhoneCheckHandle',handleCenterClass:'iPhoneCheckHandleCenter',handleRightClass:'iPhoneCheckHandleRight',dragThreshold:5,handleMargin:15,handleRadius:4,containerRadius:5,dataName:"iphoneStyle",onChange:function(){}};return iOSCheckbox;})();$.iphoneStyle=this.iOSCheckbox=iOSCheckbox;$.fn.iphoneStyle=function(){var args,checkbox,dataName,existingControl,method,params,_i,_len,_ref,_ref2,_ref3,_ref4;args=1<=arguments.length?__slice.call(arguments,0):[];dataName=(_ref=(_ref2=args[0])!=null?_ref2.dataName:void 0)!=null?_ref:iOSCheckbox.defaults.dataName;_ref3=this.filter(':checkbox');for(_i=0,_len=_ref3.length;_i<_len;_i++){checkbox=_ref3[_i];existingControl=$(checkbox).data(dataName);if(existingControl!=null){method=args[0],params=2<=args.length?__slice.call(args,1):[];if((_ref4=existingControl[method])!=null){_ref4.apply(existingControl,params);}}else{new iOSCheckbox(checkbox,args[0]);}}
return this;};$.fn.iOSCheckbox=function(options){var opts;if(options==null){options={};}
opts=$.extend({},options,{resizeHandle:false,disabledClass:'iOSCheckDisabled',containerClass:'iOSCheckContainer',labelOnClass:'iOSCheckLabelOn',labelOffClass:'iOSCheckLabelOff',handleClass:'iOSCheckHandle',handleCenterClass:'iOSCheckHandleCenter',handleRightClass:'iOSCheckHandleRight',dataName:'iOSCheckbox'});return this.iphoneStyle(opts);};}).call(this);

