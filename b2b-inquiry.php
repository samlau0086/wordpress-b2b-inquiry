<?php
/**
 * Plugin Name: B2B Inquiry
 * Description: Provides a customizable B2B inquiry modal and backend management.
 * Version: 1.0.0
 * Author: OpenAI ChatGPT
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

class B2B_Inquiry_Plugin {
    const POST_TYPE = 'b2b_inquiry';
    const OPTION_KEY = 'b2b_inquiry_settings';
    const NONCE_ACTION = 'b2b_inquiry_submit';
    const NONCE_NAME = 'b2b_inquiry_nonce';

    public function __construct() {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('init', [$this, 'register_post_type']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_shortcode('b2b_inquiry_button', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_b2b_inquiry_submit', [$this, 'handle_submission']);
        add_action('wp_ajax_nopriv_b2b_inquiry_submit', [$this, 'handle_submission']);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'register_admin_columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'render_admin_columns'], 10, 2);
        add_filter('manage_edit-' . self::POST_TYPE . '_sortable_columns', [$this, 'register_sortable_columns']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'save_inquiry_meta']);
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
    }

    public function load_textdomain() {
        load_plugin_textdomain('b2b-inquiry', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public static function activate() {
        $plugin = new self();
        $plugin->register_post_type();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public function register_post_type() {
        $labels = [
            'name' => __('B2B Inquiries', 'b2b-inquiry'),
            'singular_name' => __('B2B Inquiry', 'b2b-inquiry'),
            'menu_name' => __('B2B Inquiries', 'b2b-inquiry'),
            'add_new_item' => __('Add New Inquiry', 'b2b-inquiry'),
            'edit_item' => __('Edit Inquiry', 'b2b-inquiry'),
            'new_item' => __('New Inquiry', 'b2b-inquiry'),
            'view_item' => __('View Inquiry', 'b2b-inquiry'),
            'search_items' => __('Search Inquiries', 'b2b-inquiry'),
            'not_found' => __('No inquiries found', 'b2b-inquiry'),
            'not_found_in_trash' => __('No inquiries found in Trash', 'b2b-inquiry'),
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'supports' => ['title', 'editor'],
            'menu_position' => 25,
            'menu_icon' => 'dashicons-format-chat',
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    public function register_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=' . self::POST_TYPE,
            __('Settings', 'b2b-inquiry'),
            __('Settings', 'b2b-inquiry'),
            'manage_options',
            'b2b-inquiry-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('b2b_inquiry_settings_group', self::OPTION_KEY, [$this, 'sanitize_settings']);

        add_settings_section(
            'b2b_inquiry_notification_section',
            __('Notifications', 'b2b-inquiry'),
            '__return_false',
            'b2b-inquiry-settings'
        );

        add_settings_field(
            'emails',
            __('Notification Emails', 'b2b-inquiry'),
            [$this, 'render_emails_field'],
            'b2b-inquiry-settings',
            'b2b_inquiry_notification_section'
        );

        add_settings_field(
            'webhooks',
            __('Webhook URLs', 'b2b-inquiry'),
            [$this, 'render_webhooks_field'],
            'b2b-inquiry-settings',
            'b2b_inquiry_notification_section'
        );

        add_settings_field(
            'message_template',
            __('Default Message Template', 'b2b-inquiry'),
            [$this, 'render_message_template_field'],
            'b2b-inquiry-settings',
            'b2b_inquiry_notification_section'
        );
    }

    public function sanitize_settings($input) {
        $sanitized = [
            'emails' => [],
            'webhooks' => [],
            'message_template' => __('Send me a quote for this "{title}"', 'b2b-inquiry'),
        ];

        if (!empty($input['emails']) && is_array($input['emails'])) {
            foreach ($input['emails'] as $email) {
                $email = sanitize_email($email);
                if (!empty($email)) {
                    $sanitized['emails'][] = $email;
                }
            }
        }

        if (!empty($input['webhooks']) && is_array($input['webhooks'])) {
            foreach ($input['webhooks'] as $url) {
                $url = esc_url_raw($url);
                if (!empty($url)) {
                    $sanitized['webhooks'][] = $url;
                }
            }
        }

        if (!empty($input['message_template'])) {
            $sanitized['message_template'] = sanitize_text_field($input['message_template']);
        }

        return $sanitized;
    }

    public function render_emails_field() {
        $settings = $this->get_settings();
        $emails = !empty($settings['emails']) ? $settings['emails'] : [''];
        echo '<div id="b2b-inquiry-email-list">';
        foreach ($emails as $index => $email) {
            printf(
                '<div class="b2b-inquiry-repeatable"><input type="email" name="%1$s[emails][]" value="%2$s" class="regular-text" placeholder="%3$s" /> <button type="button" class="button b2b-inquiry-remove">%4$s</button></div>',
                esc_attr(self::OPTION_KEY),
                esc_attr($email),
                esc_attr__('notification@example.com', 'b2b-inquiry'),
                esc_html__('Remove', 'b2b-inquiry')
            );
        }
        echo '</div>';
        printf(
            '<button type="button" class="button" id="b2b-inquiry-add-email">%s</button>',
            esc_html__('Add Email', 'b2b-inquiry')
        );
    }

    public function render_webhooks_field() {
        $settings = $this->get_settings();
        $webhooks = !empty($settings['webhooks']) ? $settings['webhooks'] : [''];
        echo '<div id="b2b-inquiry-webhook-list">';
        foreach ($webhooks as $url) {
            printf(
                '<div class="b2b-inquiry-repeatable"><input type="url" name="%1$s[webhooks][]" value="%2$s" class="regular-text" placeholder="%3$s" /> <button type="button" class="button b2b-inquiry-remove">%4$s</button></div>',
                esc_attr(self::OPTION_KEY),
                esc_attr($url),
                esc_attr__('https://example.com/webhook', 'b2b-inquiry'),
                esc_html__('Remove', 'b2b-inquiry')
            );
        }
        echo '</div>';
        printf(
            '<button type="button" class="button" id="b2b-inquiry-add-webhook">%s</button>',
            esc_html__('Add Webhook', 'b2b-inquiry')
        );
    }

    public function render_message_template_field() {
        $settings = $this->get_settings();
        $template = !empty($settings['message_template']) ? $settings['message_template'] : __('Send me a quote for this "{title}"', 'b2b-inquiry');
        printf(
            '<input type="text" name="%1$s[message_template]" value="%2$s" class="regular-text" />',
            esc_attr(self::OPTION_KEY),
            esc_attr($template)
        );
        echo '<p class="description">' . esc_html__('Use placeholders {title}, {url}.', 'b2b-inquiry') . '</p>';
    }

    public function render_settings_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('B2B Inquiry Settings', 'b2b-inquiry') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('b2b_inquiry_settings_group');
        do_settings_sections('b2b-inquiry-settings');
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    public function enqueue_assets() {
        wp_enqueue_style('b2b-inquiry-front', plugins_url('assets/css/front.css', __FILE__), [], '1.0.0');
        wp_enqueue_script('b2b-inquiry-front', plugins_url('assets/js/front.js', __FILE__), ['jquery'], '1.0.0', true);

        $current_user = wp_get_current_user();
        $email = $current_user && $current_user->exists() ? $current_user->user_email : '';
        $settings = $this->get_settings();

        wp_localize_script('b2b-inquiry-front', 'B2BInquirySettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'nonceField' => self::NONCE_NAME,
            'defaultMessage' => sanitize_textarea_field($this->prepare_message_template($settings['message_template'], get_the_title(), get_permalink())),
            'email' => $email,
            'labels' => [
                'email' => __('Email', 'b2b-inquiry'),
                'phone' => __('Phone', 'b2b-inquiry'),
                'message' => __('Message', 'b2b-inquiry'),
                'submit' => __('Submit', 'b2b-inquiry'),
                'close' => __('Close', 'b2b-inquiry'),
                'sending' => __('Sending...', 'b2b-inquiry'),
                'success' => __('Inquiry submitted successfully.', 'b2b-inquiry'),
                'error' => __('Something went wrong. Please try again.', 'b2b-inquiry'),
            ],
        ]);
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'b2b-inquiry_page_b2b-inquiry-settings') {
            return;
        }

        wp_enqueue_script('b2b-inquiry-admin', plugins_url('assets/js/admin.js', __FILE__), ['jquery'], '1.0.0', true);
        wp_enqueue_style('b2b-inquiry-admin', plugins_url('assets/css/admin.css', __FILE__), [], '1.0.0');
        wp_localize_script('b2b-inquiry-admin', 'B2BInquiryAdmin', [
            'removeLabel' => __('Remove', 'b2b-inquiry'),
            'optionKey' => self::OPTION_KEY,
        ]);
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'button_text' => __('Send Inquiry', 'b2b-inquiry'),
        ], $atts, 'b2b_inquiry_button');

        ob_start();
        $this->render_modal($atts['button_text']);
        return ob_get_clean();
    }

    private function render_modal($button_text) {
        $current_url = esc_url(add_query_arg([]));
        $current_title = get_the_title();
        $settings = $this->get_settings();
        $default_message = $this->prepare_message_template($settings['message_template'], $current_title, $current_url);
        $current_user = wp_get_current_user();
        $email = $current_user && $current_user->exists() ? $current_user->user_email : '';
        ?>
        <div class="b2b-inquiry-wrapper">
            <button type="button" class="b2b-inquiry-button"><?php echo esc_html($button_text); ?></button>
            <div class="b2b-inquiry-modal" aria-hidden="true">
                <div class="b2b-inquiry-overlay"></div>
                <div class="b2b-inquiry-content" role="dialog" aria-modal="true">
                    <button type="button" class="b2b-inquiry-close" aria-label="<?php esc_attr_e('Close', 'b2b-inquiry'); ?>">&times;</button>
                    <form class="b2b-inquiry-form">
                        <input type="hidden" name="page_url" value="<?php echo esc_attr($current_url); ?>" />
                        <input type="hidden" name="page_title" value="<?php echo esc_attr($current_title); ?>" />
                        <p>
                            <label>
                                <?php esc_html_e('Email', 'b2b-inquiry'); ?>
                                <input type="email" name="email" required value="<?php echo esc_attr($email); ?>" />
                            </label>
                        </p>
                        <p>
                            <label>
                                <?php esc_html_e('Phone', 'b2b-inquiry'); ?>
                                <input type="text" name="phone" />
                            </label>
                        </p>
                        <p>
                            <label>
                                <?php esc_html_e('Message', 'b2b-inquiry'); ?>
                                <textarea name="message" required><?php echo esc_textarea($default_message); ?></textarea>
                            </label>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary"><?php esc_html_e('Submit', 'b2b-inquiry'); ?></button>
                        </p>
                    </form>
                    <div class="b2b-inquiry-feedback" role="alert"></div>
                </div>
            </div>
        </div>
        <?php
    }

    public function handle_submission() {
        check_ajax_referer(self::NONCE_ACTION, self::NONCE_NAME);

        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
        $message = isset($_POST['message']) ? wp_kses_post(wp_unslash($_POST['message'])) : '';
        $page_url = isset($_POST['page_url']) ? esc_url_raw(wp_unslash($_POST['page_url'])) : '';
        $page_title = isset($_POST['page_title']) ? sanitize_text_field(wp_unslash($_POST['page_title'])) : '';

        if (empty($email) || empty($message)) {
            wp_send_json_error(['message' => __('Email and message are required.', 'b2b-inquiry')], 400);
        }

        $post_id = wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_title' => $page_title ? sprintf(__('Inquiry for %s', 'b2b-inquiry'), $page_title) : __('Inquiry', 'b2b-inquiry'),
            'post_content' => $message,
            'post_status' => 'publish',
        ]);

        if (is_wp_error($post_id)) {
            wp_send_json_error(['message' => __('Unable to save inquiry.', 'b2b-inquiry')], 500);
        }

        update_post_meta($post_id, '_b2b_inquiry_email', $email);
        update_post_meta($post_id, '_b2b_inquiry_phone', $phone);
        update_post_meta($post_id, '_b2b_inquiry_page_url', $page_url);
        update_post_meta($post_id, '_b2b_inquiry_page_title', $page_title);

        $this->dispatch_notifications([
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'page_url' => $page_url,
            'page_title' => $page_title,
        ]);

        wp_send_json_success(['message' => __('Inquiry submitted successfully.', 'b2b-inquiry')]);
    }

    private function dispatch_notifications(array $payload) {
        $settings = $this->get_settings();

        if (!empty($settings['emails'])) {
            foreach ($settings['emails'] as $email) {
                wp_mail(
                    $email,
                    sprintf(__('New inquiry for %s', 'b2b-inquiry'), $payload['page_title']),
                    $this->build_email_body($payload)
                );
            }
        }

        if (!empty($settings['webhooks'])) {
            foreach ($settings['webhooks'] as $url) {
                wp_remote_post($url, [
                    'headers' => ['Content-Type' => 'application/json'],
                    'body' => wp_json_encode($payload),
                    'timeout' => 10,
                ]);
            }
        }
    }

    private function build_email_body(array $payload) {
        $message = sanitize_textarea_field($payload['message']);

        $lines = [
            __('A new inquiry has been submitted:', 'b2b-inquiry'),
            '',
            sprintf(__('Page Title: %s', 'b2b-inquiry'), sanitize_text_field($payload['page_title'])),
            sprintf(__('Page URL: %s', 'b2b-inquiry'), esc_url_raw($payload['page_url'])),
            sprintf(__('Email: %s', 'b2b-inquiry'), sanitize_email($payload['email'])),
            sprintf(__('Phone: %s', 'b2b-inquiry'), sanitize_text_field($payload['phone'])),
            __('Message:', 'b2b-inquiry'),
            $message,
        ];

        return implode("\n", $lines);
    }

    public function register_admin_columns($columns) {
        $columns['email'] = __('Email', 'b2b-inquiry');
        $columns['phone'] = __('Phone', 'b2b-inquiry');
        $columns['page'] = __('Page', 'b2b-inquiry');
        return $columns;
    }

    public function render_admin_columns($column, $post_id) {
        switch ($column) {
            case 'email':
                echo esc_html(get_post_meta($post_id, '_b2b_inquiry_email', true));
                break;
            case 'phone':
                echo esc_html(get_post_meta($post_id, '_b2b_inquiry_phone', true));
                break;
            case 'page':
                $url = get_post_meta($post_id, '_b2b_inquiry_page_url', true);
                if ($url) {
                    printf('<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>', esc_url($url), esc_html(get_post_meta($post_id, '_b2b_inquiry_page_title', true)));
                }
                break;
        }
    }

    public function register_sortable_columns($columns) {
        $columns['email'] = 'email';
        return $columns;
    }

    public function register_meta_boxes() {
        add_meta_box(
            'b2b-inquiry-details',
            __('Inquiry Details', 'b2b-inquiry'),
            [$this, 'render_meta_box'],
            self::POST_TYPE,
            'normal',
            'default'
        );
    }

    public function render_meta_box($post) {
        $email = get_post_meta($post->ID, '_b2b_inquiry_email', true);
        $phone = get_post_meta($post->ID, '_b2b_inquiry_phone', true);
        $page_url = get_post_meta($post->ID, '_b2b_inquiry_page_url', true);
        $page_title = get_post_meta($post->ID, '_b2b_inquiry_page_title', true);
        wp_nonce_field('b2b_inquiry_save_meta', 'b2b_inquiry_meta_nonce');

        echo '<p>';
        echo '<label for="b2b-inquiry-email"><strong>' . esc_html__('Email', 'b2b-inquiry') . '</strong></label><br />';
        echo '<input type="email" id="b2b-inquiry-email" name="_b2b_inquiry_email" class="widefat" value="' . esc_attr($email) . '" />';
        echo '</p>';

        echo '<p>';
        echo '<label for="b2b-inquiry-phone"><strong>' . esc_html__('Phone', 'b2b-inquiry') . '</strong></label><br />';
        echo '<input type="text" id="b2b-inquiry-phone" name="_b2b_inquiry_phone" class="widefat" value="' . esc_attr($phone) . '" />';
        echo '</p>';

        echo '<p>';
        echo '<label for="b2b-inquiry-page-title"><strong>' . esc_html__('Page Title', 'b2b-inquiry') . '</strong></label><br />';
        echo '<input type="text" id="b2b-inquiry-page-title" name="_b2b_inquiry_page_title" class="widefat" value="' . esc_attr($page_title) . '" />';
        echo '</p>';

        echo '<p>';
        echo '<label for="b2b-inquiry-page-url"><strong>' . esc_html__('Page URL', 'b2b-inquiry') . '</strong></label><br />';
        echo '<input type="url" id="b2b-inquiry-page-url" name="_b2b_inquiry_page_url" class="widefat" value="' . esc_url($page_url) . '" />';
        if ($page_url) {
            printf('<br /><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>', esc_url($page_url), esc_html__('Open page', 'b2b-inquiry'));
        }
        echo '</p>';
    }

    public function save_inquiry_meta($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!isset($_POST['b2b_inquiry_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['b2b_inquiry_meta_nonce'])), 'b2b_inquiry_save_meta')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $email = isset($_POST['_b2b_inquiry_email']) ? sanitize_email(wp_unslash($_POST['_b2b_inquiry_email'])) : '';
        $phone = isset($_POST['_b2b_inquiry_phone']) ? sanitize_text_field(wp_unslash($_POST['_b2b_inquiry_phone'])) : '';
        $page_url = isset($_POST['_b2b_inquiry_page_url']) ? esc_url_raw(wp_unslash($_POST['_b2b_inquiry_page_url'])) : '';
        $page_title = isset($_POST['_b2b_inquiry_page_title']) ? sanitize_text_field(wp_unslash($_POST['_b2b_inquiry_page_title'])) : '';

        update_post_meta($post_id, '_b2b_inquiry_email', $email);
        update_post_meta($post_id, '_b2b_inquiry_phone', $phone);
        update_post_meta($post_id, '_b2b_inquiry_page_url', $page_url);
        update_post_meta($post_id, '_b2b_inquiry_page_title', $page_title);
    }

    private function get_settings() {
        $defaults = [
            'emails' => [],
            'webhooks' => [],
            'message_template' => __('Send me a quote for this "{title}"', 'b2b-inquiry'),
        ];

        $settings = get_option(self::OPTION_KEY, []);
        return wp_parse_args($settings, $defaults);
    }

    private function prepare_message_template($template, $title, $url) {
        $replacements = [
            '{title}' => $title,
            '{url}' => $url,
        ];

        return strtr($template, $replacements);
    }
}

register_activation_hook(__FILE__, ['B2B_Inquiry_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['B2B_Inquiry_Plugin', 'deactivate']);

new B2B_Inquiry_Plugin();
