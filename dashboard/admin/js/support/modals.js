/**
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
