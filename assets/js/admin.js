(function ($) {

var insiteAdmin = {
    IFRAME_MIN_HEIGHT: 600,
    SCROLL_TOP_DELAY: 1000,

    crosser: null,

    init: function () {
        this.initData();
        this.initDisplay();
        this.listenToIframe();
    },

    initData: function () {
        this.blogPages = window.ioPluginData.blogPages;
        this.serverOrigin = window.ioPluginData.serverOrigin;
        this.adminPath = window.ioPluginData.adminPath;
    },

    getIframeViewPort: function(){
        return {
            height: $(window).height() - $('#wpadminbar').outerHeight() - $('#wpfooter').outerHeight(),
            width: $(window).width() - $('#adminmenuback').outerWidth()
        };
    },

    APPS_ROOTS: {
        dashboard: '/dashboard/home',
        wizard: '/wizard/editor',
        browser: '/browser/home'
    },

    iframeEvents : {

        getIframeViewPort: function(){
            return this.getIframeViewPort();
        },

        iframeViewportUpdated: function(){
            this.crosser.triggerEvent('iframeViewportUpdated', this.getIframeViewPort());
        },

        changeHeight: function (value) {
            $('#insite_iframe').height(value);
        },

        resetHeight: function () {
            var height = $(window).height() - 72;

            height = height < insiteAdmin.IFRAME_MIN_HEIGHT ? insiteAdmin.IFRAME_MIN_HEIGHT : height;
            $('#insite_iframe').height(height);
        },

        scrollToTop: function () {
            $("html, body").animate({ scrollTop: 0 }, insiteAdmin.SCROLL_TOP_DELAY);
        },

        minimizeWordpressMenu: function () {
            $('body').addClass('folded');
        },

        maximizeWordpressMenu: function () {
            $('body').removeClass('folded');
        },

        requestAddShortcodeWidget: function (data) {
            jQuery.post(ajaxurl, {"action": 'insite_update_shortcode','shortcode_id': data.id, 'shortcode_code': data.code}, function(response) {
                console.log('Got this from the server: ' + response);
                this.crosser.triggerEvent('responseAddShortcodeWidget', response);
            }.bind(this));
        },

        removeShortcodeWidget: function (data) {
            jQuery.post(ajaxurl, {'action': 'insite_delete_shortcode','shortcode_id': data.id}, function(response) {
                console.log('Got this from the server: ' + response);
            });
        },

        clearAllShortcodes: function (data) {
            jQuery.post(ajaxurl, {'action': 'insite_delete_shortcodes'}, function(response) {
                console.log('Got this from the server: ' + response);
            });
        },

        sendBlogPages: function () {
            this.crosser.triggerEvent('blogPages', this.blogPages);
        },

        editInsite: function(data){
            if (data.insiteId){
                window.location.href = this.adminPath + 'admin.php?page=insite&path=' + encodeURIComponent(this.APPS_ROOTS.wizard + '/rules/' + data.insiteId);
            }
        },

        previewUserInsite: function(data){
            if (data.insiteId){
                window.location.href = this.adminPath + 'admin.php?page=insite&path=' + encodeURIComponent(this.APPS_ROOTS.browser + '/browser/user-insite/' + data.insiteId);
            }
        },

        previewInsite: function(data){
            if (data.insiteId){
                window.location.href = this.adminPath + 'admin.php?page=insite&path=' + encodeURIComponent(this.APPS_ROOTS.browser + '/browser/insite/' + data.insiteId);
            }
        },

        editProfile: function(data){
            window.location.href = this.adminPath + 'admin.php?page=insite-profile&path=' + encodeURIComponent(this.APPS_ROOTS.dashboard + '/profile');
        },

        openDashboard: function(){
            window.location.href = this.adminPath + 'admin.php?page=insite-my';
        },

        openBrowser: function(){
            window.location.href = this.adminPath + 'admin.php?page=insite';
        },

        goToAdminPath: function(path){
            window.location.href = this.adminPath + path;
        },

        connectAccount: function (data) {
            var _self = this;
            jQuery.post(ajaxurl, {'action': 'insite_connect_account','data': data}, function(response) {
                console.log('connect url: ' + response);
                _self.crosser.triggerEvent('redirectToUrl', response);
            });
        }
    },

    initDisplay: function () {
        $(document).ready(function () {
            window.setTimeout(function(){
                $('#insite_iframe').height(this.getIframeViewPort().height);
            }.bind(this), 0)
        }.bind(this));
        $(window).on('resize.send-iframe-viewport', this.iframeEvents.iframeViewportUpdated.bind(this));
    },

    listenToIframe: function () {
        var iframeWindow = $('#insite_iframe')[0].contentWindow;

        this.crosser = new Crosser(iframeWindow, this.serverOrigin);

        this.crosser.subscribe('getIframeViewport', this.iframeEvents.getIframeViewPort.bind(this));

        this.crosser.subscribeEvent('changeHeight', this.iframeEvents.changeHeight.bind(this));
        this.crosser.subscribeEvent('resetHeight', this.iframeEvents.resetHeight.bind(this));
        this.crosser.subscribeEvent('scrollToTop', this.iframeEvents.scrollToTop.bind(this));
        this.crosser.subscribeEvent('minimizeWordpressMenu', this.iframeEvents.minimizeWordpressMenu.bind(this));
        this.crosser.subscribeEvent('maximizeWordpressMenu', this.iframeEvents.maximizeWordpressMenu.bind(this));
        this.crosser.subscribeEvent('requestAddShortcodeWidget', this.iframeEvents.requestAddShortcodeWidget.bind(this));
        this.crosser.subscribeEvent('removeShortcodeWidget', this.iframeEvents.removeShortcodeWidget.bind(this));
        this.crosser.subscribeEvent('clearAllShortcodes', this.iframeEvents.clearAllShortcodes.bind(this));
        this.crosser.subscribeEvent('getBlogPages', this.iframeEvents.sendBlogPages.bind(this));
        this.crosser.subscribeEvent('editInsite', this.iframeEvents.editInsite.bind(this));
        this.crosser.subscribeEvent('editProfile', this.iframeEvents.editProfile.bind(this));
        this.crosser.subscribeEvent('previewUserInsite', this.iframeEvents.previewUserInsite.bind(this));
        this.crosser.subscribeEvent('previewInsite', this.iframeEvents.previewInsite.bind(this));
        this.crosser.subscribeEvent('openDashboard', this.iframeEvents.openDashboard.bind(this));
        this.crosser.subscribeEvent('openBrowser', this.iframeEvents.openBrowser.bind(this));
        this.crosser.subscribeEvent('goToAdminPath', this.iframeEvents.goToAdminPath.bind(this));
        this.crosser.subscribeEvent('connectAccount', this.iframeEvents.connectAccount.bind(this));
    }
};

insiteAdmin.init();

window.insiteAdmin = insiteAdmin;

} (jQuery));
