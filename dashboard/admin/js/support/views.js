var PLCO_Support = PLCO_Support || {},
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
