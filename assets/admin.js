/**
 * YAML Custom Fields Admin JavaScript
 * File: assets/admin.js
 */

(function ($) {
  'use strict';

  const YamlCF = {
    hasMetaBoxChanges: false,
    originalMetaBoxData: {},

    init: function () {
      this.bindEvents();
      this.initMediaUploader();
      this.initMetaBoxChangeTracking();
    },

    bindEvents: function () {
      // Enable/Disable YAML for templates
      $(document).on('change', '.yaml-cf-enable-yaml', this.toggleYAML);

      // Edit Schema button
      $(document).on('click', '.yaml-cf-edit-schema', this.openSchemaModal);

      // Save Schema
      $(document).on('click', '.yaml-cf-save-schema', this.saveSchema);

      // Note: Partial data editing moved to dedicated page (yaml-cf-edit-partial)

      // Close Schema Modal
      $(document).on('click', '.yaml-cf-modal-close', this.closeModal);
      $(document).on('click', '.yaml-cf-modal', function (e) {
        if ($(e.target).hasClass('yaml-cf-modal')) {
          YamlCF.closeModal();
        }
      });

      // Block Controls
      $(document).on('click', '.yaml-cf-add-block', this.addBlock);
      $(document).on('click', '.yaml-cf-remove-block', this.removeBlock);

      // Clear Media
      $(document).on('click', '.yaml-cf-clear-media', this.clearMedia);

      // Partial Image/File Upload (for modal-based editing - if still used)
      $(document).on('click', '.yaml-cf-upload-image-partial', this.uploadImagePartial);
      $(document).on('click', '.yaml-cf-upload-file-partial', this.uploadFilePartial);

      // Reset All Data
      $(document).on('click', '.yaml-cf-reset-data', this.resetAllData);

      // Export/Import Settings
      $(document).on(
        'click',
        '.yaml-cf-export-settings',
        this.exportSettings
      );
      $(document).on(
        'click',
        '.yaml-cf-import-settings-trigger',
        this.triggerImport
      );
      $(document).on('change', '#yaml-cf-import-file', this.importSettings);

      // Code Snippet Copy
      $(document).on('click', '.yaml-cf-copy-snippet', this.copySnippet);
      $(document).on('mouseenter', '.yaml-cf-copy-snippet', this.showSnippetPopover);
      $(document).on('mouseleave', '.yaml-cf-copy-snippet', this.hideSnippetPopover);

      // Escape key to close modal
      $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
          YamlCF.closeModal();
        }
      });
    },

    toggleYAML: function () {
      const $checkbox = $(this);
      const template = $checkbox.data('template');
      const enabled = $checkbox.is(':checked');

      $.ajax({
        url: yamlCF.ajax_url,
        type: 'POST',
        data: {
          action: 'yaml_cf_save_template_settings',
          nonce: yamlCF.nonce,
          template: template,
          enabled: enabled,
        },
        success: function (response) {
          if (response.success) {
            // Update the schema button visibility
            const $row = $checkbox.closest('tr');
            const $cells = $row.find('td');

            // Schema column is always the 4th column (index 3)
            const $schemaCell = $cells.eq(3);

            // Data column is the 5th column (index 4) - only exists in partials table
            const $dataCell = $cells.eq(4);

            if (enabled) {
              const editSchemaUrl =
                yamlCF.admin_url +
                'admin.php?page=yaml-cf-edit-schema&template=' +
                encodeURIComponent(template);
              const hasSchema = response.data && response.data.has_schema;
              const buttonText = hasSchema ? 'Edit Schema' : 'Add Schema';
              const checkmark = hasSchema
                ? ' <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>'
                : '';

              $schemaCell.html(
                '<a href="' +
                  editSchemaUrl +
                  '" class="button">' +
                  buttonText +
                  '</a>' +
                  checkmark
              );

              // If this is a partial (has data column), update it too
              if ($dataCell.length) {
                if (hasSchema) {
                  const manageDataUrl =
                    yamlCF.admin_url +
                    'admin.php?page=yaml-cf-edit-partial&template=' +
                    encodeURIComponent(template);
                  $dataCell.html(
                    '<a href="' +
                      manageDataUrl +
                      '" class="button">Manage Data</a>'
                  );
                } else {
                  $dataCell.html(
                    '<span class="description">Add schema first</span>'
                  );
                }
              }
            } else {
              $schemaCell.html(
                '<span class="description">Enable YAML first</span>'
              );

              // If this is a partial (has data column), update it too
              if ($dataCell.length) {
                $dataCell.html(
                  '<span class="description">Add schema first</span>'
                );
              }
            }

            YamlCF.showMessage('Settings saved successfully', 'success');
          } else {
            $checkbox.prop('checked', !enabled);
            YamlCF.showMessage('Error saving settings', 'error');
          }
        },
        error: function () {
          $checkbox.prop('checked', !enabled);
          YamlCF.showMessage('Error saving settings', 'error');
        },
      });
    },

    openSchemaModal: function () {
      const $button = $(this);
      const template = $button.data('template');
      const templateName = $button.data('name');

      $('#yaml-cf-template-name').text(templateName);
      $('#yaml-cf-current-template').val(template);

      // Load existing schema
      $.ajax({
        url: yamlCF.ajax_url,
        type: 'POST',
        data: {
          action: 'yaml_cf_get_schema',
          nonce: yamlCF.nonce,
          template: template,
        },
        success: function (response) {
          if (response.success) {
            $('#yaml-cf-schema-editor').val(response.data.schema || '');
          }
        },
      });

      $('#yaml-cf-schema-modal').fadeIn(300);
    },

    closeModal: function () {
      $('.yaml-cf-modal').fadeOut(300);
    },

    saveSchema: function () {
      const template = $('#yaml-cf-current-template').val();
      const schema = $('#yaml-cf-schema-editor').val();

      if (!schema.trim()) {
        alert('Please enter a schema');
        return;
      }

      $('.yaml-cf-save-schema').prop('disabled', true).text('Saving...');

      $.ajax({
        url: yamlCF.ajax_url,
        type: 'POST',
        data: {
          action: 'yaml_cf_save_schema',
          nonce: yamlCF.nonce,
          template: template,
          schema: schema,
        },
        success: function (response) {
          if (response.success) {
            YamlCF.showMessage('Schema saved successfully', 'success');
            YamlCF.closeModal();

            // Update the button text to "Edit Schema"
            $('.yaml-cf-edit-schema[data-template="' + template + '"]')
              .text('Edit Schema')
              .after(
                '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>'
              );
          } else {
            YamlCF.showMessage('Error saving schema', 'error');
          }
        },
        error: function () {
          YamlCF.showMessage('Error saving schema', 'error');
        },
        complete: function () {
          $('.yaml-cf-save-schema')
            .prop('disabled', false)
            .text('Save Schema');
        },
      });
    },

    addBlock: function () {
      const $container = $(this).closest('.yaml-cf-block-container');
      const $select = $container.find('.yaml-cf-block-type-select');
      const blockType = $select.val();

      if (!blockType) {
        alert('Please select a block type');
        return;
      }

      const $blockList = $container.find('.yaml-cf-block-list');
      const fieldName = $container.data('field-name');
      const index = $blockList.find('.yaml-cf-block-item').length;

      // Generate unique ID for this block instance
      const uniqueId =
        Date.now() + '_' + Math.random().toString(36).substr(2, 9);

      // Get block definition from schema
      let blockDef = null;
      if (yamlCF.schema && yamlCF.schema.fields) {
        for (let field of yamlCF.schema.fields) {
          if (field.name === fieldName && field.blocks) {
            for (let block of field.blocks) {
              if (block.name === blockType) {
                blockDef = block;
                break;
              }
            }
            break;
          }
        }
      }

      if (!blockDef) {
        alert('Block definition not found');
        return;
      }

      const blockLabel = blockDef.label || blockType;

      // Create new block item
      const $blockItem = $('<div>', {
        class: 'yaml-cf-block-item',
        'data-block-type': blockType,
      });

      const $header = $('<div>', { class: 'yaml-cf-block-header' });
      $header.append($('<strong>').text(blockLabel));
      $header.append(
        $('<button>', {
          type: 'button',
          class: 'button yaml-cf-remove-block',
          text: 'Remove',
        })
      );

      $blockItem.append($header);
      $blockItem.append(
        $('<input>', {
          type: 'hidden',
          name: 'yaml_cf[' + fieldName + '][' + index + '][type]',
          value: blockType,
        })
      );

      // Add fields from block definition
      if (blockDef.fields && blockDef.fields.length > 0) {
        const $fieldsContainer = $('<div>', {
          class: 'yaml-cf-block-fields',
        });

        for (let blockField of blockDef.fields) {
          const $field = $('<div>', { class: 'yaml-cf-field' });
          const blockFieldId = 'ycf_' + uniqueId + '_' + blockField.name;

          $field.append(
            $('<label>', {
              for: blockFieldId,
              text: blockField.label || blockField.name,
            })
          );

          // Render field based on type
          if (blockField.type === 'boolean') {
            $field.append(
              $('<input>', {
                type: 'checkbox',
                name:
                  'yaml_cf[' +
                  fieldName +
                  '][' +
                  index +
                  '][' +
                  blockField.name +
                  ']',
                id: blockFieldId,
                value: '1',
              })
            );
          } else if (blockField.type === 'rich-text') {
            // For rich-text, we need to use WordPress editor which requires page reload
            $field.append(
              $('<div>', {
                style:
                  'padding: 10px; background: #f0f0f0; border: 1px dashed #ccc;',
                text: 'Rich text editor will appear after saving the page.',
              })
            );
            // Add hidden input to preserve the field structure
            $field.append(
              $('<input>', {
                type: 'hidden',
                name:
                  'yaml_cf[' +
                  fieldName +
                  '][' +
                  index +
                  '][' +
                  blockField.name +
                  ']',
                value: '',
              })
            );
          } else if (
            blockField.type === 'text' ||
            blockField.type === 'textarea'
          ) {
            $field.append(
              $('<textarea>', {
                name:
                  'yaml_cf[' +
                  fieldName +
                  '][' +
                  index +
                  '][' +
                  blockField.name +
                  ']',
                id: blockFieldId,
                rows: 5,
                class: 'large-text',
              })
            );
          } else if (blockField.type === 'code') {
            const options = blockField.options || {};
            const language = options.language || 'html';
            $field.append(
              $('<textarea>', {
                name:
                  'yaml_cf[' +
                  fieldName +
                  '][' +
                  index +
                  '][' +
                  blockField.name +
                  ']',
                id: blockFieldId,
                rows: 10,
                class: 'large-text code',
                'data-language': language,
              })
            );
          } else if (blockField.type === 'number') {
            const options = blockField.options || {};
            $field.append(
              $('<input>', {
                type: 'number',
                name:
                  'yaml_cf[' +
                  fieldName +
                  '][' +
                  index +
                  '][' +
                  blockField.name +
                  ']',
                id: blockFieldId,
                class: 'small-text',
                min: options.min || '',
                max: options.max || '',
              })
            );
          } else if (blockField.type === 'date') {
            const options = blockField.options || {};
            const hasTime = options.time || false;
            $field.append(
              $('<input>', {
                type: hasTime ? 'datetime-local' : 'date',
                name:
                  'yaml_cf[' +
                  fieldName +
                  '][' +
                  index +
                  '][' +
                  blockField.name +
                  ']',
                id: blockFieldId,
              })
            );
          } else if (blockField.type === 'select') {
            const options = blockField.options || {};
            const multiple = blockField.multiple || false;
            const values = blockField.values || [];

            const $select = $('<select>', {
              name:
                'yaml_cf[' +
                fieldName +
                '][' +
                index +
                '][' +
                blockField.name +
                ']' +
                (multiple ? '[]' : ''),
              id: blockFieldId,
              multiple: multiple,
            });

            $select.append($('<option>', { value: '', text: '-- Select --' }));

            if (Array.isArray(values)) {
              values.forEach(function (option) {
                const optValue =
                  typeof option === 'object' ? option.value || '' : option;
                const optLabel =
                  typeof option === 'object' ? option.label || optValue : option;
                $select.append(
                  $('<option>', { value: optValue, text: optLabel })
                );
              });
            }

            $field.append($select);
          } else if (blockField.type === 'image') {
            // Image upload field
            $field.append(
              $('<input>', {
                type: 'hidden',
                name:
                  'yaml_cf[' +
                  fieldName +
                  '][' +
                  index +
                  '][' +
                  blockField.name +
                  ']',
                id: blockFieldId,
                value: '',
              })
            );
            const $mediaButtons = $('<div>', {
              class: 'yaml-cf-media-buttons',
            });
            $mediaButtons.append(
              $('<button>', {
                type: 'button',
                class: 'button yaml-cf-upload-image',
                'data-target': blockFieldId,
                text: 'Upload Image',
              })
            );
            $field.append($mediaButtons);
          } else if (blockField.type === 'file') {
            // File upload field
            $field.append(
              $('<input>', {
                type: 'hidden',
                name:
                  'yaml_cf[' +
                  fieldName +
                  '][' +
                  index +
                  '][' +
                  blockField.name +
                  ']',
                id: blockFieldId,
                value: '',
              })
            );
            const $mediaButtons = $('<div>', {
              class: 'yaml-cf-media-buttons',
            });
            $mediaButtons.append(
              $('<button>', {
                type: 'button',
                class: 'button yaml-cf-upload-file',
                'data-target': blockFieldId,
                text: 'Upload File',
              })
            );
            $field.append($mediaButtons);
          } else if (blockField.type === 'string') {
            const options = blockField.options || {};
            $field.append(
              $('<input>', {
                type: 'text',
                name:
                  'yaml_cf[' +
                  fieldName +
                  '][' +
                  index +
                  '][' +
                  blockField.name +
                  ']',
                id: blockFieldId,
                class: 'regular-text',
                minlength: options.minlength || '',
                maxlength: options.maxlength || '',
              })
            );
          } else {
            // Default to text input for unknown types
            $field.append(
              $('<input>', {
                type: 'text',
                name:
                  'yaml_cf[' +
                  fieldName +
                  '][' +
                  index +
                  '][' +
                  blockField.name +
                  ']',
                id: blockFieldId,
                class: 'regular-text',
              })
            );
          }

          $fieldsContainer.append($field);
        }

        $blockItem.append($fieldsContainer);
      }

      $blockList.append($blockItem);
      $select.val('');
    },

    removeBlock: function () {
      if (
        confirm(
          'Are you sure you want to remove this block? Remember to update the page to save changes.'
        )
      ) {
        $(this)
          .closest('.yaml-cf-block-item')
          .fadeOut(300, function () {
            $(this).remove();
            // Re-index remaining blocks
            YamlCF.reindexBlocks();
          });
      }
    },

    reindexBlocks: function () {
      $('.yaml-cf-block-container').each(function () {
        const fieldName = $(this).data('field-name');
        $(this)
          .find('.yaml-cf-block-item')
          .each(function (index) {
            // Update input names with new index
            $(this)
              .find('input, textarea, select')
              .each(function () {
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

    initMediaUploader: function () {
      // Image Upload
      $(document).on('click', '.yaml-cf-upload-image', function (e) {
        e.preventDefault();

        const $button = $(this);
        const targetId = $button.data('target');

        // Always create a new media uploader instance to avoid target conflicts
        const mediaUploader = wp.media({
          title: 'Select Image',
          button: {
            text: 'Use This Image',
          },
          multiple: false,
          library: {
            type: 'image',
          },
        });

        mediaUploader.on('select', function () {
          const attachment = mediaUploader
            .state()
            .get('selection')
            .first()
            .toJSON();
          // Store attachment ID instead of URL
          $('#' + targetId).val(attachment.id);

          // Update preview
          const $preview = $button.siblings('.yaml-cf-image-preview');
          if ($preview.length) {
            $preview.find('img').attr('src', attachment.url);
          } else {
            $button.after(
              '<div class="yaml-cf-image-preview">' +
                '<img src="' +
                attachment.url +
                '" style="max-width: 200px; display: block; margin-top: 10px;" />' +
                '</div>'
            );
          }

          // Add clear button if it doesn't exist
          const $buttonsDiv = $button.closest('.yaml-cf-media-buttons');
          if (!$buttonsDiv.find('.yaml-cf-clear-media').length) {
            $buttonsDiv.append(
              $('<button>', {
                type: 'button',
                class: 'button yaml-cf-clear-media',
                'data-target': targetId,
                text: 'Clear',
              })
            );
          }
        });

        mediaUploader.open();
      });

      // File Upload
      $(document).on('click', '.yaml-cf-upload-file', function (e) {
        e.preventDefault();

        const $button = $(this);
        const targetId = $button.data('target');

        // Always create a new media uploader instance to avoid target conflicts
        const mediaUploader = wp.media({
          title: 'Select File',
          button: {
            text: 'Use This File',
          },
          multiple: false,
        });

        mediaUploader.on('select', function () {
          const attachment = mediaUploader
            .state()
            .get('selection')
            .first()
            .toJSON();
          // Store attachment ID instead of URL
          $('#' + targetId).val(attachment.id);

          // Update file name display
          const $fileDisplay = $button.siblings('.yaml-cf-file-name');
          if ($fileDisplay.length) {
            $fileDisplay.text(attachment.filename);
          } else {
            $button.after(
              '<div class="yaml-cf-file-name">' +
                attachment.filename +
                '</div>'
            );
          }

          // Add clear button if it doesn't exist
          const $buttonsDiv = $button.closest('.yaml-cf-media-buttons');
          if (!$buttonsDiv.find('.yaml-cf-clear-media').length) {
            $buttonsDiv.append(
              $('<button>', {
                type: 'button',
                class: 'button yaml-cf-clear-media',
                'data-target': targetId,
                text: 'Clear',
              })
            );
          }
        });

        mediaUploader.open();
      });
    },

    clearMedia: function (e) {
      e.preventDefault();

      const $button = $(this);
      const targetId = $button.data('target');
      const $field = $('#' + targetId);

      if (
        !confirm(
          'Are you sure you want to clear this file? Remember to update the page to save changes.'
        )
      ) {
        return;
      }

      // Clear the hidden input value
      $field.val('');

      // Remove the preview/filename display
      $button
        .closest('.yaml-cf-field')
        .find('.yaml-cf-image-preview')
        .remove();
      $button
        .closest('.yaml-cf-field')
        .find('.yaml-cf-file-name')
        .remove();

      // Remove the clear button itself
      $button.remove();
    },

    uploadImagePartial: function (e) {
      e.preventDefault();

      const $button = $(this);
      const targetId = $button.data('target');

      const mediaUploader = wp.media({
        title: 'Select Image',
        button: {
          text: 'Use This Image',
        },
        multiple: false,
        library: {
          type: 'image',
        },
      });

      mediaUploader.on('select', function () {
        const attachment = mediaUploader
          .state()
          .get('selection')
          .first()
          .toJSON();
        const $input = $('#' + targetId);
        // Store attachment ID instead of URL
        $input.val(attachment.id);

        // Update/add preview
        const $field = $button.closest('.yaml-cf-field');
        let $preview = $field.find('.yaml-cf-image-preview');
        if ($preview.length) {
          $preview.find('img').attr('src', attachment.url);
        } else {
          $field.append(
            $('<div>', {
              class: 'yaml-cf-image-preview',
              html:
                '<img src="' +
                attachment.url +
                '" style="max-width: 200px; display: block; margin-top: 10px;" />',
            })
          );
        }

        // Add clear button if it doesn't exist
        const $buttonsDiv = $button.closest('.yaml-cf-media-buttons');
        if (!$buttonsDiv.find('.yaml-cf-clear-media').length) {
          $buttonsDiv.append(
            $('<button>', {
              type: 'button',
              class: 'button yaml-cf-clear-media',
              'data-target': targetId,
              text: 'Clear',
            })
          );
        }
      });

      mediaUploader.open();
    },

    uploadFilePartial: function (e) {
      e.preventDefault();

      const $button = $(this);
      const targetId = $button.data('target');

      const mediaUploader = wp.media({
        title: 'Select File',
        button: {
          text: 'Use This File',
        },
        multiple: false,
      });

      mediaUploader.on('select', function () {
        const attachment = mediaUploader
          .state()
          .get('selection')
          .first()
          .toJSON();
        const $input = $('#' + targetId);
        // Store attachment ID instead of URL
        $input.val(attachment.id);

        // Update/add file name display
        const $field = $button.closest('.yaml-cf-field');
        let $fileDisplay = $field.find('.yaml-cf-file-name');
        if ($fileDisplay.length) {
          $fileDisplay.text(attachment.filename);
        } else {
          $field.append(
            $('<div>', {
              class: 'yaml-cf-file-name',
              text: attachment.filename,
            })
          );
        }

        // Add clear button if it doesn't exist
        const $buttonsDiv = $button.closest('.yaml-cf-media-buttons');
        if (!$buttonsDiv.find('.yaml-cf-clear-media').length) {
          $buttonsDiv.append(
            $('<button>', {
              type: 'button',
              class: 'button yaml-cf-clear-media',
              'data-target': targetId,
              text: 'Clear',
            })
          );
        }
      });

      mediaUploader.open();
    },

    resetAllData: function (e) {
      e.preventDefault();

      const $button = $(this);

      if (
        !confirm(
          '⚠️ WARNING: This will clear ALL custom field data for this page.\n\nThis action cannot be undone. You will need to save the page to make this permanent.\n\nAre you sure you want to continue?'
        )
      ) {
        return;
      }

      // Reset all fields in the meta box
      $('#yaml-cf-meta-box .yaml-cf-fields')
        .find('input, textarea, select')
        .each(function () {
          const $input = $(this);
          const type = $input.attr('type');

          if (type === 'checkbox') {
            $input.prop('checked', false);
          } else if (
            type === 'hidden' &&
            ($input
              .closest('.yaml-cf-field')
              .find('.yaml-cf-upload-image').length ||
              $input
                .closest('.yaml-cf-field')
                .find('.yaml-cf-upload-file').length)
          ) {
            // Clear image/file fields
            $input.val('');
          } else if ($input.is('select')) {
            $input.prop('selectedIndex', 0);
          } else if (
            !type ||
            type === 'text' ||
            type === 'number' ||
            type === 'date' ||
            type === 'datetime-local' ||
            $input.is('textarea')
          ) {
            $input.val('');
          }
        });

      // Clear image previews and file names
      $('#yaml-cf-meta-box .yaml-cf-image-preview').remove();
      $('#yaml-cf-meta-box .yaml-cf-file-name').remove();
      $('#yaml-cf-meta-box .yaml-cf-clear-media').remove();

      // Clear WordPress editors (if any)
      if (typeof tinymce !== 'undefined') {
        $('#yaml-cf-meta-box .yaml-cf-fields')
          .find('textarea')
          .each(function () {
            const editorId = $(this).attr('id');
            if (editorId && tinymce.get(editorId)) {
              tinymce.get(editorId).setContent('');
            }
          });
      }

      // Remove all blocks
      $('#yaml-cf-meta-box .yaml-cf-block-item').remove();

      alert(
        'All custom field data has been cleared. Remember to save the page to make this change permanent.'
      );
    },

    openPartialDataModal: function () {
      const $button = $(this);
      const template = $button.data('template');
      const templateName = $button.data('name');

      $('#yaml-cf-partial-name').text(templateName);
      $('#yaml-cf-current-partial').val(template);

      // Load schema and existing data
      $.ajax({
        url: yamlCF.ajax_url,
        type: 'POST',
        data: {
          action: 'yaml_cf_get_partial_data',
          nonce: yamlCF.nonce,
          template: template,
        },
        success: function (response) {
          if (response.success) {
            YamlCF.renderPartialFields(
              response.data.schema,
              response.data.data || {}
            );
          } else {
            alert('Error loading partial data');
          }
        },
        error: function () {
          alert('Error loading partial data');
        },
      });

      $('#yaml-cf-partial-data-modal').fadeIn(300);
    },

    renderPartialFields: function (schema, data) {
      const $container = $('#yaml-cf-partial-fields');
      $container.empty();

      if (!schema || !schema.fields) {
        $container.html('<p>No schema defined for this partial.</p>');
        return;
      }

      schema.fields.forEach(function (field) {
        const fieldValue = data[field.name] || field.default || '';
        const fieldId = 'partial_' + field.name;
        const $fieldDiv = $('<div>', { class: 'yaml-cf-field' });

        $fieldDiv.append(
          $('<label>', {
            for: fieldId,
            text: field.label || field.name,
          })
        );

        // Render field based on type
        switch (field.type) {
          case 'image':
            const $imageInput = $('<input>', {
              type: 'hidden',
              name: 'partial_data[' + field.name + ']',
              id: fieldId,
              value: fieldValue,
            });
            const $imageButtonsDiv = $('<div>', {
              class: 'yaml-cf-media-buttons',
            });
            const $imageUploadBtn = $('<button>', {
              type: 'button',
              class: 'button yaml-cf-upload-image-partial',
              'data-target': fieldId,
              text: 'Upload Image',
            });
            $imageButtonsDiv.append($imageUploadBtn);

            if (fieldValue) {
              const $imageClearBtn = $('<button>', {
                type: 'button',
                class: 'button yaml-cf-clear-media',
                'data-target': fieldId,
                text: 'Clear',
              });
              $imageButtonsDiv.append($imageClearBtn);
            }

            $fieldDiv.append($imageInput).append($imageButtonsDiv);

            if (fieldValue) {
              $fieldDiv.append(
                $('<div>', {
                  class: 'yaml-cf-image-preview',
                  html:
                    '<img src="' +
                    fieldValue +
                    '" style="max-width: 200px; display: block; margin-top: 10px;" />',
                })
              );
            }
            break;

          case 'file':
            const $fileInput = $('<input>', {
              type: 'hidden',
              name: 'partial_data[' + field.name + ']',
              id: fieldId,
              value: fieldValue,
            });
            const $fileButtonsDiv = $('<div>', {
              class: 'yaml-cf-media-buttons',
            });
            const $fileUploadBtn = $('<button>', {
              type: 'button',
              class: 'button yaml-cf-upload-file-partial',
              'data-target': fieldId,
              text: 'Upload File',
            });
            $fileButtonsDiv.append($fileUploadBtn);

            if (fieldValue) {
              const $fileClearBtn = $('<button>', {
                type: 'button',
                class: 'button yaml-cf-clear-media',
                'data-target': fieldId,
                text: 'Clear',
              });
              $fileButtonsDiv.append($fileClearBtn);
            }

            $fieldDiv.append($fileInput).append($fileButtonsDiv);

            if (fieldValue) {
              const fileName = fieldValue.split('/').pop();
              $fieldDiv.append(
                $('<div>', {
                  class: 'yaml-cf-file-name',
                  text: fileName,
                })
              );
            }
            break;
          case 'boolean':
            $fieldDiv.append(
              $('<input>', {
                type: 'checkbox',
                name: 'partial_data[' + field.name + ']',
                id: fieldId,
                value: '1',
                checked: fieldValue == 1,
              })
            );
            break;

          case 'string':
            const options = field.options || {};
            $fieldDiv.append(
              $('<input>', {
                type: 'text',
                name: 'partial_data[' + field.name + ']',
                id: fieldId,
                value: fieldValue,
                class: 'regular-text',
                maxlength: options.maxlength || '',
                minlength: options.minlength || '',
              })
            );
            break;

          case 'text':
            const textOptions = field.options || {};
            $fieldDiv.append(
              $('<textarea>', {
                name: 'partial_data[' + field.name + ']',
                id: fieldId,
                rows: 5,
                class: 'large-text',
                maxlength: textOptions.maxlength || '',
                text: fieldValue,
              })
            );
            break;

          case 'number':
            const numOptions = field.options || {};
            $fieldDiv.append(
              $('<input>', {
                type: 'number',
                name: 'partial_data[' + field.name + ']',
                id: fieldId,
                value: fieldValue,
                class: 'small-text',
                min: numOptions.min || '',
                max: numOptions.max || '',
              })
            );
            break;

          case 'select':
            const $select = $('<select>', {
              name: 'partial_data[' + field.name + ']',
              id: fieldId,
            });
            $select.append($('<option>', { value: '', text: '-- Select --' }));

            if (field.values && Array.isArray(field.values)) {
              field.values.forEach(function (option) {
                const optValue = option.value || option;
                const optLabel = option.label || optValue;
                $select.append(
                  $('<option>', {
                    value: optValue,
                    text: optLabel,
                    selected: fieldValue === optValue,
                  })
                );
              });
            }
            $fieldDiv.append($select);
            break;

          case 'date':
            const dateOptions = field.options || {};
            const hasTime = dateOptions.time || false;
            $fieldDiv.append(
              $('<input>', {
                type: hasTime ? 'datetime-local' : 'date',
                name: 'partial_data[' + field.name + ']',
                id: fieldId,
                value: fieldValue,
              })
            );
            break;

          default:
            $fieldDiv.append(
              $('<input>', {
                type: 'text',
                name: 'partial_data[' + field.name + ']',
                id: fieldId,
                value: fieldValue,
                class: 'regular-text',
              })
            );
        }

        $container.append($fieldDiv);
      });
    },

    savePartialData: function () {
      const template = $('#yaml-cf-current-partial').val();
      const $fields = $('#yaml-cf-partial-fields').find(
        'input, textarea, select'
      );
      const data = {};

      $fields.each(function () {
        const $field = $(this);
        const name = $field.attr('name');
        if (name && name.startsWith('partial_data[')) {
          const fieldName = name.match(/partial_data\[([^\]]+)\]/)[1];
          if ($field.attr('type') === 'checkbox') {
            data[fieldName] = $field.is(':checked') ? 1 : 0;
          } else {
            data[fieldName] = $field.val();
          }
        }
      });

      $('.yaml-cf-save-partial-data')
        .prop('disabled', true)
        .text('Saving...');

      $.ajax({
        url: yamlCF.ajax_url,
        type: 'POST',
        data: {
          action: 'yaml_cf_save_partial_data',
          nonce: yamlCF.nonce,
          template: template,
          data: JSON.stringify(data),
        },
        success: function (response) {
          if (response.success) {
            YamlCF.showMessage(
              'Partial data saved successfully',
              'success'
            );
            YamlCF.closeModal();
          } else {
            YamlCF.showMessage('Error saving partial data', 'error');
          }
        },
        error: function () {
          YamlCF.showMessage('Error saving partial data', 'error');
        },
        complete: function () {
          $('.yaml-cf-save-partial-data')
            .prop('disabled', false)
            .text('Save Data');
        },
      });
    },

    exportSettings: function (e) {
      e.preventDefault();
      const $button = $(this);
      const originalText = $button.html();

      $button
        .prop('disabled', true)
        .html(
          '<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear;"></span> Exporting...'
        );

      $.ajax({
        url: yamlCF.ajax_url,
        type: 'POST',
        data: {
          action: 'yaml_cf_export_settings',
          nonce: yamlCF.nonce,
        },
        success: function (response) {
          if (response.success) {
            // Create JSON file and download
            const dataStr = JSON.stringify(response.data, null, 2);
            const dataBlob = new Blob([dataStr], { type: 'application/json' });
            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement('a');
            const timestamp = new Date()
              .toISOString()
              .replace(/[:.]/g, '-')
              .slice(0, -5);
            link.href = url;
            link.download = 'yaml-cf-settings-' + timestamp + '.json';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);

            YamlCF.showMessage('Settings exported successfully', 'success');
          } else {
            YamlCF.showMessage(
              'Error exporting settings: ' + (response.data || 'Unknown error'),
              'error'
            );
          }
        },
        error: function () {
          YamlCF.showMessage('AJAX error occurred during export', 'error');
        },
        complete: function () {
          $button.prop('disabled', false).html(originalText);
        },
      });
    },

    triggerImport: function (e) {
      e.preventDefault();
      $('#yaml-cf-import-file').click();
    },

    importSettings: function (e) {
      const file = e.target.files[0];
      if (!file) return;

      // Validate file type
      if (!file.name.endsWith('.json')) {
        YamlCF.showMessage('Please select a valid JSON file', 'error');
        return;
      }

      // Confirm import
      const confirmMsg =
        'This will import settings and may overwrite existing schemas.\n\n' +
        'Choose:\n' +
        'OK = Replace all settings\n' +
        'Cancel = Merge with existing settings\n\n' +
        'Continue?';

      if (!confirm(confirmMsg)) {
        // User wants to merge
        const mergeConfirm = confirm(
          'Merge imported settings with existing settings?'
        );
        if (!mergeConfirm) {
          e.target.value = ''; // Reset file input
          return;
        }
      }

      const merge = !confirm(
        'Replace all existing settings? (Cancel to merge instead)'
      );

      const reader = new FileReader();
      reader.onload = function (evt) {
        try {
          const importData = JSON.parse(evt.target.result);

          $.ajax({
            url: yamlCF.ajax_url,
            type: 'POST',
            data: {
              action: 'yaml_cf_import_settings',
              nonce: yamlCF.nonce,
              data: JSON.stringify(importData),
              merge: merge,
            },
            success: function (response) {
              if (response.success) {
                const info = response.data;
                let message = 'Settings imported successfully!';
                if (info.imported_from && info.imported_from !== 'unknown') {
                  message += '\n\nImported from: ' + info.imported_from;
                }
                if (info.exported_at && info.exported_at !== 'unknown') {
                  message += '\nExported at: ' + info.exported_at;
                }
                alert(message);
                YamlCF.showMessage(
                  'Settings imported successfully',
                  'success'
                );

                // Reload page to show updated settings
                setTimeout(function () {
                  window.location.reload();
                }, 1500);
              } else {
                YamlCF.showMessage(
                  'Error importing settings: ' +
                    (response.data || 'Unknown error'),
                  'error'
                );
              }
            },
            error: function () {
              YamlCF.showMessage(
                'AJAX error occurred during import',
                'error'
              );
            },
            complete: function () {
              // Reset file input
              $('#yaml-cf-import-file').val('');
            },
          });
        } catch (err) {
          YamlCF.showMessage('Invalid JSON file: ' + err.message, 'error');
          $('#yaml-cf-import-file').val('');
        }
      };

      reader.readAsText(file);
    },

    initMetaBoxChangeTracking: function () {
      const self = this;
      const $metaBox = $('#yaml-cf-meta-box');

      // Only run on post editor
      if (!$metaBox.length) return;

      // Capture original meta box state
      function captureMetaBoxState() {
        const data = {};
        $metaBox
          .find('.yaml-cf-fields')
          .find('input, textarea, select')
          .each(function () {
            const $field = $(this);
            const name = $field.attr('name');
            if (!name) return;

            if ($field.attr('type') === 'checkbox') {
              data[name] = $field.is(':checked');
            } else if ($field.is('select') && $field.prop('multiple')) {
              data[name] = JSON.stringify($field.val() || []);
            } else {
              data[name] = $field.val();
            }
          });
        return data;
      }

      // Check for changes
      function checkMetaBoxChanges() {
        const currentData = captureMetaBoxState();
        const changed =
          JSON.stringify(self.originalMetaBoxData) !==
          JSON.stringify(currentData);

        if (changed !== self.hasMetaBoxChanges) {
          self.hasMetaBoxChanges = changed;
          toggleMetaBoxIndicator(changed);

          // Integrate with WordPress's own save warning
          if (changed) {
            // Mark WordPress form as dirty
            if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
              // Gutenberg
              wp.data
                .dispatch('core/editor')
                .editPost({ meta: { _ycf_changed: Date.now() } });
            } else {
              // Classic editor - trigger WordPress's own warning
              $('#post').trigger('change');
            }
          }
        }
      }

      // Show/hide indicator
      function toggleMetaBoxIndicator(show) {
        if (show) {
          YamlCF.showMessage(
            'You have unsaved changes in YAML Custom Fields fields',
            'warning',
            true
          );
        } else {
          YamlCF.hideMessage('warning');
        }
      }

      // Capture initial state after page loads
      setTimeout(function () {
        self.originalMetaBoxData = captureMetaBoxState();
      }, 1000);

      // Watch for changes in meta box fields
      $metaBox.on('input change', 'input, textarea, select', function () {
        checkMetaBoxChanges();
      });

      // Clear changes flag on form submit/save
      $('form#post').on('submit', function () {
        self.hasMetaBoxChanges = false;
        toggleMetaBoxIndicator(false);
      });

      // Also listen for Gutenberg save
      if (typeof wp !== 'undefined' && wp.data && wp.data.subscribe) {
        let wasSaving = false;
        wp.data.subscribe(function () {
          const isSaving = wp.data.select('core/editor').isSavingPost();
          if (wasSaving && !isSaving) {
            // Save just completed
            self.hasMetaBoxChanges = false;
            self.originalMetaBoxData = captureMetaBoxState();
            toggleMetaBoxIndicator(false);
          }
          wasSaving = isSaving;
        });
      }
    },

    showMessage: function (message, type, persistent) {
      // Create notification container if it doesn't exist
      let $container = $('#yaml-cf-notifications');
      if (!$container.length) {
        $container = $('<div>', {
          id: 'yaml-cf-notifications',
        });
        $('body').append($container);
      }

      // Remove existing message of the same type if persistent
      if (persistent) {
        $container.find('.yaml-cf-message.' + type).remove();
      }

      const $message = $('<div>', {
        class: 'yaml-cf-message ' + type,
        text: message,
        'data-type': type,
      });

      $container.append($message);

      // Auto-hide after 3 seconds unless persistent
      if (!persistent) {
        setTimeout(function () {
          $message.fadeOut(300, function () {
            $(this).remove();
          });
        }, 3000);
      }
    },

    hideMessage: function (type) {
      $('#yaml-cf-notifications .yaml-cf-message.' + type).fadeOut(
        300,
        function () {
          $(this).remove();
        }
      );
    },

    showSnippetPopover: function (e) {
      const $button = $(this);
      const popoverId = $button.data('popover');

      if (!popoverId) {
        return;
      }

      const $popover = $('#' + popoverId);

      if (!$popover.length) {
        return;
      }

      // Clear any hide timeout
      if ($popover.data('hideTimeout')) {
        clearTimeout($popover.data('hideTimeout'));
      }

      // Show the popover
      $popover.addClass('visible');

      // Add hover handlers to the popover itself
      $popover.off('mouseenter mouseleave');

      $popover.on('mouseenter', function () {
        // Clear hide timeout when entering popover
        if ($(this).data('hideTimeout')) {
          clearTimeout($(this).data('hideTimeout'));
        }
        $(this).addClass('visible');
      });

      $popover.on('mouseleave', function () {
        const $self = $(this);
        const hideTimeout = setTimeout(function () {
          $self.removeClass('visible');
        }, 100);
        $self.data('hideTimeout', hideTimeout);
      });
    },

    hideSnippetPopover: function (e) {
      const $button = $(this);
      const popoverId = $button.data('popover');

      if (!popoverId) {
        return;
      }

      const $popover = $('#' + popoverId);

      // Delay hiding to allow moving to popover
      const hideTimeout = setTimeout(function () {
        $popover.removeClass('visible');
      }, 100);

      $popover.data('hideTimeout', hideTimeout);
    },

    copySnippet: function (e) {
      e.preventDefault();
      e.stopPropagation();

      const $button = $(this);
      const snippet = $button.data('snippet');
      const popoverId = $button.data('popover');

      if (!snippet) {
        return;
      }

      // Hide the popover immediately
      if (popoverId) {
        const $popover = $('#' + popoverId);
        $popover.removeClass('visible');
        if ($popover.data('hideTimeout')) {
          clearTimeout($popover.data('hideTimeout'));
        }
      }

      // Copy to clipboard
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(snippet).then(
          function () {
            YamlCF.showCopyFeedback($button);
          },
          function () {
            // Fallback for older browsers
            YamlCF.fallbackCopyToClipboard(snippet, $button);
          }
        );
      } else {
        // Fallback for older browsers
        YamlCF.fallbackCopyToClipboard(snippet, $button);
      }
    },

    fallbackCopyToClipboard: function (text, $button) {
      const $temp = $('<textarea>');
      $('body').append($temp);
      $temp.val(text).select();
      try {
        document.execCommand('copy');
        YamlCF.showCopyFeedback($button);
      } catch (err) {
        YamlCF.showMessage('Failed to copy snippet', 'error');
      }
      $temp.remove();
    },

    showCopyFeedback: function ($button) {
      // Remove existing tooltips
      $('.yaml-cf-snippet-tooltip').remove();

      // Add visual feedback to button
      $button.addClass('copied');
      setTimeout(function () {
        $button.removeClass('copied');
      }, 2000);

      // Create success tooltip
      const $tooltip = $('<div>', {
        class: 'yaml-cf-snippet-tooltip',
        text: 'Copied!',
      });

      // Position tooltip
      const buttonOffset = $button.offset();
      const buttonHeight = $button.outerHeight();
      const buttonWidth = $button.outerWidth();

      $('body').append($tooltip);

      const tooltipWidth = $tooltip.outerWidth();
      const tooltipLeft = buttonOffset.left - tooltipWidth / 2 + buttonWidth / 2;
      const tooltipTop = buttonOffset.top + buttonHeight + 8;

      $tooltip.css({
        left: tooltipLeft + 'px',
        top: tooltipTop + 'px',
      });

      // Auto-hide tooltip
      setTimeout(function () {
        $tooltip.fadeOut(300, function () {
          $(this).remove();
        });
      }, 2000);

      // Show success message
      YamlCF.showMessage('Code snippet copied to clipboard!', 'success');
    },
  };

  // Initialize on document ready
  $(document).ready(function () {
    YamlCF.init();

    // Fix duplicate nonce IDs created by multiple wp_editor() instances
    YamlCF.removeDuplicateNonces();
  });

  // Remove duplicate nonce fields with the same ID
  YamlCF.removeDuplicateNonces = function() {
    const seenIds = {};
    $('input[type="hidden"]').each(function() {
      const id = $(this).attr('id');
      if (id && id.includes('nonce')) {
        if (seenIds[id]) {
          // Remove duplicate
          $(this).remove();
        } else {
          // Mark as seen
          seenIds[id] = true;
        }
      }
    });
  };

  // Make YamlCF globally accessible
  window.YamlCF = YamlCF;
})(jQuery);
