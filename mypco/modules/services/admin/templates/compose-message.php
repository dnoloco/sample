<?php
/**
 * Compose Message Template
 *
 * Template for composing messages to send via Clearstream.
 * Note: This may be moved to the Messages module in the future.
 *
 * Available variables:
 * - $plan_id (string|null) - Plan ID if composing from plan
 * - $from (string) - Source page (services, messages, etc.)
 * - $title (string) - Plan title (if from plan)
 * - $team_members (array) - Team members (if from plan)
 */

defined('ABSPATH') || exit;
?>

<div class="wrap">
    <h1><?php _e('Compose Message', 'mypco-online'); ?></h1>
    
    <?php if (isset($from) && $from === 'plan'): ?>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=mypco-services&view=plan_details&plan_id=' . $plan_id)); ?>" class="button">
                ← <?php _e('Back to Plan', 'mypco-online'); ?>
            </a>
        </p>
    <?php else: ?>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=mypco-services')); ?>" class="button">
                ← <?php _e('Back to Plans', 'mypco-online'); ?>
            </a>
        </p>
    <?php endif; ?>

    <hr class="wp-header-end">

    <div class="card">
        <h2><?php _e('Message Composition', 'mypco-online'); ?></h2>
        <p><?php _e('This feature will be moved to the Messages module.', 'mypco-online'); ?></p>
        
        <p><?php _e('For now, please use:', 'mypco-online'); ?></p>
        <ul>
            <li>
                <a href="<?php echo esc_url(admin_url('admin.php?page=mypco-messages')); ?>">
                    <?php _e('MyPCO → Messages', 'mypco-online'); ?>
                </a>
            </li>
        </ul>

        <?php if (isset($plan_id) && isset($title)): ?>
            <hr>
            <h3><?php _e('Plan Information', 'mypco-online'); ?></h3>
            <p><strong><?php _e('Plan:', 'mypco-online'); ?></strong> <?php echo esc_html($title); ?></p>
            
            <?php if (isset($team_members) && !empty($team_members)): ?>
                <p><strong><?php _e('Team Members:', 'mypco-online'); ?></strong> <?php echo count($team_members); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Placeholder Form (to be implemented in Messages module) -->
    <div class="card" style="margin-top: 20px; opacity: 0.5; pointer-events: none;">
        <h3><?php _e('Preview of Future Interface', 'mypco-online'); ?></h3>
        
        <form method="post" action="">
            <?php wp_nonce_field('send_clearstream_message'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="message_body"><?php _e('Message', 'mypco-online'); ?></label>
                    </th>
                    <td>
                        <textarea name="message_body" id="message_body" rows="5" cols="50" class="large-text" disabled></textarea>
                        <p class="description"><?php _e('Enter your message text', 'mypco-online'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <?php _e('Recipients', 'mypco-online'); ?>
                    </th>
                    <td>
                        <p class="description"><?php _e('Select team members to receive this message', 'mypco-online'); ?></p>
                        <!-- Recipient selection will go here -->
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" name="send_clearstream_message" class="button button-primary" disabled>
                    <?php _e('Send Message', 'mypco-online'); ?>
                </button>
                <button type="submit" name="schedule_clearstream_message" class="button" disabled>
                    <?php _e('Schedule Message', 'mypco-online'); ?>
                </button>
            </p>
        </form>
    </div>

</div>
