/**
 * The JS for endless-update.
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package Goma\Form
 * @version 1.1
 */
var endlessScroller = function(options) {
    this.baseElement = $("body");

    for(var i in options) {
        if(options.hasOwnProperty(i)) {
            this[i] = options[i];
        }
    }

    this.scrollElement.scroll(this.checkForEndlessUpdate.bind(this));
    this.checkForEndlessUpdate();

    return this;
};

endlessScroller.prototype = {
    scrollElement: $(window),
    endlessElement: null,
    urlProperty: "data-url",
    urlWrapperElement: null,
    currentEndlessEndLoading: null,
    throttleTo: 10,
    throttleTime: null,
    checkForEndlessUpdate: function() {
        if(this.endlessElement == null) {
            console.log("Unable to find endless-element due to the fact it is null.");
            return;
        }

        var endLessEnd = is_string(this.endlessElement) ? this.baseElement.find(this.endlessElement) : $(this.endlessElement);
        if(endLessEnd.length == 0 || this.currentEndlessEndLoading != null)
            return;

        var currentTime = new Date().getTime();
        if(this.throttleTime == null || currentTime - this.throttleTime > this.throttleTo) {
            var scroll = this.scrollElement.scrollTop();
            var offset = endLessEnd.offset();
            var scrollHeight = this.scrollElement.height();
            var scrollOffset = this.scrollElement.offset() != null ? this.scrollElement.offset() : {top: 0, left: 0};

            if (offset.top - scrollOffset.top < scroll + scrollHeight * 1.25) {
                this.currentEndlessEndLoading = endLessEnd;

                this.onStartLoading();
                this.loadData()
                    .done(this.insertData.bind(this))
                    .fail(this.onError.bind(this))
                    .done(this.onFinishInsertingData.bind(this)).always(function(){
                        this.currentEndlessEndLoading = null;
                        this.checkForEndlessUpdate();
                        goma.ui.triggerContentLoaded();

                        setTimeout(this.checkForEndlessUpdate.bind(this), 250);
                    }.bind(this));
            }
            this.throttleTime = currentTime;
        }
    },
    loadData: function() {
        return $.ajax({
            url: this.currentEndlessEndLoading.attr(this.urlProperty)
        });
    },
    insertData: function(data) {
        if(this.urlWrapperElement != null) {
            var node = $("<div></div>").append(data);

            this.currentEndlessEndLoading.replaceWith(node.find(this.urlWrapperElement).html());
        } else {
            this.currentEndlessEndLoading.replaceWith(data);
        }
    },
    onError: function() {

    },
    onStartLoading: function() {
        this.currentEndlessEndLoading.addClass("loading");
    },
    onFinishInsertingData: function() {

    }
};
