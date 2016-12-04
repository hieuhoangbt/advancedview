(function ($) {
    "use strict";
    var AdvancedView = window.AdvancedView || {};
    AdvancedView.init = function () {
        /**
         * Overwrite firstLoad @function
         */
        SUGAR.ajaxUI.firstLoad = function () {
            var url = YAHOO.util.History.getBookmarkedState('ajaxUILoc');
            var aRegex = /action=([^&#]*)/.exec(window.location);
            var action = aRegex ? aRegex[1] : false;
            var mRegex = /module=([^&#]*)/.exec(window.location);
            var module = mRegex ? mRegex[1] : false;
            if (module != "ModuleBuilder" && module != "CGX_AdvancedView")
            {
                var go = url != null || action == "ajaxui";
                url = url ? url : 'index.php?module=Home&action=index';
                YAHOO.util.History.register('ajaxUILoc', url, SUGAR.ajaxUI.go);
                YAHOO.util.History.initialize("ajaxUI-history-field", "ajaxUI-history-iframe");
                SUGAR.ajaxUI.hist_loaded = true;
                if (go)
                    SUGAR.ajaxUI.go(url);
            }
            SUGAR_callsInProgress--;
        };
        SUGAR.util.doWhen("typeof $ != 'undefined'", function () {
            $("#MassAssign_SecurityGroups").remove();
        });
    };

    AdvancedView.init();
})(jQuery);