<?php
/**
 * Settings page class
 */

class TAL_Settings {
    
    public function init() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function add_settings_page() {
        add_options_page(
            __('Translate & Localize Settings', 'translate-and-localize'),
            __('Translate & Localize', 'translate-and-localize'),
            'manage_options',
            'translate-and-localize',
            array($this, 'render_settings_page')
        );
    }
    
    public function register_settings() {
        // Register settings
        register_setting('tal_settings', 'tal_grok_api_key', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('tal_settings', 'tal_grok_model', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'grok-beta'
        ));
        
        register_setting('tal_settings', 'tal_default_prompt', array(
            'sanitize_callback' => 'wp_kses_post'
        ));
        
        register_setting('tal_settings', 'tal_batch_size', array(
            'sanitize_callback' => 'intval',
            'default' => 1
        ));
        
        register_setting('tal_settings', 'tal_request_timeout', array(
            'sanitize_callback' => 'intval',
            'default' => 120
        ));
        
        // API Settings Section
        add_settings_section(
            'tal_api_settings',
            __('Grok API Settings', 'translate-and-localize'),
            array($this, 'api_settings_section_callback'),
            'translate-and-localize'
        );
        
        add_settings_field(
            'tal_grok_api_key',
            __('API Key', 'translate-and-localize'),
            array($this, 'api_key_field_callback'),
            'translate-and-localize',
            'tal_api_settings'
        );
        
        add_settings_field(
            'tal_grok_model',
            __('Model', 'translate-and-localize'),
            array($this, 'model_field_callback'),
            'translate-and-localize',
            'tal_api_settings'
        );
        
        // Prompt Settings Section
        add_settings_section(
            'tal_prompt_settings',
            __('Translation Prompt Settings', 'translate-and-localize'),
            array($this, 'prompt_settings_section_callback'),
            'translate-and-localize'
        );
        
        add_settings_field(
            'tal_default_prompt',
            __('Default Prompt', 'translate-and-localize'),
            array($this, 'prompt_field_callback'),
            'translate-and-localize',
            'tal_prompt_settings'
        );
        
        // Performance Settings Section
        add_settings_section(
            'tal_performance_settings',
            __('Performance Settings', 'translate-and-localize'),
            array($this, 'performance_settings_section_callback'),
            'translate-and-localize'
        );
        
        add_settings_field(
            'tal_batch_size',
            __('Batch Size', 'translate-and-localize'),
            array($this, 'batch_size_field_callback'),
            'translate-and-localize',
            'tal_performance_settings'
        );
        
        add_settings_field(
            'tal_request_timeout',
            __('Request Timeout', 'translate-and-localize'),
            array($this, 'request_timeout_field_callback'),
            'translate-and-localize',
            'tal_performance_settings'
        );
    }
    
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('tal_settings');
                do_settings_sections('translate-and-localize');
                submit_button();
                ?>
            </form>
            
            <hr>
            
            <h2><?php esc_html_e('Test Connection', 'translate-and-localize'); ?></h2>
            <p><?php esc_html_e('Test your API key and model configuration.', 'translate-and-localize'); ?></p>
            <button type="button" id="tal-test-connection" class="button button-secondary">
                <?php esc_html_e('Test Connection', 'translate-and-localize'); ?>
            </button>
            <span id="tal-test-result" style="margin-left: 10px;"></span>
            
