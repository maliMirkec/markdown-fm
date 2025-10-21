/**
 * Markdown FM Admin JavaScript
 * File: assets/admin.js
 */

(function($) {
    'use strict';

    const MarkdownFM = {
        init: function() {
            this.bindEvents();
            this.initMediaUploader();
        },

        bindEvents: function() {
            // Enable/Disable YAML for templates
            $(document).on('change', '.markdown-fm-enable-yaml', this.toggleYAML);

            // Edit Schema button
            $(document).on('click', '.markdown-fm-edit-schema', this.openSchemaModal);

            // Save Schema
            $(document).on('click', '.markdown-fm-save-schema', this.saveSchema);

            // Close Modal
            $(document).on('click', '.markdown-fm-modal-close', this.closeModal);
            $(document).on('click', '.markdown-fm-modal', function(e) {
                if ($(e.target).hasClass('markdown-fm-modal')) {
                    MarkdownFM.closeModal();
                }
            });

            // Block Controls
            $(document).on('click', '.markdown-fm-add-block', this.addBlock);
            $(document).on('click', '.markdown-fm-remove-block', this.removeBlock);

            // Escape key to close modal
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    MarkdownFM.closeModal();
                }
            });
        },

        toggleYAML: function() {
            const $checkbox = $(this);
            const template = $checkbox.data('template');
            const enabled = $checkbox.is(':checked');

            $.ajax({
                url: markdownFM.ajax_url,
                type: 'POST',
                data: {
                    action: 'markdown_fm_save_template_settings',
                    nonce: markdownFM.nonce,
                    template: template,
                    enabled: enabled
                },
                success: function(response) {
                    if (response.success) {
                        // Update the schema button visibility
                        const $row = $checkbox.closest('tr');
                        const $schemaCell = $row.find('td:last');

                        if (enabled) {
                            $schemaCell.html(
                                '<button type="button" class="button markdown-fm-edit-schema" ' +
                                'data-template="' + template + '" ' +
                                'data-name="' + $row.find('td:first strong').text() + '">' +
                                'Add Schema</button>'
                            );
                        } else {
                            $schemaCell.html('<span class="description">Enable YAML first</span>');
                        }

                        MarkdownFM.showMessage('Settings saved successfully', 'success');
                    } else {
                        $checkbox.prop('checked', !enabled);
                        MarkdownFM.showMessage('Error saving settings', 'error');
                    }
                },
                error: function() {
                    $checkbox.prop('checked', !enabled);
                    MarkdownFM.showMessage('Error saving settings', 'error');
                }
            });
        },

        openSchemaModal: function() {
            const $button = $(this);
            const template = $button.data('template');
            const templateName = $button.data('name');

            $('#markdown-fm-template-name').text(templateName);
            $('#markdown-fm-current-template').val(template);

            // Load existing schema
            $.ajax({
                url: markdownFM.ajax_url,
                type: 'POST',
                data: {
                    action: 'markdown_fm_get_schema',
                    nonce: markdownFM.nonce,
                    template: template
                },
                success: function(response) {
                    if (response.success) {
                        $('#markdown-fm-schema-editor').val(response.data.schema || '');
                    }
                }
            });

            $('#markdown-fm-schema-modal').fadeIn(300);
        },

        closeModal: function() {
            $('#markdown-fm-schema-modal').fadeOut(300);
        },

        saveSchema: function() {
            const template = $('#markdown-fm-current-template').val();
            const schema = $('#markdown-fm-schema-editor').val();

            if (!schema.trim()) {
                alert('Please enter a schema');
                return;
            }

            $('.markdown-fm-save-schema').prop('disabled', true).text('Saving...');

            $.ajax({
                url: markdownFM.ajax_url,
                type: 'POST',
                data: {
                    action: 'markdown_fm_save_schema',
                    nonce: markdownFM.nonce,
                    template: template,
                    schema: schema
                },
                success: function(response) {
                    if (response.success) {
                        MarkdownFM.showMessage('Schema saved successfully', 'success');
                        MarkdownFM.closeModal();

                        // Update the button text to "Edit Schema"
                        $('.markdown-fm-edit-schema[data-template="' + template + '"]')
                            .text('Edit Schema')
                            .after('<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>');
                    } else {
                        MarkdownFM.showMessage('Error saving schema', 'error');
                    }
                },
                error: function() {
                    MarkdownFM.showMessage('Error saving schema', 'error');
                },
                complete: function() {
                    $('.markdown-fm-save-schema').prop('disabled', false).text('Save Schema');
                }
            });
        },

        addBlock: function() {
            const $container = $(this).closest('.markdown-fm-block-container');
            const $select = $container.find('.markdown-fm-block-type-select');
            const blockType = $select.val();

            if (!blockType) {
                alert('Please select a block type');
                return;
            }

            const $blockList = $container.find('.markdown-fm-block-list');
            const fieldName = $container.data('field-name');
            const index = $blockList.find('.markdown-fm-block-item').length;

            // Get block definition from select option text
            const blockLabel = $select.find('option:selected').text();

            // Create new block item (simplified version)
            const $blockItem = $('<div>', {
                class: 'markdown-fm-block-item',
                'data-block-type': blockType
            });

            const $header = $('<div>', { class: 'markdown-fm-block-header' });
            $header.append($('<strong>').text(blockLabel));
            $header.append($('<button>', {
                type: 'button',
                class: 'button markdown-fm-remove-block',
                text: 'Remove'
            }));

            $blockItem.append($header);
            $blockItem.append($('<input>', {
                type: 'hidden',
                name: 'markdown_fm[' + fieldName + '][' + index + '][type]',
                value: blockType
            }));

            // Add a simple content field
            const $fieldsContainer = $('<div>', { class: 'markdown-fm-block-fields' });
            const $field = $('<div>', { class: 'markdown-fm-field' });
            $field.append($('<label>').text('Content'));
            $field.append($('<textarea>', {
                name: 'markdown_fm[' + fieldName + '][' + index + '][content]',
                rows: 5,
                class: 'large-text'
            }));

            $fieldsContainer.append($field);
            $blockItem.append($fieldsContainer);

            $blockList.append($blockItem);
            $select.val('');
        },

        removeBlock: function() {
            if (confirm('Are you sure you want to remove this block?')) {
                $(this).closest('.markdown-fm-block-item').fadeOut(300, function() {
                    $(this).remove();
                    // Re-index remaining blocks
                    MarkdownFM.reindexBlocks();
                });
            }
        },

        reindexBlocks: function() {
            $('.markdown-fm-block-container').each(function() {
                const fieldName = $(this).data('field-name');
                $(this).find('.markdown-fm-block-item').each(function(index) {
                    // Update input names with new index
                    $(this).find('input, textarea, select').each(function() {
                        const $input = $(this);
                        const name = $input.attr('name');
                        if (name) {
                            const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                            $input.attr('name', newName);
                        }
                    });
                });
            });
        },

        initMediaUploader: function() {
            let mediaUploader;

            // Image Upload
            $(document).on('click', '.markdown-fm-upload-image', function(e) {
                e.preventDefault();

                const $button = $(this);
                const targetId = $button.data('target');

                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }

                mediaUploader = wp.media({
                    title: 'Select Image',
                    button: {
                        text: 'Use This Image'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });

                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#' + targetId).val(attachment.url);

                    // Update preview
                    const $preview = $button.siblings('.markdown-fm-image-preview');
                    if ($preview.length) {
                        $preview.find('img').attr('src', attachment.url);
                    } else {
                        $button.after(
                            '<div class="markdown-fm-image-preview">' +
                            '<img src="' + attachment.url + '" style="max-width: 200px; display: block; margin-top: 10px;" />' +
                            '</div>'
                        );
                    }
                });

                mediaUploader.open();
            });

            // File Upload
            $(document).on('click', '.markdown-fm-upload-file', function(e) {
                e.preventDefault();

                const $button = $(this);
                const targetId = $button.data('target');

                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }

                mediaUploader = wp.media({
                    title: 'Select File',
                    button: {
                        text: 'Use This File'
                    },
                    multiple: false
                });

                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#' + targetId).val(attachment.url);

                    // Update file name display
                    const $fileDisplay = $button.siblings('.markdown-fm-file-name');
                    if ($fileDisplay.length) {
                        $fileDisplay.text(attachment.filename);
                    } else {
                        $button.after(
                            '<div class="markdown-fm-file-name">' + attachment.filename + '</div>'
                        );
                    }
                });

                mediaUploader.open();
            });
        },

        showMessage: function(message, type) {
            const $message = $('<div>', {
                class: 'markdown-fm-message ' + type,
                text: message
            });

            $('.markdown-fm-admin-container').prepend($message);

            setTimeout(function() {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        MarkdownFM.init();
    });

})(jQuery);
