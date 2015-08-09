(function ($) {

var insiteProxy = {
    crosser: null,

    init: function () {
        this.initData();
        this.listenToIframe();
    },

    initData: function () {
        this.serverOrigin = window.insiteProxyData.serverOrigin;
    },

    parentEvents: {
        injectScript: function(value) {
            window.INSITE.stopAll();
            window.INSITE.actions("inject-script", {
                value1: value
            });
        },

        previewActions: function (action) {
            window.INSITE.actions(action.ref,action.values, this.scrollToElement);
        }
    },

    scrollToElement: function (el) {
        if (el) {
            jQuery('html, body').animate({
                scrollTop: el.offset().top
            }, 1000);
        }
    },

    listenToIframe: function () {
        this.crosser = new Crosser(window.parent, this.serverOrigin);

        this.crosser.subscribeEvent('injectScript', this.parentEvents.injectScript.bind(this));
        this.crosser.subscribeEvent('PREVIEW_ACTIONS', this.parentEvents.previewActions.bind(this));
    }
};

insiteProxy.init();

window.insiteProxy = insiteProxy;

} (jQuery));
