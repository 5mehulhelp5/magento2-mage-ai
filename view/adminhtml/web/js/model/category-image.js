define([
    'jquery',
    'underscore',
    'ko',
    'uiRegistry',
    'Magento_Ui/js/modal/alert'
], function ($, _, ko, registry, alert) {
    'use strict';

    /**
     * Shared helper for the category image widgets.
     *
     * The category image field is a Knockout UI component (Magento_Ui/js/form/element/image-uploader),
     * so instead of writing to a DOM input the widgets read and write the component's value observable.
     * The component instance is resolved from the rendered element with ko.dataFor(), which avoids
     * depending on the ui_component path of the field.
     */
    return {
        options: {
            uploaderSelector: '.file-uploader.image-uploader',
            // Identifies the category image uploader among all uploaders rendered on the page
            uploaderUrlMatch: 'category_image/upload',
            fallbackSelector: '[data-index=image] .file-uploader.image-uploader',
            actionsClass: 'mp-mageai-image-actions',
            // Marks the uploader row as holding MageAI buttons so the CSS can keep it on one line
            areaClass: 'mp-mageai-has-actions'
        },

        /**
         * Run the given callback now and whenever the form DOM changes.
         *
         * The category form is rendered by Knockout, which fires no jQuery event when done, so the
         * widgets cannot inject their buttons on document ready alone. A single debounced observer
         * is shared by all callbacks; each callback is expected to be idempotent and cheap.
         *
         * @param {Function} callback
         */
        watch: function (callback) {
            this._watchers = this._watchers || [];
            this._watchers.push(callback);
            callback();

            if (this._watching) {
                return;
            }
            this._watching = true;

            var self = this,
                run = _.debounce(function () {
                    _.each(self._watchers, function (watcher) {
                        watcher();
                    });
                }, 150);

            $('body').on('contentUpdated', run);

            if (typeof MutationObserver !== 'undefined') {
                new MutationObserver(run).observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }
        },

        /**
         * Resolve the category image uploader element.
         *
         * @returns {jQuery}
         */
        getUploaderElement: function () {
            var self = this,
                found = null;

            $(this.options.uploaderSelector).each(function () {
                if (self.isCategoryUploader(self.getComponent(this))) {
                    found = this;

                    return false;
                }
            });

            if (!found) {
                found = $(this.options.fallbackSelector).get(0) || null;
            }

            return found ? $(found) : $();
        },

        /**
         * Get the Knockout component bound to the given element.
         *
         * @param {HTMLElement} element
         * @returns {Object|null}
         */
        getComponent: function (element) {
            var component;

            try {
                component = ko.dataFor(element);
            } catch (e) {
                return null;
            }

            return component && typeof component.addFile === 'function' ? component : null;
        },

        /**
         * Check whether the given component uploads to the category image controller.
         *
         * @param {Object|null} component
         * @returns {Boolean}
         */
        isCategoryUploader: function (component) {
            var url = component && component.uploaderConfig ? component.uploaderConfig.url : '';

            return typeof url === 'string' && url.indexOf(this.options.uploaderUrlMatch) !== -1;
        },

        /**
         * Get the uploader component of the category image field.
         *
         * @returns {Object|null}
         */
        getUploader: function () {
            var $el = this.getUploaderElement();

            return $el.length ? this.getComponent($el.get(0)) : null;
        },

        /**
         * Get the container the MageAI buttons are injected into, creating it when missing.
         *
         * The container is placed directly after the last core uploader button ("Select from
         * Gallery") so both MageAI buttons sit on the same row as Upload / Select from Gallery.
         * Both widgets share the container, so the buttons keep a stable order regardless of
         * which widget initializes first.
         *
         * @returns {jQuery}
         */
        getActionsContainer: function () {
            var $area = this.getUploaderElement().find('.file-uploader-area').first(),
                $container,
                $anchor;

            if (!$area.length) {
                return $();
            }

            $area.addClass(this.options.areaClass);
            $container = $area.find('.' + this.options.actionsClass).first();

            if (!$container.length) {
                $container = $('<span>', {'class': this.options.actionsClass});
                $anchor = $area.find('.file-uploader-button').last();

                if ($anchor.length) {
                    $anchor.after($container);
                } else {
                    $area.prepend($container);
                }
            }

            return $container;
        },

        /**
         * Check whether the field is read-only, which is the case on a store view scope while
         * "Use Default Value" is checked.
         *
         * @returns {Boolean}
         */
        isDisabled: function () {
            var component = this.getUploader();

            return !!(component && typeof component.disabled === 'function' && component.disabled());
        },

        /**
         * Get the image currently set on the category, if any.
         *
         * @returns {Object|null}
         */
        getCurrentImage: function () {
            var component = this.getUploader(),
                value = component ? component.value() : [];

            return value.length ? value[0] : null;
        },

        /**
         * Set the given file as the category image, replacing any existing one.
         *
         * @param {Object} fileData
         * @returns {Boolean} Whether the value was applied
         */
        setImage: function (fileData) {
            var component = this.getUploader();

            if (!component) {
                return false;
            }

            component.addFile(fileData);

            return true;
        },

        /**
         * Read a value from the category form data provider (e.g. 'name', 'description').
         *
         * @param {String} path
         * @returns {String}
         */
        getFormValue: function (path) {
            var provider = this.getFormProvider(),
                value = provider ? provider.get('data.' + path) : '';

            return value === null || value === undefined ? '' : String(value);
        },

        /**
         * Resolve the data provider of the category form.
         *
         * uiElement replaces the component's "source" property with the provider instance on init;
         * the registry lookup covers the case where the provider was not yet registered back then.
         *
         * @returns {Object|null}
         */
        getFormProvider: function () {
            var component = this.getUploader(),
                provider;

            if (!component) {
                return null;
            }

            if (component.source && typeof component.source.get === 'function') {
                return component.source;
            }

            provider = component.provider ? registry.get(component.provider) : null;

            return provider && typeof provider.get === 'function' ? provider : null;
        },

        /**
         * POST to a MageAI category image controller and hand the file data to the callback.
         *
         * @param {String} url
         * @param {Object} data
         * @param {Function} onSuccess  Receives the returned file data
         */
        request: function (url, data, onSuccess) {
            $.ajax({
                url: url,
                type: 'POST',
                showLoader: true,
                data: $.extend({'form_key': window.FORM_KEY}, data),
                success: function (response) {
                    if (response && response.error) {
                        alert({
                            title: $.mage.__('MageAI Error'),
                            content: response.data
                        });

                        return;
                    }

                    if (response && response.file) {
                        onSuccess(response);
                    } else {
                        alert({
                            title: $.mage.__('Error'),
                            content: $.mage.__('Unexpected response from server. Please try again.')
                        });
                    }
                },
                error: function () {
                    alert({
                        title: $.mage.__('Error'),
                        content: $.mage.__('Failed to communicate with the server. Please try again.')
                    });
                }
            });
        }
    };
});
