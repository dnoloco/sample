<?php
/**
 * Add Registration Template
 *
 * Manual registration entry form.
 *
 * Available variables:
 * - $signup (object) - Signup details
 * - $saved (bool) - Whether form was just saved
 * - $error (string|null) - Error message if any
 */

defined('ABSPATH') || exit;
?>

<div class="wrap">
    <?php if (isset($error)): ?>
        <h1><?php _e('Error', 'simplepco-online'); ?></h1>
        <p><?php echo esc_html($error); ?></p>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=simplepco-registrations')); ?>" class="button">
                ← <?php _e('Back to Registrations', 'simplepco-online'); ?>
            </a>
        </p>
        <?php return; ?>
    <?php endif; ?>

    <h1><?php _e('Add Manual Registration', 'simplepco-online'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=simplepco-registrations&view=registrants&signup_id=' . $signup->id)); ?>" 
       class="page-title-action">
        ← <?php _e('Back to Registrants', 'simplepco-online'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Registration added successfully!', 'simplepco-online'); ?></p>
        </div>
    <?php endif; ?>

    <!-- Event Info -->
    <div class="card" style="max-width: 800px; margin-bottom: 20px;">
        <h2><?php _e('Event', 'simplepco-online'); ?></h2>
        <p><strong><?php echo esc_html($signup->event_name); ?></strong></p>
        <p><?php echo esc_html(mysql2date('l, F j, Y g:i A', $signup->event_date)); ?></p>
    </div>

    <!-- Registration Form -->
    <form method="POST" style="max-width: 800px;">
        <?php wp_nonce_field('add_registration'); ?>
        <input type="hidden" name="signup_id" value="<?php echo esc_attr($signup->id); ?>">

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="first_name"><?php _e('First Name', 'simplepco-online'); ?> *</label>
                </th>
                <td>
                    <input type="text" name="first_name" id="first_name" class="regular-text" required>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="last_name"><?php _e('Last Name', 'simplepco-online'); ?> *</label>
                </th>
                <td>
                    <input type="text" name="last_name" id="last_name" class="regular-text" required>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="email"><?php _e('Email', 'simplepco-online'); ?> *</label>
                </th>
                <td>
                    <input type="email" name="email" id="email" class="regular-text" required>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="phone"><?php _e('Phone', 'simplepco-online'); ?></label>
                </th>
                <td>
                    <input type="tel" name="phone" id="phone" class="regular-text">
                    <p class="description"><?php _e('Optional', 'simplepco-online'); ?></p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="add_registration" class="button button-primary button-large" 
                   value="<?php esc_attr_e('Add Registration', 'simplepco-online'); ?>">
        </p>
    </form>

    <div class="notice notice-info" style="max-width: 800px;">
        <p><strong><?php _e('Note:', 'simplepco-online'); ?></strong> <?php _e('This creates a manual registration. The registrant will not receive automated emails or payment links.', 'simplepco-online'); ?></p>
    </div>
</div>
