/**
  * extends the CKEditor-Link-Dialog with a new Link-Type
  *
  *@package goma framework
  *@link http://goma-cms.org
  *@license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
  *@author Goma-Team
  * last modified: 12.12.2012
  * $Version 1.0.3
*/
$(function(){
	CKEDITOR.on( 'dialogDefinition', function( ev )
	{
		// Take the dialog name and its definition from the event data.
		var dialogName = ev.data.name;
		var dialogDefinition = ev.data.definition;
		var editor = ev.editor;
 		
 		var url = "";

		var updatePositionOfElements = function(dialogUiText, inputElement) {
			dialogUiText.find(" > .textDropDown").css({
				left: (inputElement.outerWidth(true) - inputElement.outerWidth()) / 2,
				top: inputElement.outerHeight() + 16,
				width: inputElement.outerWidth() + 1 - (inputElement.outerWidth(true) - inputElement.outerWidth()) / 2
			});
		};

		// Check if the definition is from the dialog we're
		// interested on (the Link dialog).
		if ( dialogName == 'link' ) {
		
			// rebuild event for change
			
			var linkTypeField = dialogDefinition.getContents("info").get("linkType");
			linkTypeField.items.push([self.lang("page"), 'page']);
			dialogDefinition.getContents("info").add({
				id: 	"pageOptions",
				type : 'hbox',
				children :
				[
					{
						type : 'text',
						id : 'pagename',
						commit: function(data) {
							if ( !data.url )
								data.url = {};
							var dialog = this.getDialog();
							if(data.type == "page") {
								data.type = "url";
								dialog.setValueOf("info", "protocol", "");
								dialog.setValueOf("info", "url", url);
							}
						},
						
						onLoad: function() {
							var timeout;
							var $edit = this;
							var inputElement = $("#" + $edit.getInputElement().getId() );
							var dialogUiText = inputElement.parents('.cke_dialog_ui_text');

							inputElement.attr("placeholder", lang("search"));

							dialogUiText.addClass("pageLinkHolder");
							dialogUiText.css('position', 'relative');
							dialogUiText.append('<a href="javascript:;" class="cancelButton"></a>');
							dialogUiText.append('<div class="textDropDown" style="display: none;"><ul></ul></div>');
							updatePositionOfElements(dialogUiText, inputElement);

							dialogUiText.find(".cancelButton").click(function(){
								url = "";
								inputElement.prop("disabled", false);
								inputElement.val("");
								inputElement.focus();
								inputElement.parents('.cke_dialog_ui_text').find(" > .textDropDown").css("display", "block");
								inputElement.keydown();
							});
							
							if(inputElement.val() == "") {
								inputElement.prop("disabled", false);
							}

							inputElement.keydown(function(){
								if(inputElement.val() == "") {
									inputElement.parents('.cke_dialog_ui_text').find(".cancelButton").css("display", "none");
									inputElement.prop("disabled", false);
								} else {
									$("#" + $edit.getInputElement().getId() ).parents('.cke_dialog_ui_text').find(".cancelButton").css("display", "block");
								}
								clearTimeout(timeout);
								timeout = setTimeout(function(){
									$.ajax({
										url: "api/pagelinks/search",
										type: "post",
										data: {search: $("#" + $edit.getInputElement().getId() ).val()},
										dataType: "html",
										success: function(data) {
											try {
												var parsedData = parseJSON(data);
												if (parsedData.count > 0) {
													var ul = dialogUiText.find(" > .textDropDown > ul");
													ul.html("");
													var i;
													for (i in parsedData.nodes) {
														var record = parsedData.nodes[i];
														ul.append('<li><a href="' + record.url + '" class="pagenode_' + record.id + '">' + record.title + '</a></li>');
														$('.pagenode_' + record.id).click(function () {
															url = $(this).attr("href");
															dialogUiText.find(" > .textDropDown").css("display", "none");
															inputElement.val($(this).text());
															inputElement.prop("disabled", "disabled");
															dialogUiText.find(".cancelButton").css("display", "block");
															return false;
														});
													}
													dialogUiText.find(" > .textDropDown").css("display", "block");
													updatePositionOfElements(dialogUiText, inputElement);
												} else {
													dialogUiText.find(" > .textDropDown").css("display", "none");
												}
											} catch(e) {
												alert(e);
												console.error(e);
											}
										}
									});
								}, 300);
							});
							
							$("#" + this.getInputElement().getId() ).keydown();
						}
					}
				]
			});

			var content = dialogDefinition.contents[0].elements[1];
			content.onChange = CKEDITOR.tools.override(content.onChange, function(original) {
				return function() {
					var dialog = this.getDialog();
					uploadTab = dialog.definition.getContents( 'upload' ),
					uploadInitiallyHidden = uploadTab && uploadTab.hidden;
					
					original.call(this);
					var element = dialog.getContentElement( 'info',"pageOptions" ).getElement().getParent().getParent();
					if(this.getValue() == "page") {
						element.show();
					 	if (editor.config.linkShowTargetTab) {
              		 	 	dialog.showPage('target');
             		 	}
             		 	dialog.hidePage( 'advanced' );
             		 	
             		 	if ( !uploadInitiallyHidden )
							dialog.showPage( 'upload' );
							
						$("#" + dialog.getContentElement("info", "pageOptions").getElement().getId()).find(".cke_dialog_ui_input_text").keydown();
					} else {
              			element.hide();
              			if(editor.config.linkShowAdvancedTab)
              				dialog.showPage( 'advanced' );
              			
              			if ( !uploadInitiallyHidden )
							dialog.showPage( 'upload' );
            		}
					
					dialog.layout();
				}
			});
		}
	});
});