define([
    'jquery',
    'Mageprince_MageAI/js/model/category-image',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/modal'
], function ($, categoryImage, alert, modal) {
    'use strict';

    $.widget('mage.mageAiCategoryImageModify', {
        options: {
            modalSelector: '#mp-category-image-modify-modal',
            promptSelector: '#mp-category-modify-prompt',
            buttonId: 'mp-category-modify-image-btn',
            modifyImageUrl: window.mageAICategoryModifyImageUrl || ''
        },

        /**
         * Widget initialization: inject the button, now and on every (re-)render of the form.
         */
        _create: function () {
            this._current = null;
            this._pendingNew = null;
            categoryImage.watch(this._injectButton.bind(this));
        },

        /**
         * Inject the "Edit Image with MageAI" button into the category image uploader,
         * after the generate button. Idempotent.
         */
        _injectButton: function () {
            if ($('#' + this.options.buttonId).length) {
                return;
            }

            var $container = categoryImage.getActionsContainer();

            if (!$container.length) {
                return;
            }

            var $btn = $('<button>', {
                id: this.options.buttonId,
                type: 'button',
                'class': 'action-secondary mp-modify-image-btn',
                title: $.mage.__('Edit Image with MageAI')
            }).html('<span>' + $.mage.__('Edit Image with MageAI') + '</span>');

            $container.append($btn);

            this._bindButton();
        },

        /**
         * Bind the click handler for the injected button (delegated so it survives re-renders).
         */
        _bindButton: function () {
            var self = this;

            $(document).off('click.mageAiCategoryModify', '#' + this.options.buttonId)
                .on('click.mageAiCategoryModify', '#' + this.options.buttonId, function () {
                    self._openModal();
                });
        },

        /**
         * Open the modify modal on the prompt view, or explain that there is nothing to edit yet.
         */
        _openModal: function () {
            var $modal = $(this.options.modalSelector);

            this._current = categoryImage.getCurrentImage();
            this._pendingNew = null;

            if (categoryImage.isDisabled()) {
                alert({
                    title: $.mage.__('Category Image Locked'),
                    content: $.mage.__('The category image uses the default value. Uncheck "Use Default Value" to change it.')
                });

                return;
            }

            if (!this._current || !this._current.url) {
                alert({
                    title: $.mage.__('No Category Image'),
                    content: $.mage.__('This category has no image yet. Upload or generate one first, then edit it.')
                });

                return;
            }

            if (!$modal.data('mpCategoryModifyModalInited')) {
                modal({
                    type: 'popup',
                    responsive: true,
                    title: $.mage.__('Edit Category Image with MageAI'),
                    modalClass: 'mp-mageai-modify-modal',
                    buttons: []
                }, $modal);
                this._bindModalEvents($modal);
                $modal.data('mpCategoryModifyModalInited', true);
            }

            $modal.find('[data-role=modify-selected-img]').attr('src', this._current.url);
            $modal.find(this.options.promptSelector).val('');
            this._showView($modal, 'prompt');
            $modal.modal('openModal');
        },

        /**
         * Bind all in-modal interactions once.
         *
         * @param {jQuery} $modal
         */
        _bindModalEvents: function ($modal) {
            var self = this;

            // Submit the prompt and request a modified image.
            $modal.on('click', '[data-role=modify-submit]', function () {
                self._modify($modal);
            });

            // Discard the result and try a different prompt.
            $modal.on('click', '[data-role=modify-regenerate]', function () {
                self._pendingNew = null;
                self._showView($modal, 'prompt');
            });

            // Confirm: replace the category image with the modified version.
            $modal.on('click', '[data-role=modify-confirm]', function () {
                if (self._pendingNew) {
                    categoryImage.setImage(self._pendingNew);
                }
                $modal.modal('closeModal');
            });
        },

        /**
         * Switch the visible modal view: 'prompt' or 'result'.
         *
         * @param {jQuery} $modal
         * @param {String} name
         */
        _showView: function ($modal, name) {
            $modal.find('.mp-modify-view').hide();
            $modal.find('.mp-modify-view-' + name).show();
        },

        /**
         * Send the current image and prompt to the controller; on success show the comparison view.
         *
         * @param {jQuery} $modal
         */
        _modify: function ($modal) {
            var self = this,
                prompt = $modal.find(this.options.promptSelector).val().trim();

            categoryImage.request(
                this.options.modifyImageUrl,
                {
                    'custom_prompt': prompt,
                    // The uploader keeps the image URL; the controller resolves it back to a media path
                    'image_file': this._current.url || this._current.name,
                    'category_name': categoryImage.getFormValue('name'),
                    'category_description': categoryImage.getFormValue('description')
                },
                function (fileData) {
                    self._pendingNew = fileData;
                    $modal.find('[data-role=modify-original-img]').attr('src', self._current.url);
                    $modal.find('[data-role=modify-result-img]').attr('src', fileData.url);
                    self._showView($modal, 'result');
                }
            );
        }
    });

    return $.mage.mageAiCategoryImageModify;
});
