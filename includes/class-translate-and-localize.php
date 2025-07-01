<?php
/**
 * Main plugin class
 */

class Translate_And_Localize {
    
    private $settings;
    private $queue_processor;
    private $grok_api;
    
    public function __construct() {
        $this->settings = new TAL_Settings();
        $this->queue_processor = new TAL_Queue_Processor();
        $this->grok_api = new TAL_Grok_API();
    }
    
    public function init() {
        // Check if Polylang is active
        if (!function_exists('pll_the_languages')) {
            add_action('admin_notices', array($this, 'polylang_missing_notice'));
            return;
        }
        
        // Initialize components
        $this->settings->init();
        $this->queue_processor->init();
        
        // Add hooks
        add_action('add_meta_boxes', array($this, 'add_translate_metabox'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_tal_start_translation', array($this, 'ajax_start_translation'));
        add_action('wp_ajax_tal_check_translation_status', array($this, 'ajax_check_status'));
        add_action('wp_ajax_tal_test_connection', array($this, 'ajax_test_connection'));
        add_action('tal_process_queue', array($this->queue_processor, 'process_queue'));
        
        // Add translate button to post row actions
        add_filter('post_row_actions', array($this, 'add_row_action'), 10, 2);
        add_filter('page_row_actions', array($this, 'add_row_action'), 10, 2);
    }
    
    public function polylang_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('Translate and Localize with Grok requires Polylang plugin to be installed and activated.', 'translate-and-localize'); ?></p>
        </div>
        <?php
    }
    
    public function add_translate_metabox() {
        // Get all public post types
        $post_types = get_post_types(array('public' => true), 'names');
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'tal_translate_metabox',
                __('Translate & Localize with Grok', 'translate-and-localize'),
                array($this, 'render_translate_metabox'),
                $post_type,
                'side',
                'default'
            );
        }
    }
    
    public function render_translate_metabox($post) {
        // Check if API key is configured
        $api_key = get_option('tal_grok_api_key');
        if (empty($api_key)) {
            ?>
            <p><?php esc_html_e('Please configure your Grok API key in the plugin settings.', 'translate-and-localize'); ?></p>
            <a href="<?php echo esc_url(admin_url('options-general.php?page=translate-and-localize')); ?>" class="button">
                <?php esc_html_e('Go to Settings', 'translate-and-localize'); ?>
            </a>
            <?php
            return;
        }
        
        // Get current post language
        $current_lang = pll_get_post_language($post->ID);
        if (!$current_lang) {
            ?>
            <p><?php esc_html_e('Please set a language for this post first.', 'translate-and-localize'); ?></p>
            <?php
            return;
        }
        
        // Get available languages
        $languages = pll_the_languages(array('raw' => 1));
        
        if (empty($languages)) {
            ?>
            <p><?php esc_html_e('No other languages available for translation.', 'translate-and-localize'); ?></p>
            <?php
            return;
        }
        
        wp_nonce_field('tal_translate_nonce', 'tal_translate_nonce_field');
        ?>
        <div id="tal-translate-container">
            <div class="tal-language-select">
                <label for="tal-target-language">
                    <?php esc_html_e('Translate to:', 'translate-and-localize'); ?>
                </label>
                <select id="tal-target-language" name="tal_target_language" class="widefat">
                    <option value=""><?php esc_html_e('Select target language', 'translate-and-localize'); ?></option>
                    <?php foreach ($languages as $lang): ?>
                        <option value="<?php echo esc_attr($lang['slug']); ?>" <?php selected($lang['slug'], $current_lang); ?>>
                            <?php echo esc_html($lang['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description" style="margin-top: 5px;">
                    <?php esc_html_e('This will translate the content into the selected language.', 'translate-and-localize'); ?>
                </p>
            </div>
            
            <div class="tal-actions" style="margin-top: 10px;">
                <button type="button" id="tal-translate-button" class="button button-primary" disabled>
                    <?php esc_html_e('Translate & Localize', 'translate-and-localize'); ?>
                </button>
                <span class="spinner" style="float: none;"></span>
            </div>
            
            <div id="tal-status-message" style="margin-top: 10px; display: none;"></div>
            
            <div id="tal-progress" style="margin-top: 10px; display: none;">
                <div style="background: #f0f0f0; height: 20px; border-radius: 3px; overflow: hidden;">
                    <div class="tal-progress-bar" style="background: #0073aa; height: 100%; width: 0%; transition: width 0.3s;"></div>
                </div>
                <p class="tal-progress-text" style="margin-top: 5px; font-size: 12px;"></p>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var postId = <?php echo $post->ID; ?>;
            var sourceLang = '<?php echo esc_js($current_lang); ?>';
            
            // Check if a language is already selected on load
            if ($('#tal-target-language').val()) {
                $('#tal-translate-button').prop('disabled', false);
            }
            
            $('#tal-target-language').on('change', function() {
                $('#tal-translate-button').prop('disabled', !$(this).val());
            });
            
            $('#tal-translate-button').on('click', function() {
                var targetLang = $('#tal-target-language').val();
                if (!targetLang) return;
                
                var $button = $(this);
                var $spinner = $('.spinner', '#tal-translate-container');
                var $status = $('#tal-status-message');
                var $progress = $('#tal-progress');
                
                $button.prop('disabled', true);
                $spinner.addClass('is-active');
                $status.hide();
                $progress.show();
                
                $('.tal-progress-bar').css('width', '10%');
                $('.tal-progress-text').text('<?php esc_html_e('Queuing translation...', 'translate-and-localize'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'tal_start_translation',
                        post_id: postId,
                        source_lang: sourceLang,
                        target_lang: targetLang,
                        nonce: $('#tal_translate_nonce_field').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.tal-progress-bar').css('width', '20%');
                            $('.tal-progress-text').text('<?php esc_html_e('Translation queued. Processing...', 'translate-and-localize'); ?>');
                            
                            // Start checking status
                            checkTranslationStatus(response.data.queue_id);
                        } else {
                            $spinner.removeClass('is-active');
                            $button.prop('disabled', false);
                            $progress.hide();
                            $status.removeClass('notice-success').addClass('notice-error').html(response.data.message).show();
                        }
                    },
                    error: function() {
                        $spinner.removeClass('is-active');
                        $button.prop('disabled', false);
                        $progress.hide();
                        $status.removeClass('notice-success').addClass('notice-error').html('<?php esc_html_e('An error occurred. Please try again.', 'translate-and-localize'); ?>').show();
                    }
                });
            });
            
            function checkTranslationStatus(queueId) {
                var checkInterval = setInterval(function() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'tal_check_translation_status',
                            queue_id: queueId,
                            nonce: $('#tal_translate_nonce_field').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                var status = response.data.status;
                                
                                if (status === 'processing') {
                                    $('.tal-progress-bar').css('width', '50%');
                                    $('.tal-progress-text').text('<?php esc_html_e('Processing translation...', 'translate-and-localize'); ?>');
                                } else if (status === 'completed') {
                                    clearInterval(checkInterval);
                                    $('.tal-progress-bar').css('width', '100%');
                                    $('.tal-progress-text').text('<?php esc_html_e('Translation completed!', 'translate-and-localize'); ?>');
                                    
                                    setTimeout(function() {
                                        $('#tal-progress').hide();
                                        $('#tal-status-message').removeClass('notice-error').addClass('notice-success').html(response.data.message).show();
                                        $('.spinner', '#tal-translate-container').removeClass('is-active');
                                        $('#tal-translate-button').prop('disabled', false);
                                        
                                        // Only redirect if it's a different post
                                        if (response.data.translated_post_url && !response.data.same_post_updated) {
                                            setTimeout(function() {
                                                window.location.href = response.data.translated_post_url;
                                            }, 2000);
                                        }
                                    }, 1000);
                                } else if (status === 'failed') {
                                    clearInterval(checkInterval);
                                    $('#tal-progress').hide();
                                    $('#tal-status-message').removeClass('notice-success').addClass('notice-error').html(response.data.message).show();
                                    $('.spinner', '#tal-translate-container').removeClass('is-active');
                                    $('#tal-translate-button').prop('disabled', false);
                                }
                            }
                        }
                    });
                }, 2000); // Check every 2 seconds
            }
        });
        </script>
        <?php
    }
    
    public function add_row_action($actions, $post) {
        // Check if API key is configured
        $api_key = get_option('tal_grok_api_key');
        if (empty($api_key)) {
            return $actions;
        }
        
        // Check if post has a language set
        $current_lang = pll_get_post_language($post->ID);
        if (!$current_lang) {
            return $actions;
        }
        
        // Check if there are other languages available
        $languages = pll_the_languages(array('raw' => 1));
        unset($languages[$current_lang]);
        
        if (!empty($languages)) {
            $actions['tal_translate'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(get_edit_post_link($post->ID) . '#tal_translate_metabox'),
                __('Translate & Localize', 'translate-and-localize')
            );
        }
        
        return $actions;
    }
    
    public function enqueue_admin_scripts($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        wp_enqueue_style(
            'tal-admin',
            TAL_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            TAL_VERSION
        );
    }
    
    public function ajax_start_translation() {
        check_ajax_referer('tal_translate_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $source_lang = sanitize_text_field($_POST['source_lang']);
        $target_lang = sanitize_text_field($_POST['target_lang']);
        
        // Validate inputs
        if (!$post_id || !$source_lang || !$target_lang) {
            wp_send_json_error(array('message' => __('Invalid request parameters.', 'translate-and-localize')));
        }
        
        // Get post content
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(array('message' => __('Post not found.', 'translate-and-localize')));
        }
        
        // Get the prompt template
        $prompt_template = get_option('tal_default_prompt');
        
        // Since Polylang posts might be in English regardless of their assigned language,
        // we'll specify the actual source language in the prompt
        $actual_source_lang = 'English'; // You can make this configurable if needed
        
        // Replace placeholders
        $prompt = str_replace(
            array('{source_lang}', '{target_lang}', '{content}'),
            array($actual_source_lang, $target_lang, $post->post_content),
            $prompt_template
        );
        
        // Add to queue
        global $wpdb;
        $wpdb->insert(
            TAL_QUEUE_TABLE,
            array(
                'post_id' => $post_id,
                'source_lang' => $source_lang,
                'target_lang' => $target_lang,
                'status' => 'pending',
                'prompt' => $prompt,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        $queue_id = $wpdb->insert_id;
        
        // Trigger immediate processing (will be picked up by cron if this times out)
        wp_schedule_single_event(time(), 'tal_process_queue');
        
        wp_send_json_success(array(
            'queue_id' => $queue_id,
            'message' => __('Translation queued successfully.', 'translate-and-localize')
        ));
    }
    
    public function ajax_check_status() {
        check_ajax_referer('tal_translate_nonce', 'nonce');
        
        $queue_id = intval($_POST['queue_id']);
        
        global $wpdb;
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . TAL_QUEUE_TABLE . " WHERE id = %d",
            $queue_id
        ));
        
        if (!$item) {
            wp_send_json_error(array('message' => __('Queue item not found.', 'translate-and-localize')));
        }
        
        $response_data = array(
            'status' => $item->status,
            'message' => ''
        );
        
        if ($item->status === 'completed') {
            // Check if we translated to the same language (in-place translation)
            $post_lang = pll_get_post_language($item->post_id);
            
            if ($post_lang === $item->target_lang) {
                // Same post was updated
                $response_data['message'] = __('Translation completed! Post content has been updated. <a href="#" onclick="location.reload(); return false;">Refresh page</a>', 'translate-and-localize');
                $response_data['same_post_updated'] = true;
            } else {
                // Different language post was created/updated
                $translations = pll_get_post_translations($item->post_id);
                if (isset($translations[$item->target_lang])) {
                    $translated_post = get_post($translations[$item->target_lang]);
                    $response_data['message'] = sprintf(
                        __('Translation completed! <a href="%s">View translated post</a>', 'translate-and-localize'),
                        get_edit_post_link($translated_post->ID)
                    );
                    $response_data['translated_post_url'] = get_edit_post_link($translated_post->ID);
                }
            }
        } elseif ($item->status === 'failed') {
            $response_data['message'] = sprintf(
                __('Translation failed: %s', 'translate-and-localize'),
                $item->error_message
            );
        }
        
        wp_send_json_success($response_data);
    }
    
    public function ajax_test_connection() {
        check_ajax_referer('tal_test_connection', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'translate-and-localize')));
        }
        
        try {
            // Test the connection with a simple translation request
            $test_prompt = "Translate this test phrase to Spanish: Hello World";
            $result = $this->grok_api->translate($test_prompt);
            
            if (!empty($result)) {
                wp_send_json_success(array(
                    'message' => sprintf(
                        __('Connection successful! Using model: %s', 'translate-and-localize'),
                        get_option('tal_grok_model', 'grok-beta')
                    )
                ));
            } else {
                wp_send_json_error(array('message' => __('API returned empty response', 'translate-and-localize')));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
}