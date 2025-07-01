<?php
/**
 * Queue processor for background translation
 */

class TAL_Queue_Processor {
    
    private $grok_api;
    
    public function __construct() {
        $this->grok_api = new TAL_Grok_API();
    }
    
    public function init() {
        // Hook for manual processing
        add_action('admin_post_tal_process_queue_manually', array($this, 'process_queue_manually'));
    }
    
    /**
     * Process the translation queue
     */
    public function process_queue() {
        // Prevent concurrent processing
        $lock_key = 'tal_queue_processing_lock';
        $lock_timeout = 300; // 5 minutes
        
        // Try to acquire lock
        if (get_transient($lock_key)) {
            return; // Another process is already running
        }
        
        // Set lock
        set_transient($lock_key, 1, $lock_timeout);
        
        try {
            $this->process_pending_items();
        } catch (Exception $e) {
            error_log('TAL Queue Processing Error: ' . $e->getMessage());
        } finally {
            // Release lock
            delete_transient($lock_key);
        }
    }
    
    /**
     * Process pending items in the queue
     */
    private function process_pending_items() {
        global $wpdb;
        
        $batch_size = get_option('tal_batch_size', 1);
        
        // Get pending items
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . TAL_QUEUE_TABLE . " 
             WHERE status = 'pending' 
             ORDER BY created_at ASC 
             LIMIT %d",
            $batch_size
        ));
        
        if (empty($items)) {
            return;
        }
        
        foreach ($items as $item) {
            $this->process_single_item($item);
            
            // Sleep briefly between items to avoid rate limiting
            usleep(500000); // 0.5 seconds
        }
    }
    
    /**
     * Process a single queue item
     */
    private function process_single_item($item) {
        global $wpdb;
        
        // Update status to processing
        $wpdb->update(
            TAL_QUEUE_TABLE,
            array(
                'status' => 'processing',
                'processed_at' => current_time('mysql')
            ),
            array('id' => $item->id),
            array('%s', '%s'),
            array('%d')
        );
        
        try {
            // Get the original post
            $original_post = get_post($item->post_id);
            if (!$original_post) {
                throw new Exception('Original post not found');
            }
            
            // Call Grok API
            $translated_content = $this->grok_api->translate($item->prompt);
            
            if (empty($translated_content)) {
                throw new Exception('Empty translation response');
            }
            
            // Create or update translated post
            $translated_post_id = $this->create_or_update_translation(
                $original_post,
                $translated_content,
                $item->target_lang
            );
            
            // Update queue item as completed
            $wpdb->update(
                TAL_QUEUE_TABLE,
                array(
                    'status' => 'completed',
                    'response' => $translated_content,
                    'processed_at' => current_time('mysql')
                ),
                array('id' => $item->id),
                array('%s', '%s', '%s'),
                array('%d')
            );
            
        } catch (Exception $e) {
            // Update queue item as failed
            $wpdb->update(
                TAL_QUEUE_TABLE,
                array(
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'processed_at' => current_time('mysql')
                ),
                array('id' => $item->id),
                array('%s', '%s', '%s'),
                array('%d')
            );
            
            error_log('TAL Translation Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Create or update translated post
     */
    private function create_or_update_translation($original_post, $translated_content, $target_lang) {
        // Get the current post's language
        $post_lang = pll_get_post_language($original_post->ID);
        
        // If the target language is the same as the post's language,
        // update the current post directly (common when Polylang posts aren't actually translated yet)
        if ($post_lang === $target_lang) {
            wp_update_post(array(
                'ID' => $original_post->ID,
                'post_content' => $translated_content
            ));
            return $original_post->ID;
        }
        
        // Otherwise, handle normal translation flow
        $translations = pll_get_post_translations($original_post->ID);
        
        // Check if translation already exists
        if (isset($translations[$target_lang])) {
            $translated_post_id = $translations[$target_lang];
            
            // Update existing post
            wp_update_post(array(
                'ID' => $translated_post_id,
                'post_content' => $translated_content
            ));
        } else {
            // Create new post
            $translated_post_data = array(
                'post_title' => $original_post->post_title,
                'post_content' => $translated_content,
                'post_status' => $original_post->post_status, // Preserve original post status
                'post_type' => $original_post->post_type,
                'post_author' => $original_post->post_author,
                'post_excerpt' => $original_post->post_excerpt,
                'post_parent' => $original_post->post_parent,
                'menu_order' => $original_post->menu_order,
                'comment_status' => $original_post->comment_status,
                'ping_status' => $original_post->ping_status
            );
            
            $translated_post_id = wp_insert_post($translated_post_data);
            
            if (is_wp_error($translated_post_id)) {
                throw new Exception($translated_post_id->get_error_message());
            }
            
            // Set language for the new post
            pll_set_post_language($translated_post_id, $target_lang);
            
            // Link translations
            $translations[$target_lang] = $translated_post_id;
            pll_save_post_translations($translations);
            
            // Copy post meta
            $this->copy_post_meta($original_post->ID, $translated_post_id);
            
            // Copy taxonomies
            $this->copy_post_taxonomies($original_post->ID, $translated_post_id, $target_lang);
        }
        
        return $translated_post_id;
    }
    
    /**
     * Copy post meta from original to translated post
     */
    private function copy_post_meta($from_post_id, $to_post_id) {
        $post_meta = get_post_meta($from_post_id);
        
        // Skip certain meta keys
        $skip_keys = array('_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date');
        
        foreach ($post_meta as $key => $values) {
            if (in_array($key, $skip_keys)) {
                continue;
            }
            
            // Delete existing meta
            delete_post_meta($to_post_id, $key);
            
            // Add new meta
            foreach ($values as $value) {
                add_post_meta($to_post_id, $key, maybe_unserialize($value));
            }
        }
    }
    
    /**
     * Copy taxonomies from original to translated post
     */
    private function copy_post_taxonomies($from_post_id, $to_post_id, $target_lang) {
        $taxonomies = get_object_taxonomies(get_post_type($from_post_id));
        
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_object_terms($from_post_id, $taxonomy, array('fields' => 'ids'));
            
            if (!empty($terms) && !is_wp_error($terms)) {
                $translated_terms = array();
                
                foreach ($terms as $term_id) {
                    // Get translated term if exists
                    $translated_term_id = pll_get_term($term_id, $target_lang);
                    
                    if ($translated_term_id) {
                        $translated_terms[] = intval($translated_term_id);
                    }
                }
                
                if (!empty($translated_terms)) {
                    wp_set_object_terms($to_post_id, $translated_terms, $taxonomy);
                }
            }
        }
    }
    
    /**
     * Manual queue processing endpoint
     */
    public function process_queue_manually() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'translate-and-localize'));
        }
        
        check_admin_referer('tal_process_queue_manually');
        
        $this->process_queue();
        
        wp_redirect(add_query_arg(
            array('page' => 'translate-and-localize', 'processed' => '1'),
            admin_url('options-general.php')
        ));
        exit;
    }
}