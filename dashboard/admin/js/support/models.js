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

