/**
 * Created by Pop Aurelian.
 */


var PLCO_Membership = PLCO_Membership || {},
    PLCOUtils = PLCOUtils || {};
PLCO_Membership.views = PLCO_Membership.views || {};
PLCOUtils.util = PLCOUtils.util || {};

( function ( $ ) {
    $( function () {

        PLCO_Membership.views.MemberEditTabContent = PLCOUtils.views.Base.extend( {
            template: PLCOUtils.util.template( 'membership_view' ),
            payments: null,
            initialize: function (options) {
                this.setData();
            },
            render: function () {
                if(this.model.get("user")) {
                    this.$el.html( this.template( { model: this.model } ) );
                    wp.hooks.doAction("render_view_membership_extra_fields", this);
                    let view = new PLCO_Membership.views.MembershipPaymentsContent( {
                    } );

                    this.$el.find( ".plco-user-membership-payments" ).append( view.render().$el );
                }

                PLCO_Membership.globals.page_loader.close();
                return this;
            },
            setData() {
                let ID = parseInt( Backbone.history.getFragment().split( "/" )[ 1 ] );

                this.model = new PLCO_Membership.models.Member({ID: ID})

                let self = this;
                PLCO_Membership.globals.page_loader.open();

                let xhr = this.model.fetch();

                if ( xhr ) {
                    xhr.success( function ( response, status, options ) {
                        self.model.set( response );
                        self.render();
                    } );
                    xhr.complete( function () {
                        PLCO_Membership.globals.page_loader.close();
                    } );
                }
            }
        });
    } );
} )( jQuery );
