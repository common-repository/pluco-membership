/**
 * Created by Pop Aurelian on 11-Nov-17.
 */

var PLCO_Support = PLCO_Support || {},
    PLCOUtils = PLCOUtils || {};
PLCO_Support.models = PLCO_Support.models || {};
PLCO_Support.collections = PLCO_Support.collections || {};
PLCOUtils.util = PLCOUtils.util || {};
PLCOUtils.globals = PLCOUtils.globals || {};
PLCOUtils.models = PLCOUtils.models || {};
PLCOUtils.collections = PLCOUtils.collections || {};

(function ( $ ) {
    /**
     * Model For A Topic
     */
    PLCO_Support.models.Topic = PLCOUtils.models.Base.extend( {
        idAttribute: 'id',
        defaults: {
            title: '',
            content: '',
            replies: [],
            product: '',
            user_id: '',
            status: 1
        },
        initialize: function (  ) {
            this.set('replies', new PLCO_Support.collections.Replies(this.get('replies')));
            this.set('user_id', PLCOUtils.globals.license.get('user_id'));
        },
        validate: function ( attrs, options ) {
            var errors = [];

            if ( ! attrs.title ) {
                errors.push( this.validation_error( 'title', PLCOUtils.t.NoSlug ) );
            }

            if ( ! attrs.content ) {
                errors.push( PLCOUtils.t.NoContent );
            }

            if ( ! attrs.product ) {
                errors.push( PLCOUtils.t.NoProduct );
            }

            if ( errors.length ) {
                return errors;
            }
        },
        url: function () {
            var url = PLCOUtils.routes.topics;

            if ( this.get( 'id' ) || this.get( 'id' ) === 0 ) {
                url += '/' + this.get( 'id' );
            }
            return url;
        }
    } );

    /**
     * Topics Collection
     */
    PLCO_Support.collections.Topics = PLCOUtils.collections.Base.extend( {
        model: PLCO_Support.models.Topic,
        url: function () {
            return PLCOUtils.routes.topics + '/get_topics/';
        },
        comparator: function ( topicA, topicB ) {
            if ( topicA.get( 'created_at' ) > topicB.get( 'created_at' ) ) {
                return - 1;
            } // before
            if ( topicB.get( 'created_at' ) > topicA.get( 'created_at' ) ) {
                return 1;
            } // after
            return 0; // equal
        }
    } );

    PLCO_Support.models.Pagination = PLCOUtils.models.Base.extend({
        defaults: {
            current_page: 1,
            items_per_page: 10,
            total_items: 0,
            state: "ACTIVE",
            extra: [
                {test: 1}
            ],
        }
    });

    /**
     * Model For A Topic
     */
    PLCO_Support.models.Reply = PLCOUtils.models.Base.extend( {
        idAttribute: 'ID',
        defaults: {
            topic_id: '',
            user_id: '',
            content: ''
        },
        validate: function ( attrs, options ) {
            var errors = [];
            if ( ! attrs.content ) {
                errors.push( PLCOUtils.t.NoContent );
            }

            if ( errors.length ) {
                return errors;
            }
        },
        url: function () {
            var url = PLCOUtils.routes.replies;

            if ( this.get( 'ID' ) || this.get( 'ID' ) === 0 ) {
                url += '/' + this.get( 'ID' );
            }
            return url;
        }
    } );

    /**
     * Topics Collection
     */
    PLCO_Support.collections.Replies = PLCOUtils.collections.Base.extend( {
        model: PLCO_Support.models.Reply,
        comparator: function ( topicA, topicB ) {
            if ( topicA.get( 'created_at' ) < topicB.get( 'created_at' ) ) {
                return - 1;
            } // before
            if ( topicB.get( 'created_at' ) < topicA.get( 'created_at' ) ) {
                return 1;
            } // after
            return 0; // equal
        }
    } );
})( jQuery );

;var PLCO_Support = PLCO_Support || {},
    PLCOUtils = PLCOUtils || {},
    PLCO_Dashboard = PLCO_Dashboard || {};
