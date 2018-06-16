/**
 * JS for resizable Textarea.
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package Goma\Form
 * @version 1.0
 */
var resizableTextarea = function(id) {
    this.text = document.getElementById(id);

    this.init();

    return this;
};

resizableTextarea.prototype.init = function() {
    this.observe(this.text, 'change', this.resize.bind(this));
    this.observe(this.text, 'cut', this.delayedResize.bind(this));
    this.observe(this.text, 'paste', this.delayedResize.bind(this));
    this.observe(this.text, 'drop', this.delayedResize.bind(this));
    this.observe(this.text, 'keydown', this.delayedResize.bind(this));

    this.resize();
};

resizableTextarea.prototype.observe = function(element, event, handler) {
    if (window.attachEvent) {
        element.attachEvent('on' + event, handler);
    } else {
        element.addEventListener(event, handler, false);
    }
};

resizableTextarea.prototype.resize = function() {
    var oldHeight = this.text.style.height;
    $(this.text).parent().css("height", $(this.text).parent().height());
    this.text.style.height = "auto";
    if(this.text.scrollHeight > 0) {
        this.text.style.height = this.text.scrollHeight + 16 + "px";
    } else {
        this.text.style.height = oldHeight;
    }
    $(this.text).parent().css("height", "");
};

resizableTextarea.prototype.delayedResize = function() {
    window.setTimeout(this.resize.bind(this), 0);
};
