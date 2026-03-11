<?php
/**
 * Groups List Template
 *
 * Displays list of PCO Groups.
 *
 * Available variables:
 * - $groups (array) - Array of group objects from PCO
 * - $campus_map (array) - Map of campus IDs to campus data
 * - $group_type_map (array) - Map of group type IDs to type data
 * - $atts (array) - Shortcode attributes
 */

defined('ABSPATH') || exit;
?>

<div class="mypco-groups-container">
    <h2 class="mypco-groups-title"><?php _e('Find a Group', 'mypco-online'); ?></h2>

    <?php if (empty($groups)): ?>
        <p class="mypco-groups-empty">
            <?php _e('No groups found or API connection failed. Check your PCO Groups permissions.', 'mypco-online'); ?>
        </p>
    <?php else: ?>
        <div class="mypco-groups-grid">
            <?php foreach ($groups as $group):
                $attr = $group['attributes'];
                $rels = $group['relationships'];

                // Fetch related data
                $campus_id = $rels['campus']['data']['id'] ?? null;
                $group_type_id = $rels['group_type']['data']['id'] ?? null;
                $campus_name = $campus_map[$campus_id]['name'] ?? __('N/A', 'mypco-online');
                $type_name = $group_type_map[$group_type_id]['name'] ?? __('General Group', 'mypco-online');
                $schedule = $attr['schedule'] ?? __('Check leader for schedule', 'mypco-online');
                ?>
                <div class="mypco-group-card">
                    <h3 class="mypco-group-name"><?php echo esc_html($attr['name']); ?></h3>
                    
                    <div class="mypco-group-meta">
                        <span class="mypco-group-type"><?php echo esc_html($type_name); ?></span>
                        <span class="mypco-group-separator">|</span>
                        <span class="mypco-group-campus"><?php echo esc_html($campus_name); ?></span>
                    </div>
                    
                    <p class="mypco-group-schedule">
                        <?php echo esc_html(wp_trim_words($schedule, 15, '...')); ?>
                    </p>
                    
                    <?php if (!empty($attr['description'])): ?>
                        <p class="mypco-group-description">
                            <?php echo esc_html(wp_trim_words($attr['description'], 20, '...')); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($attr['public_web_url'])): ?>
                        <a href="<?php echo esc_url($attr['public_web_url']); ?>" 
                           target="_blank" 
                           rel="noopener noreferrer" 
                           class="mypco-group-link">
                            <?php _e('View Details', 'mypco-online'); ?> &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
