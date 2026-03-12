<?php
/**
 * Shortcodes Admin Page Template
 *
 * Renders three views:
 *  - List view: WP list table of saved shortcodes
 *  - New view:  Two-panel builder (module/type picker + settings form)
 *  - Edit view: Settings form for an existing shortcode
 *
 * Variables vary by view — see inline comments.
 */

defined('ABSPATH') || exit;

$is_new_view  = isset($action) && $action === 'new';
$is_edit_view = isset($action) && $action === 'edit';
?>

<div class="wrap simplepco-shortcodes-admin">

<?php if ($is_new_view): ?>
    <?php // ================================================================
          // ADD NEW — TWO-PANEL BUILDER
          //
          // Variables:
          //   $types    (array)  — all shortcode type definitions
          //   $modules  (array)  — module_key => name
          //   $page_url (string) — base admin URL for this page
          // ================================================================ ?>

    <h1>
        <?php _e('Add New Shortcode', 'simplepco-online'); ?>
        <a href="<?php echo esc_url($page_url); ?>" class="page-title-action"><?php _e('Back to Shortcodes', 'simplepco-online'); ?></a>
    </h1>
    <hr class="wp-header-end">

    <?php
    // Group types by module
    $grouped = [];
    foreach ($types as $slug => $type) {
        $grouped[$type['module']][$slug] = $type;
    }
    ?>

    <div class="simplepco-builder">
        <!-- Left Panel -->
        <div class="simplepco-builder-left">
            <h3><?php _e('Select a Module', 'simplepco-online'); ?></h3>
            <select id="simplepco-module-select">
                <option value=""><?php _e('Choose...', 'simplepco-online'); ?></option>
                <?php foreach ($modules as $mod_key => $mod_name): ?>
                    <option value="<?php echo esc_attr($mod_key); ?>"><?php echo esc_html($mod_name); ?></option>
                <?php endforeach; ?>
            </select>

            <h3><?php _e('Shortcode List', 'simplepco-online'); ?></h3>

            <div id="simplepco-type-lists">
                <p class="simplepco-builder-hint" id="simplepco-type-hint"><?php _e('Select a module above.', 'simplepco-online'); ?></p>

                <?php foreach ($grouped as $mod_key => $mod_types):
                    // Separate regular types from addon types
                    $regular_types = array_filter($mod_types, function($t) { return empty($t['is_addon']); });
                    $addon_types = array_filter($mod_types, function($t) { return !empty($t['is_addon']); });
                ?>
                    <ul class="simplepco-type-list" data-module="<?php echo esc_attr($mod_key); ?>">
                        <?php foreach ($regular_types as $slug => $type): ?>
                            <li>
                                <a href="#" class="simplepco-type-link" data-type="<?php echo esc_attr($slug); ?>">
                                    <?php echo esc_html($type['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <?php if (!empty($addon_types)): ?>
                            <li class="simplepco-type-list-addon-separator" aria-hidden="true">&nbsp;</li>
                            <li class="simplepco-type-list-addon-header"><?php _e('Addon', 'simplepco-online'); ?></li>
                            <?php foreach ($addon_types as $slug => $type): ?>
                                <li>
                                    <a href="#" class="simplepco-type-link" data-type="<?php echo esc_attr($slug); ?>">
                                        <?php echo esc_html($type['name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="simplepco-builder-right">
            <div id="simplepco-builder-placeholder">
                <p><?php _e('Select a shortcode type to configure.', 'simplepco-online'); ?></p>
            </div>

            <form method="post" action="" id="simplepco-builder-form" style="display:none;">
                <?php wp_nonce_field('simplepco_save_module_shortcode'); ?>
                <input type="hidden" name="simplepco_save_module_shortcode" value="1">
                <input type="hidden" name="shortcode_id" value="0">
                <input type="hidden" name="shortcode_type" id="simplepco-builder-type" value="">

                <h3><?php _e('Shortcode Settings', 'simplepco-online'); ?></h3>

                <!-- Description (always visible) -->
                <div class="simplepco-field">
                    <label for="shortcode_description"><?php _e('Description', 'simplepco-online'); ?></label>
                    <input type="text" id="shortcode_description" name="shortcode_description" class="large-text"
                           >
                </div>

                <!-- Module-specific field groups (one per type, toggled by JS) -->
                <?php foreach ($types as $slug => $type_def): ?>
                    <?php if (!empty($type_def['fields'])): ?>
                        <div class="simplepco-type-fields" data-type="<?php echo esc_attr($slug); ?>">
                            <?php foreach ($type_def['fields'] as $field):
                                $field_id = $slug . '_' . $field['key'];
                                $default  = $type_def['defaults'][$field['key']] ?? '';
                            ?>
                                <div class="simplepco-field"<?php if (!empty($field['show_when'])): ?> data-show-when-field="<?php echo esc_attr($slug . '_' . $field['show_when']['field']); ?>" data-show-when-value="<?php echo esc_attr($field['show_when']['value']); ?>" style="display:none;"<?php endif; ?>>
                                    <?php if ($field['type'] === 'checkbox'): ?>
                                        <label>
                                            <input type="checkbox" name="<?php echo esc_attr($field['key']); ?>"
                                                   data-field="<?php echo esc_attr($slug); ?>" value="1"
                                                   <?php checked($default); ?>>
                                            <?php echo esc_html($field['label']); ?>
                                        </label>
                                    <?php else: ?>
                                        <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($field['label']); ?></label>

                                        <?php if ($field['type'] === 'select'): ?>
                                            <select id="<?php echo esc_attr($field_id); ?>"
                                                    name="<?php echo esc_attr($field['key']); ?>"
                                                    data-field="<?php echo esc_attr($slug); ?>">
                                                <?php foreach ($field['options'] as $ov => $ol): ?>
                                                    <option value="<?php echo esc_attr($ov); ?>" <?php selected($default, $ov); ?>>
                                                        <?php echo esc_html($ol); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>

                                        <?php elseif ($field['type'] === 'number'): ?>
                                            <input type="number" id="<?php echo esc_attr($field_id); ?>"
                                                   name="<?php echo esc_attr($field['key']); ?>"
                                                   data-field="<?php echo esc_attr($slug); ?>"
                                                   value="<?php echo esc_attr($default); ?>"
                                                   <?php if (isset($field['min'])): ?>min="<?php echo esc_attr($field['min']); ?>"<?php endif; ?>
                                                   <?php if (isset($field['max'])): ?>max="<?php echo esc_attr($field['max']); ?>"<?php endif; ?>
                                                   <?php if (isset($field['step'])): ?>step="<?php echo esc_attr($field['step']); ?>"<?php endif; ?>
                                                   class="small-text">
                                            <?php if (!empty($field['after'])): ?>
                                                <span><?php echo esc_html($field['after']); ?></span>
                                            <?php endif; ?>

                                        <?php else: ?>
                                            <input type="text" id="<?php echo esc_attr($field_id); ?>"
                                                   name="<?php echo esc_attr($field['key']); ?>"
                                                   data-field="<?php echo esc_attr($slug); ?>"
                                                   value="<?php echo esc_attr($default); ?>"
                                                   class="regular-text"
                                                   <?php if (!empty($field['placeholder'])): ?>placeholder="<?php echo esc_attr($field['placeholder']); ?>"<?php endif; ?>>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if (!empty($field['description']) && $field['type'] !== 'checkbox'): ?>
                                        <p class="description"><?php echo esc_html($field['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <!-- Styling (always visible once a type is chosen) -->
                <h4><?php _e('Styling', 'simplepco-online'); ?></h4>

                <div class="simplepco-field">
                    <label for="custom_class"><?php _e('Custom CSS Class', 'simplepco-online'); ?></label>
                    <input type="text" id="custom_class" name="custom_class" class="regular-text"
                           placeholder="<?php esc_attr_e('my-custom-class', 'simplepco-online'); ?>">
                </div>

                <div class="simplepco-field-row">
                    <div class="simplepco-field simplepco-field-color">
                        <label for="primary_color"><?php _e('Primary', 'simplepco-online'); ?></label>
                        <input type="color" id="primary_color" name="primary_color" value="#333333">
                    </div>
                    <div class="simplepco-field simplepco-field-color">
                        <label for="text_color"><?php _e('Text', 'simplepco-online'); ?></label>
                        <input type="color" id="text_color" name="text_color" value="#333333">
                    </div>
                    <div class="simplepco-field simplepco-field-color">
                        <label for="background_color"><?php _e('Background', 'simplepco-online'); ?></label>
                        <input type="color" id="background_color" name="background_color" value="#ffffff">
                    </div>
                    <div class="simplepco-field simplepco-field-color">
                        <label for="border_radius"><?php _e('Radius', 'simplepco-online'); ?></label>
                        <input type="number" id="border_radius" name="border_radius" value="8" min="0" max="30" class="small-text"> px
                    </div>
                </div>

                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e('Create Shortcode', 'simplepco-online'); ?></button>
                    <a href="<?php echo esc_url($page_url); ?>" class="button"><?php _e('Cancel', 'simplepco-online'); ?></a>
                </p>
            </form>
        </div>
    </div>

    <script>
    (function($) {
        'use strict';

        // Module dropdown → show matching type list
        $('#simplepco-module-select').on('change', function() {
            var mod = $(this).val();
            $('.simplepco-type-list').hide();
            $('.simplepco-type-link').removeClass('active');
            $('#simplepco-builder-form').hide();
            $('#simplepco-builder-placeholder').show();

            if (mod) {
                $('#simplepco-type-hint').hide();
                $('.simplepco-type-list[data-module="' + mod + '"]').show();
            } else {
                $('#simplepco-type-hint').show();
            }
        });

        // Type link click → show the settings form
        $('.simplepco-type-link').on('click', function(e) {
            e.preventDefault();
            var type = $(this).data('type');

            // Highlight active
            $('.simplepco-type-link').removeClass('active');
            $(this).addClass('active');

            // Set the hidden type input
            $('#simplepco-builder-type').val(type);

            // Toggle field groups: disable + hide non-matching, enable + show matching
            $('.simplepco-type-fields').hide().find(':input').prop('disabled', true);
            $('.simplepco-type-fields[data-type="' + type + '"]').show().find(':input').prop('disabled', false);

            // Apply conditional field visibility for active type
            $('.simplepco-type-fields[data-type="' + type + '"]').find('[data-show-when-field]').each(function() {
                var $target = $(this);
                var srcId = $target.data('show-when-field');
                var val = $target.data('show-when-value');
                var $src = $('#' + srcId);
                $target.toggle($src.val() === val);
            });

            // Show form
            $('#simplepco-builder-placeholder').hide();
            $('#simplepco-builder-form').show();
        });

        // Conditional field visibility: show/hide fields based on a select value
        $('#simplepco-builder-form').on('change', 'select', function() {
            var selectId = $(this).attr('id');
            $('[data-show-when-field="' + selectId + '"]').each(function() {
                var val = $(this).data('show-when-value');
                $(this).toggle($('#' + selectId).val() === val);
            });
        });

        // Auto-select module from URL parameter
        var urlParams = new URLSearchParams(window.location.search);
        var preselectedModule = urlParams.get('module');
        if (preselectedModule) {
            $('#simplepco-module-select').val(preselectedModule).trigger('change');
        }
    })(jQuery);
    </script>

<?php elseif ($is_edit_view): ?>
    <?php // ================================================================
          // EDIT EXISTING SHORTCODE
          //
          // Variables:
          //   $id        (int)    — shortcode ID
          //   $shortcode (array)  — saved settings
          //   $type_slug (string) — e.g. 'simplepco_calendar'
          //   $type_def  (array)  — type definition from registry
          //   $page_url  (string)
          // ================================================================ ?>

    <h1>
        <?php printf(__('Edit Shortcode #%d', 'simplepco-online'), $id); ?>
        <a href="<?php echo esc_url($page_url); ?>" class="page-title-action"><?php _e('Back to Shortcodes', 'simplepco-online'); ?></a>
    </h1>
    <hr class="wp-header-end">

    <?php if ($id > 0): ?>
        <div class="simplepco-shortcode-preview-bar">
            <strong><?php _e('Shortcode:', 'simplepco-online'); ?></strong>
            <?php $preview_code = '[' . $type_def['tag'] . ' id="' . $id . '"]'; ?>
            <code id="shortcode-preview"><?php echo esc_html($preview_code); ?></code>
            <button type="button" class="button button-small simplepco-copy-btn" data-copy="<?php echo esc_attr($preview_code); ?>">
                <?php _e('Copy', 'simplepco-online'); ?>
            </button>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('simplepco_save_module_shortcode'); ?>
        <input type="hidden" name="simplepco_save_module_shortcode" value="1">
        <input type="hidden" name="shortcode_id" value="<?php echo esc_attr($id); ?>">
        <input type="hidden" name="shortcode_type" value="<?php echo esc_attr($type_slug); ?>">

        <div class="card">
            <h2><?php _e('General Settings', 'simplepco-online'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="shortcode_description"><?php _e('Description', 'simplepco-online'); ?></label></th>
                    <td>
                        <input type="text" id="shortcode_description" name="shortcode_description"
                               value="<?php echo esc_attr($shortcode['description'] ?? ''); ?>"
                               class="large-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Module', 'simplepco-online'); ?></th>
                    <td><strong><?php echo esc_html($type_def['module_name']); ?></strong></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Shortcode Type', 'simplepco-online'); ?></th>
                    <td><code><?php echo esc_html($type_def['tag']); ?></code></td>
                </tr>
            </table>
        </div>

        <?php if (!empty($type_def['fields'])): ?>
            <div class="card">
                <h2><?php printf(__('%s Settings', 'simplepco-online'), esc_html($type_def['name'])); ?></h2>
                <table class="form-table">
                    <?php foreach ($type_def['fields'] as $field):
                        $value = $shortcode[$field['key']] ?? ($type_def['defaults'][$field['key']] ?? '');
                    ?>
                        <tr<?php if (!empty($field['show_when'])): ?> data-show-when-field="<?php echo esc_attr($field['show_when']['field']); ?>" data-show-when-value="<?php echo esc_attr($field['show_when']['value']); ?>"<?php if (($shortcode[$field['show_when']['field']] ?? '') !== $field['show_when']['value']): ?> style="display:none;"<?php endif; ?><?php endif; ?>>
                            <th scope="row"><label for="<?php echo esc_attr($field['key']); ?>"><?php echo esc_html($field['label']); ?></label></th>
                            <td>
                                <?php switch ($field['type']):
                                    case 'text': ?>
                                        <input type="text" id="<?php echo esc_attr($field['key']); ?>"
                                               name="<?php echo esc_attr($field['key']); ?>"
                                               value="<?php echo esc_attr($value); ?>" class="regular-text"
                                               <?php if (!empty($field['placeholder'])): ?>placeholder="<?php echo esc_attr($field['placeholder']); ?>"<?php endif; ?>>
                                        <?php break;
                                    case 'number': ?>
                                        <input type="number" id="<?php echo esc_attr($field['key']); ?>"
                                               name="<?php echo esc_attr($field['key']); ?>"
                                               value="<?php echo esc_attr($value); ?>"
                                               <?php if (isset($field['min'])): ?>min="<?php echo esc_attr($field['min']); ?>"<?php endif; ?>
                                               <?php if (isset($field['max'])): ?>max="<?php echo esc_attr($field['max']); ?>"<?php endif; ?>
                                               <?php if (isset($field['step'])): ?>step="<?php echo esc_attr($field['step']); ?>"<?php endif; ?>
                                               class="small-text">
                                        <?php if (!empty($field['after'])): ?><?php echo esc_html($field['after']); ?><?php endif; ?>
                                        <?php break;
                                    case 'select': ?>
                                        <select id="<?php echo esc_attr($field['key']); ?>" name="<?php echo esc_attr($field['key']); ?>">
                                            <?php foreach ($field['options'] as $ov => $ol): ?>
                                                <option value="<?php echo esc_attr($ov); ?>" <?php selected($value, $ov); ?>><?php echo esc_html($ol); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php break;
                                    case 'checkbox': ?>
                                        <label>
                                            <input type="checkbox" id="<?php echo esc_attr($field['key']); ?>"
                                                   name="<?php echo esc_attr($field['key']); ?>"
                                                   value="1" <?php checked($value); ?>>
                                            <?php if (!empty($field['description'])): ?><?php echo esc_html($field['description']); ?><?php endif; ?>
                                        </label>
                                        <?php break;
                                endswitch; ?>
                                <?php if (!empty($field['description']) && $field['type'] !== 'checkbox'): ?>
                                    <p class="description"><?php echo esc_html($field['description']); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2><?php _e('Styling', 'simplepco-online'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="custom_class"><?php _e('Custom CSS Class', 'simplepco-online'); ?></label></th>
                    <td><input type="text" id="custom_class" name="custom_class" value="<?php echo esc_attr($shortcode['custom_class'] ?? ''); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="primary_color"><?php _e('Primary Color', 'simplepco-online'); ?></label></th>
                    <td><input type="color" id="primary_color" name="primary_color" value="<?php echo esc_attr($shortcode['primary_color'] ?? '#333333'); ?>"> <span class="simplepco-color-preview"><?php echo esc_html($shortcode['primary_color'] ?? '#333333'); ?></span></td>
                </tr>
                <tr>
                    <th scope="row"><label for="text_color"><?php _e('Text Color', 'simplepco-online'); ?></label></th>
                    <td><input type="color" id="text_color" name="text_color" value="<?php echo esc_attr($shortcode['text_color'] ?? '#333333'); ?>"> <span class="simplepco-color-preview"><?php echo esc_html($shortcode['text_color'] ?? '#333333'); ?></span></td>
                </tr>
                <tr>
                    <th scope="row"><label for="background_color"><?php _e('Background Color', 'simplepco-online'); ?></label></th>
                    <td><input type="color" id="background_color" name="background_color" value="<?php echo esc_attr($shortcode['background_color'] ?? '#ffffff'); ?>"> <span class="simplepco-color-preview"><?php echo esc_html($shortcode['background_color'] ?? '#ffffff'); ?></span></td>
                </tr>
                <tr>
                    <th scope="row"><label for="border_radius"><?php _e('Border Radius', 'simplepco-online'); ?></label></th>
                    <td><input type="number" id="border_radius" name="border_radius" value="<?php echo esc_attr($shortcode['border_radius'] ?? 8); ?>" min="0" max="30" class="small-text"> px</td>
                </tr>
            </table>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php _e('Save Settings', 'simplepco-online'); ?></button>
            <a href="<?php echo esc_url($page_url); ?>" class="button"><?php _e('Cancel', 'simplepco-online'); ?></a>
        </p>
    </form>

    <script>
    (function($) {
        'use strict';
        $('.simplepco-copy-btn').on('click', function() {
            var text = $(this).data('copy'), $btn = $(this);
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    $btn.text('<?php echo esc_js(__('Copied!', 'simplepco-online')); ?>');
                    setTimeout(function() { $btn.text('<?php echo esc_js(__('Copy', 'simplepco-online')); ?>'); }, 2000);
                });
            }
        });
        $('input[type="color"]').on('input change', function() {
            $(this).next('.simplepco-color-preview').text($(this).val());
        });
        // Conditional field visibility for edit view
        $('select').on('change', function() {
            var fieldName = $(this).attr('name');
            $('[data-show-when-field="' + fieldName + '"]').each(function() {
                var val = $(this).data('show-when-value');
                $(this).toggle($('select[name="' + fieldName + '"]').val() === val);
            });
        });
    })(jQuery);
    </script>

<?php else: ?>
    <?php // ================================================================
          // LIST VIEW
          //
          // Variables:
          //   $shortcodes, $types, $modules, $count_all,
          //   $counts_by_module, $current_filter,
          //   $settings_saved, $deleted, $bulk_deleted, $page_url
          // ================================================================ ?>

    <h1 class="wp-heading-inline"><?php _e('Shortcodes', 'simplepco-online'); ?></h1>
    <a href="<?php echo esc_url($page_url . '&action=new'); ?>" class="page-title-action"><?php _e('Add New', 'simplepco-online'); ?></a>
    <hr class="wp-header-end">

    <?php if ($settings_saved): ?>
        <div class="notice notice-success is-dismissible"><p><?php _e('Shortcode settings saved successfully!', 'simplepco-online'); ?></p></div>
    <?php endif; ?>
    <?php if ($deleted): ?>
        <div class="notice notice-success is-dismissible"><p><?php _e('Shortcode deleted.', 'simplepco-online'); ?></p></div>
    <?php endif; ?>
    <?php if (!empty($bulk_deleted)): ?>
        <div class="notice notice-success is-dismissible"><p><?php printf(_n('%d shortcode deleted.', '%d shortcodes deleted.', $bulk_deleted, 'simplepco-online'), $bulk_deleted); ?></p></div>
    <?php endif; ?>

    <!-- Module Filter Links -->
    <ul class="subsubsub">
        <li class="all">
            <a href="<?php echo esc_url($page_url); ?>" <?php echo empty($current_filter) ? 'class="current" aria-current="page"' : ''; ?>>
                <?php _e('All', 'simplepco-online'); ?> <span class="count">(<?php echo esc_html($count_all); ?>)</span>
            </a>
        </li>
        <?php foreach ($modules as $mod_key => $mod_name): ?>
            <?php if ($counts_by_module[$mod_key] > 0 || $current_filter === $mod_key): ?>
                | <li>
                    <a href="<?php echo esc_url(add_query_arg('module_filter', $mod_key, $page_url)); ?>"
                       <?php echo ($current_filter === $mod_key) ? 'class="current" aria-current="page"' : ''; ?>>
                        <?php echo esc_html($mod_name); ?> <span class="count">(<?php echo esc_html($counts_by_module[$mod_key]); ?>)</span>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>

    <form method="post" id="simplepco-shortcodes-form">
        <?php wp_nonce_field('simplepco_bulk_module_shortcodes'); ?>
        <input type="hidden" name="simplepco_bulk_module_shortcodes" value="1">

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="bulk_action" id="bulk-action-selector-top">
                    <option value="-1"><?php _e('Bulk actions', 'simplepco-online'); ?></option>
                    <option value="trash"><?php _e('Move to Trash', 'simplepco-online'); ?></option>
                </select>
                <input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'simplepco-online'); ?>">
            </div>
            <div class="tablenav-pages one-page">
                <span class="displaying-num"><?php printf(_n('%s item', '%s items', count($shortcodes), 'simplepco-online'), count($shortcodes)); ?></span>
            </div>
            <br class="clear">
        </div>

        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
            <tr>
                <td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox"></td>
                <th scope="col" class="manage-column column-shortcode column-primary"><?php _e('Shortcode', 'simplepco-online'); ?></th>
                <th scope="col" class="manage-column column-description"><?php _e('Description', 'simplepco-online'); ?></th>
                <th scope="col" class="manage-column column-module"><?php _e('Module', 'simplepco-online'); ?></th>
                <th scope="col" class="manage-column column-type"><?php _e('Type', 'simplepco-online'); ?></th>
            </tr>
            </thead>
            <tbody id="the-list">
            <?php if (empty($shortcodes)): ?>
                <tr class="no-items"><td class="colspanchange" colspan="5"><?php _e('No shortcodes found. Click "Add New" to create one.', 'simplepco-online'); ?></td></tr>
            <?php else: ?>
                <?php foreach ($shortcodes as $sc_id => $sc):
                    $sc_type_slug = SimplePCO_Shortcodes_Admin::resolve_legacy_type($sc['shortcode_type'] ?? '', $sc);
                    $sc_type      = $types[$sc_type_slug] ?? null;
                    $sc_tag       = $sc_type ? $sc_type['tag'] : ($sc['shortcode_type'] ?? $sc_type_slug);
                    $sc_code      = '[' . $sc_tag . ' id="' . $sc_id . '"]';
                    $sc_desc      = $sc['description'] ?? '';
                    $mod_name     = $sc_type ? $sc_type['module_name'] : __('Unknown', 'simplepco-online');
                    $type_name    = $sc_type ? $sc_type['name'] : $sc_type_slug;
                    $edit_url     = esc_url($page_url . '&action=edit&id=' . $sc_id);
                    $trash_url    = esc_url(wp_nonce_url($page_url . '&action=delete&id=' . $sc_id, 'simplepco_delete_module_shortcode_' . $sc_id));
                ?>
                    <tr id="shortcode-<?php echo esc_attr($sc_id); ?>">
                        <th scope="row" class="check-column"><input type="checkbox" name="shortcode_ids[]" value="<?php echo esc_attr($sc_id); ?>"></th>
                        <td class="shortcode column-shortcode has-row-actions column-primary">
                            <strong><a class="row-title" href="<?php echo $edit_url; ?>"><code><?php echo esc_html($sc_code); ?></code></a></strong>
                            <div class="row-actions">
                                <span class="edit"><a href="<?php echo $edit_url; ?>"><?php _e('Edit', 'simplepco-online'); ?></a></span>
                                | <span class="copy"><a href="#" class="simplepco-copy-link" data-copy="<?php echo esc_attr($sc_code); ?>"><?php _e('Copy', 'simplepco-online'); ?></a></span>
                                | <span class="trash"><a href="<?php echo $trash_url; ?>" class="submitdelete" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this shortcode?', 'simplepco-online')); ?>');"><?php _e('Trash', 'simplepco-online'); ?></a></span>
                            </div>
                        </td>
                        <td class="description column-description"><?php echo !empty($sc_desc) ? esc_html($sc_desc) : '<span class="simplepco-no-description">&mdash;</span>'; ?></td>
                        <td class="module column-module"><?php echo esc_html($mod_name); ?></td>
                        <td class="type column-type"><?php echo esc_html($type_name); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <tfoot>
            <tr>
                <td class="manage-column column-cb check-column"><input id="cb-select-all-2" type="checkbox"></td>
                <th scope="col" class="manage-column column-shortcode column-primary"><?php _e('Shortcode', 'simplepco-online'); ?></th>
                <th scope="col" class="manage-column column-description"><?php _e('Description', 'simplepco-online'); ?></th>
                <th scope="col" class="manage-column column-module"><?php _e('Module', 'simplepco-online'); ?></th>
                <th scope="col" class="manage-column column-type"><?php _e('Type', 'simplepco-online'); ?></th>
            </tr>
            </tfoot>
        </table>

        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select name="bulk_action2" id="bulk-action-selector-bottom">
                    <option value="-1"><?php _e('Bulk actions', 'simplepco-online'); ?></option>
                    <option value="trash"><?php _e('Move to Trash', 'simplepco-online'); ?></option>
                </select>
                <input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'simplepco-online'); ?>">
            </div>
            <div class="tablenav-pages one-page">
                <span class="displaying-num"><?php printf(_n('%s item', '%s items', count($shortcodes), 'simplepco-online'), count($shortcodes)); ?></span>
            </div>
            <br class="clear">
        </div>
    </form>

    <script>
    (function($) {
        'use strict';
        $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
            var c = $(this).prop('checked');
            $('input[name="shortcode_ids[]"]').prop('checked', c);
            $('#cb-select-all-1, #cb-select-all-2').prop('checked', c);
        });
        $('#bulk-action-selector-top').on('change', function() { $('#bulk-action-selector-bottom').val($(this).val()); });
        $('#bulk-action-selector-bottom').on('change', function() { $('#bulk-action-selector-top').val($(this).val()); $('select[name="bulk_action"]').val($(this).val()); });
        $('.simplepco-copy-link').on('click', function(e) {
            e.preventDefault();
            var text = $(this).data('copy'), $l = $(this), orig = $l.text();
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    $l.text('<?php echo esc_js(__('Copied!', 'simplepco-online')); ?>');
                    setTimeout(function() { $l.text(orig); }, 2000);
                });
            }
        });
    })(jQuery);
    </script>

<?php endif; ?>

</div>
