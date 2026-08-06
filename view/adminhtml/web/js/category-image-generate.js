define([
    'jquery',
    'Mageprince_MageAI/js/model/category-image',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/modal'
], function ($, categoryImage, alert, modal) {
    'use strict';

    $.widget('mage.mageAiCategoryImageGenerate', {
        options: {
            modalSelector: '#mp-category-image-generate-modal',
            promptSelector: '#mp-category-image-prompt',
            buttonId: 'mp-category-generate-image-btn',
            generateImageUrl: window.mageAICategoryGenerateImageUrl || ''
        },

        /**
         * Widget initialization: inject the button, now and on every (re-)render of the form.
         */
        _create: function () {
            categoryImage.watch(this._injectButton.bind(this));
        },

        /**
         * Inject the "Generate Image with MageAI" button into the category image uploader,
         * next to the core Upload / Select from Gallery buttons. Idempotent.
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
                'class': 'action-secondary mp-generate-image-btn',
                title: $.mage.__('Generate Image with MageAI')
            }).html('<span>' + $.mage.__('Generate Image with MageAI') + '</span>');

            // Generated always comes first, edit second — see getActionsContainer()
            $container.prepend($btn);

            this._bindGenerateButton();
        },

        /**
         * Bind the click handler for the injected button (delegated so it survives re-renders).
         */
        _bindGenerateButton: function () {
            var self = this;

            $(document).off('click.mageAiCategoryImage', '#' + this.options.buttonId)
                .on('click.mageAiCategoryImage', '#' + this.options.buttonId, function () {
                    self._openModal();
                });
        },

        /**
         * Open the image generation modal. Initializes it on first use.
         */
        _openModal: function () {
            var self = this,
                $modal = $(this.options.modalSelector);

            if (categoryImage.isDisabled()) {
                alert({
                    title: $.mage.__('Category Image Locked'),
                    content: $.mage.__('The category image uses the default value. Uncheck "Use Default Value" to change it.')
                });

                return;
            }

            if (!$modal.data('mpCategoryImageModalInited')) {
                modal({
                    type: 'popup',
                    responsive: true,
                    title: $.mage.__('Generate Category Image with MageAI'),
                    modalClass: 'mp-mageai-image-modal',
                    buttons: [{
                        text: $.mage.__('Generate with MageAI'),
                        class: 'action-primary mp-generate-image-submit',
                        click: function () {
                            self._generate();
                        }
                    }]
                }, $modal);
                $modal.data('mpCategoryImageModalInited', true);
            }

            $modal.modal('openModal');
        },

        /**
         * Run the image generation AJAX call and, on success, set the image on the category.
         */
        _generate: function () {
            var self = this,
                prompt = $(this.options.promptSelector).val().trim();

            categoryImage.request(
                this.options.generateImageUrl,
                {
                    'custom_prompt': prompt,
                    'category_name': categoryImage.getFormValue('name'),
                    'category_description': categoryImage.getFormValue('description')
                },
                function (fileData) {
                    if (!categoryImage.setImage(fileData)) {
                        alert({
                            title: $.mage.__('Error'),
                            content: $.mage.__('The category image field could not be found on this page.')
                        });

                        return;
                    }

                    $(self.options.modalSelector).modal('closeModal');
                    $(self.options.promptSelector).val('');
                }
            );
        }
    });

    return $.mage.mageAiCategoryImageGenerate;
});