PLCO_Dashboard.views = PLCO_Dashboard.views || {};
PLCOUtils.util = PLCOUtils.util || {};
PLCOUtils.globals = PLCOUtils.globals || {};

(function ($) {
    $(function () {
        /**
         * Header View
         */
        PLCO_Support.views.Header = PLCOUtils.views.Base.extend({
            template: PLCOUtils.util.template('support_header'),
            render: function () {
                this.$el.html(this.template({}));
                PLCOUtils.util.materialize(this.$el);
                return this;

            }
        });

        PLCO_Support.views.Dashboard = PLCOUtils.views.Base.extend({
            className: 'plco-container row',
            template: PLCOUtils.util.template('support_dashboard'),
            events: {
                'click .plco-open-topic': 'openTopic'
            },
            initialize: function () {
                this.listenTo(this.collection, 'add', this.render);
            },
            render: function () {
                this.$el.html(this.template({}));
                this.renderHeader();
                this.renderTopics();
                return this
            },
            renderHeader: function () {
                var view = new PLCO_Support.views.SupportHeader({});

                this.$('.plco-support-table').append(view.render().$el);
            },
            renderTopics: function () {
                this.$('.plco-support-table').append('<tbody>');
                this.collection.each(this.renderTopic, this);
            },
            renderTopic: function (topic) {
                var view = new PLCO_Support.views.SupportTopic({
                    model: topic
                });


                this.$('.plco-support-table tbody').append(view.render().$el);
            },
            openTopic: function () {
                if (PLCOUtils.globals.license.get('jwt_access')) {
                    this.modal(PLCO_Support.views.OpenTopic, {
                        'max-width': '60%',
                        width: '1000px',
                        in_duration: 200,
                        out_duration: 0,
                        model: new PLCO_Support.models.Topic,
                        collection: PLCO_Support.globals.topics,
                        products: PLCOUtils.globals.products
                    });
                } else {
                    PLCOUtils.toast_error('Your license has expired, please renew your license');
                }
            }
        });

        PLCO_Support.views.SupportHeader = PLCOUtils.views.Base.extend({
            tagName: 'thead',
            className: 'plco-table-header',
            template: PLCOUtils.util.template('table/header'),
            render: function () {
                this.$el.html(this.template({}));
                return this;
            }
        });


        PLCO_Support.views.SupportTopic = PLCOUtils.views.Base.extend({
            tagName: 'tr',
            className: 'plco-table-topic',
            template: PLCOUtils.util.template('table/topic'),
            events: {
                'click .plco-expand-topic': 'toggleContent',
                'click .plco-close-topic': 'closeTopic',
                'click .plco-reopen-topic': 'reopenTopic',
                'click .plco-edit-topic': 'editTopic'
            },
            initialize: function () {
                var self = this;
                this.model.on('changed_status', function () {
                    self.render();
                    self.toggleContent();
                });
            },
            render: function () {
                this.$el.html(this.template({model: this.model}));
                this.renderContent();
                PLCOUtils.util.materialize(this.$el);

                return this;
            },
            renderContent: function () {
                var view = new PLCO_Support.views.SupportTopicContent({
                    el: this.$('.plco-table-content'),
                    model: this.model,
                    collection: this.collection
                });

                view.render();
            },
            toggleContent: function () {
                var icon = this.$('.plco-expand-topic i');
                icon.text() === 'expand_more' ? icon.text('expand_less') : icon.text('expand_more');
                this.$('.plco-table-content').slideToggle();
            },
            editTopic: function () {
                this.modal(PLCO_Support.views.OpenTopic, {
                    'max-width': '60%',
                    width: '1000px',
                    in_duration: 200,
                    out_duration: 0,
                    model: this.model,
                    collection: PLCO_Support.globals.topics,
                    products: PLCOUtils.globals.products
                });
            },
            closeTopic: function () {
                if (PLCOUtils.globals.license.get('jwt_access')) {

                    this.modal(PLCO_Support.views.CloseTopic, {
                        'max-width': '60%',
                        width: '1000px',
                        in_duration: 200,
                        out_duration: 0,
                        model: this.model
                    });
                } else {
                    PLCOUtils.toast_error('Your license has expired, please renew your license');
                }
            },
            reopenTopic: function () {
                if (PLCOUtils.globals.license.get('jwt_access')) {

                    this.modal(PLCO_Support.views.ReOpenTopic, {
                        'max-width': '60%',
                        width: '1000px',
                        in_duration: 200,
                        out_duration: 0,
                        model: this.model
                    });
                } else {
                    PLCOUtils.toast_error('Your license has expired, please renew your license');
                }
            }
        });

        PLCO_Support.views.SupportTopicContent = PLCOUtils.views.Base.extend({
            template: PLCOUtils.util.template('table/topic_content'),
            events: {
                'click .plco-add-reply': 'addReply'
            },
            initialize: function () {
                this.listenTo(this.model.get('replies'), 'add', this.render);
                this.listenTo(this.model.get('replies'), 'remove', this.render);
            },
            render: function () {
                this.$el.html(this.template({model: this.model}));
                this.renderReplies();
                return this;
            },
            renderReplies: function () {
                if (this.model.get('replies').length > 0) {

                    this.model.get('replies').each(function (reply) {
                        var view = new PLCO_Support.views.SupportTopicReply({
                            model: reply,
                            topic: this.model,
                            collection: this.model.get('replies')
                        });

                        this.$('.plco-table-replies').append(view.render().$el);
                    }, this);
                } else {
                    this.$('.plco-table-replies').html('<li>There are no replies yet</li>')
                }
            },
            addReply: function () {
                if (PLCOUtils.globals.license.get('jwt_access')) {
                    var model = new PLCO_Support.models.Reply();
                    model.set({topic_id: this.model.get('ID'), user_id: PLCOUtils.globals.license.get('user_id')});

                    this.modal(PLCO_Support.views.AddReply, {
                        'max-width': '60%',
                        width: '1000px',
                        in_duration: 200,
                        out_duration: 0,
                        model: model,
                        topic: this.model,
                        collection: this.model.get('replies')
                    });
                } else {
                    PLCOUtils.toast_error('Your license has expired, please renew your license');
                }
            }
        });

        PLCO_Support.views.SupportTopicReply = PLCOUtils.views.Base.extend({
            tagName: 'li',
            className: 'plco-collection-item plco-table-reply plco-col plco-s12',
            template: PLCOUtils.util.template('table/topic_reply'),
            events: {
                'click .plco-edit-reply': 'editReply',
                'click .plco-delete-reply': 'deleteReply'
            },
            initialize: function (options) {
                this.topic = options.topic;
            },
            render: function () {
                this.$el.html(this.template({model: this.model}));
                return this;
            },
            editReply: function () {
                this.modal(PLCO_Support.views.AddReply, {
                    'max-width': '60%',
                    width: '1000px',
                    in_duration: 200,
                    out_duration: 0,
                    model: this.model,
                    topic: this.topic,
                    collection: this.topic.get('replies')
                });
            },
            deleteReply: function () {
                this.modal(PLCO_Support.views.DeleteReply, {
                    'max-width': '60%',
                    width: '1000px',
                    in_duration: 200,
                    out_duration: 0,
                    model: this.model,
                    topic: this.topic,
                    collection: this.topic.get('replies')
                });
            }
        });

        PLCO_Support.views.NoLicenseDashboard = PLCOUtils.views.Base.extend({
            className: 'plco-container',
            template: PLCOUtils.util.template('no-license'),
            render: function () {
                this.$el.html(this.template({}));
                return this;
            }
        });
    });
})(jQuery);
;/**
 * The One Plugin - https://theoneplugin.com
 * Created by stork on 3/19/2017.
 *
 * @package custom-fields
 */

