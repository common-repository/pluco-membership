/**
 * Created by Pop Aurelian.
 */

var PLCO_Support = PLCO_Support || {},
    PLCO_Support = PLCO_Support || {},
    PLCOUtils = PLCOUtils || {};
PLCO_Support.globals = PLCO_Support.globals || {};
PLCO_Support.views = PLCO_Support.views || {};
PLCOUtils.util = PLCOUtils.util || {};
PLCOUtils.globals = PLCOUtils.globals || {};
PLCO_Support.globals = PLCO_Support.globals || {};
PLCO_Support.views = PLCO_Support.views || {};


(function ($) {
    let Router = Backbone.Router.extend({
        $el: $('#plco-support-content'),
        routes: {
            'support': 'dashboard'
        },
        dash_view: null,
        dashboard: function () {
            var self = this;

            if ( this.dash_view ) {
                this.dash_view.remove();
            }

            if ( PLCOUtils.globals.license.get('jwt_access')) {
                PLCO_Support.globals.topics.fetch().success( function () {
                    self.renderDashboard();
                } );
            } else {
                this.renderNoLicenseDashboard();
            }
        },
        renderNoLicenseDashboard: function (  ) {
            this.dash_view = new PLCO_Support.views.NoLicenseDashboard( {
            } );

            this.$el.html( this.dash_view.render().$el );
            window.scrollTo( 0, 0 );
        },
        renderDashboard: function () {
            this.dash_view = new PLCO_Support.views.Dashboard( {
                model: PLCOUtils.globals.license,
                collection: PLCO_Support.globals.topics
            } );

            this.$el.html( this.dash_view.render().$el );
            window.scrollTo( 0, 0 );
        }

    });
    /**
     * DOM Ready
     */
    $(function () {
        PLCO_Support.globals.topics = new PLCO_Support.collections.Topics()
        PLCO_Support.globals.page_loader = new PLCOUtils.util.PageLoader();

        PLCO_Support.router = new Router;

        if (!Backbone.History.started) {
            Backbone.history.start({hashchange: true});
        }

        if (!Backbone.history.getFragment()) {
            PLCO_Support.router.navigate('#support', {trigger: true});
        } else {
            Backbone.history.fragment = null;
            Backbone.history.navigate(Backbone.history.getFragment(), {trigger: true});
        }
    });
})(jQuery);