            <script>
            jQuery(document).ready(function($) {
                $('#tal-test-connection').on('click', function() {
                    var $button = $(this);
                    var $result = $('#tal-test-result');
                    
                    $button.prop('disabled', true);
                    $result.html('<span class="spinner is-active" style="float: none;"></span>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'tal_test_connection',
                            nonce: '<?php echo wp_create_nonce('tal_test_connection'); ?>'
                        },
                        success: function(response) {
                            $button.prop('disabled', false);
                            if (response.success) {
                                $result.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
                            } else {
                                $result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
                            }
                        },
                        error: function() {
                            $button.prop('disabled', false);
                            $result.html('<span style="color: red;">✗ <?php esc_html_e('Connection test failed', 'translate-and-localize'); ?></span>');
                        }
                    });
                });
            });
            </script>
            
            <div class="tal-settings-info">
                <h2><?php esc_html_e('Queue Status', 'translate-and-localize'); ?></h2>
                <?php $this->display_queue_status(); ?>
                
                <h2><?php esc_html_e('How to Get Grok API Key', 'translate-and-localize'); ?></h2>
                <ol>
                    <li><?php esc_html_e('Visit the xAI Console at', 'translate-and-localize'); ?> <a href="https://console.x.ai" target="_blank">https://console.x.ai</a></li>
                    <li><?php esc_html_e('Sign up or log in to your account', 'translate-and-localize'); ?></li>
                    <li><?php esc_html_e('Navigate to API Keys section', 'translate-and-localize'); ?></li>
                    <li><?php esc_html_e('Create a new API key', 'translate-and-localize'); ?></li>
                    <li><?php esc_html_e('Copy the key and paste it in the field above', 'translate-and-localize'); ?></li>
                </ol>
                
                <h2><?php esc_html_e('Available Placeholders for Prompt', 'translate-and-localize'); ?></h2>
                <ul>
                    <li><code>{source_lang}</code> - <?php esc_html_e('Source language code', 'translate-and-localize'); ?></li>
                    <li><code>{target_lang}</code> - <?php esc_html_e('Target language code', 'translate-and-localize'); ?></li>
                    <li><code>{content}</code> - <?php esc_html_e('The content to be translated', 'translate-and-localize'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    public function api_settings_section_callback() {
        echo '<p>' . esc_html__('Configure your Grok API credentials.', 'translate-and-localize') . '</p>';
    }
    
    public function api_key_field_callback() {
        $api_key = get_option('tal_grok_api_key');
        ?>
        <input type="password" 
               id="tal_grok_api_key" 
               name="tal_grok_api_key" 
               value="<?php echo esc_attr($api_key); ?>" 
               class="regular-text" 
               autocomplete="off" />
        <button type="button" class="button" onclick="toggleApiKeyVisibility()">
            <span class="dashicons dashicons-visibility"></span>
        </button>
        <p class="description">
            <?php esc_html_e('Enter your Grok API key from xAI Console.', 'translate-and-localize'); ?>
        </p>
        <script>
        function toggleApiKeyVisibility() {
            var input = document.getElementById('tal_grok_api_key');
            input.type = input.type === 'password' ? 'text' : 'password';
        }
        </script>
        <?php
    }
    
    public function model_field_callback() {
        $model = get_option('tal_grok_model', 'grok-beta');
        ?>
        <input type="text" 
               id="tal_grok_model" 
               name="tal_grok_model" 
               value="<?php echo esc_attr($model); ?>" 
               class="regular-text" 
               placeholder="grok-beta" />
        <p class="description">
            <?php esc_html_e('Enter the Grok model name (e.g., grok-beta, grok-2-beta, grok-2-vision-beta).', 'translate-and-localize'); ?><br>
            <?php esc_html_e('Check', 'translate-and-localize'); ?> <a href="https://docs.x.ai/docs#models" target="_blank"><?php esc_html_e('xAI documentation', 'translate-and-localize'); ?></a> <?php esc_html_e('for the latest available models.', 'translate-and-localize'); ?>
        </p>
        <?php
    }
    
    public function prompt_settings_section_callback() {
        echo '<p>' . esc_html__('Customize the prompt sent to Grok for translation and localization.', 'translate-and-localize') . '</p>';
    }
    
    public function prompt_field_callback() {
        $prompt = get_option('tal_default_prompt');
        ?>
        <textarea id="tal_default_prompt" 
                  name="tal_default_prompt" 
                  rows="8" 
                  cols="60" 
                  class="large-text"><?php echo esc_textarea($prompt); ?></textarea>
        <p class="description">
            <?php esc_html_e('This prompt will be sent to Grok. Use placeholders to include dynamic content.', 'translate-and-localize'); ?>
        </p>
        <?php
    }
    
    public function performance_settings_section_callback() {
        echo '<p>' . esc_html__('Configure performance settings to avoid timeouts.', 'translate-and-localize') . '</p>';
    }
    
    public function batch_size_field_callback() {
        $batch_size = get_option('tal_batch_size', 1);
        ?>
        <input type="number" 
               id="tal_batch_size" 
               name="tal_batch_size" 
               value="<?php echo esc_attr($batch_size); ?>" 
               min="1" 
               max="10" 
               class="small-text" />
        <p class="description">
            <?php esc_html_e('Number of translations to process per batch. Lower values help avoid timeouts.', 'translate-and-localize'); ?>
        </p>
        <?php
    }
    
    public function request_timeout_field_callback() {
        $timeout = get_option('tal_request_timeout', 120);
        ?>
        <input type="number" 
               id="tal_request_timeout" 
               name="tal_request_timeout" 
               value="<?php echo esc_attr($timeout); ?>" 
               min="30" 
               max="300" 
               class="small-text" />
        <?php esc_html_e('seconds', 'translate-and-localize'); ?>
        <p class="description">
            <?php esc_html_e('Maximum time to wait for API response. Must be less than your server timeout.', 'translate-and-localize'); ?>
        </p>
        <?php
    }
    
    private function display_queue_status() {
        global $wpdb;
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM " . TAL_QUEUE_TABLE);
        $pending = $wpdb->get_var("SELECT COUNT(*) FROM " . TAL_QUEUE_TABLE . " WHERE status = 'pending'");
        $processing = $wpdb->get_var("SELECT COUNT(*) FROM " . TAL_QUEUE_TABLE . " WHERE status = 'processing'");
        $completed = $wpdb->get_var("SELECT COUNT(*) FROM " . TAL_QUEUE_TABLE . " WHERE status = 'completed'");
        $failed = $wpdb->get_var("SELECT COUNT(*) FROM " . TAL_QUEUE_TABLE . " WHERE status = 'failed'");
        
        ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('Status', 'translate-and-localize'); ?></th>
                    <th><?php esc_html_e('Count', 'translate-and-localize'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php esc_html_e('Total', 'translate-and-localize'); ?></td>
                    <td><?php echo esc_html($total); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Pending', 'translate-and-localize'); ?></td>
                    <td><?php echo esc_html($pending); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Processing', 'translate-and-localize'); ?></td>
                    <td><?php echo esc_html($processing); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Completed', 'translate-and-localize'); ?></td>
                    <td><?php echo esc_html($completed); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Failed', 'translate-and-localize'); ?></td>
                    <td><?php echo esc_html($failed); ?></td>
                </tr>
            </tbody>
        </table>
        
        <?php if ($pending > 0 || $processing > 0): ?>
            <p class="description">
                <em><?php esc_html_e('Translations are processed in the background. The queue runs every minute via WordPress cron.', 'translate-and-localize'); ?></em>
            </p>
        <?php endif; ?>
        <?php
    }
}