var PLCO_Support = PLCO_Support || {},
    PLCOUtils = PLCOUtils || {};
PLCO_Support.views = PLCO_Support.views || {};
PLCOUtils.util = PLCOUtils.util || {};
PLCOUtils.globals = PLCOUtils.globals || {};

(function ( $ ) {

    $( function () {
        PLCO_Support.views.OpenTopic = PLCOUtils.util.Modal.extend( {
            template: PLCOUtils.util.template( 'modals/open_topic' ),
            events: function () {
                return _.extend( {}, PLCOUtils.util.ModalSteps.prototype.events, {
                    'input .plco-input-change': 'changeInput',
                    'change select': 'changeSelect',
                    'click .plco-open-topic': 'saveTopic'
                } );

            },
            afterRender: function () {
                this.renderMCE();
            },
            renderMCE: function () {
                var self = this;
                setTimeout( function () {
                    PLCOUtils.util.clearMCEEditor( 'plco-content' );
                    self.editorInit( 'plco-content' );
                }, 0 );
            },
            editorInit: function ( id ) {
                if ( typeof tinymce === 'undefined' || ! tinymce ) {
                    return;
                }
                var self = this,
                    mce_reinit = PLCOUtils.util.build_mce_init( {
                        mce: window.tinyMCEPreInit.mceInit['plco-dummy-editor'],
                        qt: window.tinyMCEPreInit.qtInit['plco-dummy-editor']
                    }, id );

                tinyMCEPreInit.mceInit = $.extend( tinyMCEPreInit.mceInit, mce_reinit.mce_init );
                tinyMCEPreInit.mceInit[id].setup = function ( editor ) {
                    editor.on( 'init', function () {
                        editor.setContent( self.model.get( 'content' ) );
                    } );
                    editor.on( 'keyup', function ( e ) {
                        var description = tinymce.get( 'plco-content' );

                        self.model.set( {content: description.getContent()} );
                    } );
                };
                tinymce.init( tinyMCEPreInit.mceInit[id] );
            },
            changeInput: function ( e ) {
                var value = e.target.value,
                    name = $( e.target ).attr( 'name' );

                this.model.set( name, value );
            },
            changeSelect: function ( e ) {
                var value = $( e.target ).val(),
                    name = $( e.target ).attr( 'name' );

                this.model.set( name, value );
            },
            saveTopic: function () {
                PLCOUtils.opened_modal_view.showLoader();
                this.PLCO_clear_errors();

                if ( ! this.model.isValid() ) {
                    return this.PLCO_display_errors( this.model );
                }

                var xhr = this.model.save(),
                    self = this;

                if ( xhr ) {
                    xhr.done( function ( response, status, options ) {
                        var model = self.collection.findWhere( {title: self.model.get( 'title' )} );
                        self.model.set( response );
                        self.model.set('replies', new PLCO_Support.collections.Replies(self.model.get('replies')));
                        if ( ! model ) {
                            self.collection.add( self.model );
                        } else {
                            self.model.trigger( 'changed_status' );
                        }



                        self.close();
                    } );
                    xhr.error( function ( errorObj ) {
                        var response = JSON.parse( errorObj.responseText );

                        PLCOUtils.toast_error( response.message );
                    } );
                    xhr.complete(function () {
                        PLCOUtils.opened_modal_view.hideLoader();
                    });
                }
            }
        } );

        PLCO_Support.views.AddReply = PLCOUtils.util.Modal.extend( {
            template: PLCOUtils.util.template( 'modals/add_reply' ),
            events: {
                'click .plco-add-reply': 'addReply'
            },
            afterInitialize: function ( options ) {
                this.topic = options.topic;
            },
            afterRender: function () {
                this.renderMCE();
            },
            renderMCE: function () {
                var self = this;
                setTimeout( function () {
                    PLCOUtils.util.clearMCEEditor( 'plco-reply-content' );
                    self.editorInit( 'plco-reply-content' );
                }, 0 );
            },
            editorInit: function ( id ) {
                if ( typeof tinymce === 'undefined' || ! tinymce ) {
                    return;
                }
                var self = this,
                    mce_reinit = PLCOUtils.util.build_mce_init( {
                        mce: window.tinyMCEPreInit.mceInit['plco-dummy-editor'],
                        qt: window.tinyMCEPreInit.qtInit['plco-dummy-editor']
                    }, id );

                tinyMCEPreInit.mceInit = $.extend( tinyMCEPreInit.mceInit, mce_reinit.mce_init );
                tinyMCEPreInit.mceInit[id].setup = function ( editor ) {
                    editor.on( 'init', function () {
                        editor.setContent( self.model.get( 'content' ) );
                    } );
                    editor.on( 'keyup', function ( e ) {
                        var description = tinymce.get( 'plco-reply-content' );

                        self.model.set( {content: description.getContent()} );
                    } );
                };
                tinymce.init( tinyMCEPreInit.mceInit[id] );
            },
            addReply: function () {
                this.PLCO_clear_errors();

                if ( ! this.model.isValid() ) {
                    return this.PLCO_display_errors( this.model );
                }

                var id = this.model.get( 'id' ),
                    xhr = this.model.save(),
                    self = this;

                if ( xhr ) {
                    xhr.done( function ( response, status, options ) {
                        self.model.set( response );
                        if ( ! id ) {
                            self.collection.add( self.model );
                        }
                        self.topic.set( {status: 1} );
                        self.topic.trigger( 'changed_status' );
                        self.close();
                    } );
                    xhr.error( function ( errorObj ) {
                        var response = JSON.parse( errorObj.responseText );

                        PLCOUtils.toast_error( response.message );
                    } );
                }
            }
        } );

        PLCO_Support.views.DeleteReply = PLCOUtils.util.Modal.extend( {
            template: PLCOUtils.util.template( 'modals/delete_reply' ),
            events: {
                'click .plco-yes': 'deleteReply'
            },
            deleteReply: function ( ) {
                var xhr = this.model.destroy(),
                    self = this;

                if ( xhr ) {
                    xhr.done( function ( response, status, options ) {
                        PLCOUtils.toast_success("Successfully Deleted Reply");
                        self.close();
                    } );
                    xhr.error( function ( errorObj ) {
                        var response = errorObj.responseText;

                        PLCOUtils.toast_error(response.message);
                    } );
                }
            }
        });

        PLCO_Support.views.CloseTopic = PLCOUtils.util.Modal.extend( {
            template: PLCOUtils.util.template( 'modals/close_topic' ),
            events: {
                'click .plco-yes': 'closeTopic'
            },
            closeTopic: function () {
                PLCOUtils.opened_modal_view.showLoader();
                var self = this;
                $.ajax( {
                    headers: {
                        'X-WP-Nonce': PLCOUtils.nonce
                    },
                    type: 'POST',
                    data: {
                        ID: self.model.get( 'ID' )
                    },
                    url: PLCOUtils.routes.topics + '/close_topic/'
                } ).done( function ( response, status, options ) {
                    if ( response ) {
                        self.model.set( {status: 0} );
                        self.model.trigger( 'changed_status' );
                        self.close();
                    }
                } ).error( function ( errorObj ) {
                    var response = JSON.parse( errorObj.responseText );

                    PLCOUtils.toast_error( response.message );
                } ).complete(function () {
                    PLCOUtils.opened_modal_view.hideLoader();
                });;
            }
        } );

        PLCO_Support.views.ReOpenTopic = PLCOUtils.util.Modal.extend( {
            template: PLCOUtils.util.template( 'modals/reopen_topic' ),
            events: {
                'click .plco-yes': 'reopenTopic'
            },
            reopenTopic: function () {
                PLCOUtils.opened_modal_view.showLoader();
                var self = this;
                $.ajax({
                    headers: {
                        'X-WP-Nonce': PLCOUtils.nonce
                    },
                    type: 'POST',
                    data: {
                        ID: self.model.get('ID')
                    },
                    url: PLCOUtils.routes.topics + '/reopen_topic/'
                }).done(function (response, status, options) {
                    if (response) {
                        self.model.set({status: 1});
                        self.model.trigger('changed_status');
                        self.close();
                    }
                }).error(function (errorObj) {
                    var response = JSON.parse(errorObj.responseText);

                    PLCOUtils.toast_error(response.message);
                }).complete(function () {
                    PLCOUtils.opened_modal_view.hideLoader();
                });
            }
        } );
    } ); //end document ready
})( jQuery );
;/**
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
