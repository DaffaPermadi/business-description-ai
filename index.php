<?php
/**
 * Plugin Name: AI Business Description Generator
 * Description: Plugin untuk generate deskripsi bisnis menggunakan Google Gemini AI
 * Version: 1.2
 * Author: Daffa Permadi
 */

if (!defined('ABSPATH')) {
    exit;
}

function abdg_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('abdg-script', plugins_url('js/script.js', __FILE__), array('jquery'), '1.0', true);
    wp_enqueue_style('abdg-style', plugins_url('css/style.css', __FILE__));
    
    wp_localize_script('abdg-script', 'abdg_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('abdg_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'abdg_enqueue_scripts');

function abdg_form_shortcode() {
    ob_start();
    ?>
    <div class="abdg-form-container">
        <form id="abdg-form" class="abdg-form">
            <div class="form-group">
                <label for="business_name">Nama Bisnis:</label>
                <input type="text" id="business_name" name="business_name" required 
                       placeholder="Masukkan nama bisnis Anda">
            </div>
            
            <div class="form-group">
                <label for="business_type">Jenis Bisnis:</label>
                <select id="business_type" name="business_type" required>
                    <option value="">Pilih jenis bisnis</option>
                    <option value="retail">Retail</option>
                    <option value="restaurant">Restaurant</option>
                    <option value="service">Jasa</option>
                    <option value="technology">Teknologi</option>
                    <option value="other">Lainnya</option>
                </select>
            </div>

            <div class="form-group">
                <label for="brief_description">Deskripsi Sekilas (Opsional):</label>
                <textarea id="brief_description" name="brief_description" rows="3" 
                         placeholder="Ceritakan sekilas tentang bisnis Anda (produk/layanan utama, target market, dll)"></textarea>
            </div>
            
            <div class="form-group">
                <label for="business_description">Deskripsi Bisnis:</label>
                <textarea id="business_description" name="business_description" rows="5" 
                         placeholder="Deskripsi lengkap akan muncul di sini"></textarea>
                <button type="button" id="generate-description" class="button">Generate dengan AI</button>
            </div>
            
            <button type="submit" class="button button-primary">Submit</button>
        </form>
        
        <div id="abdg-modal" class="abdg-modal">
            <div class="abdg-modal-content">
                <span class="abdg-close">&times;</span>
                <h3>Generate Deskripsi Bisnis</h3>
                <div id="ai-result"></div>
                <button id="use-description" class="button">Gunakan Deskripsi Ini</button>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('business_description_form', 'abdg_form_shortcode');

function abdg_generate_description() {
    check_ajax_referer('abdg_nonce', 'nonce');
    
    $business_name = sanitize_text_field($_POST['business_name']);
    $business_type = sanitize_text_field($_POST['business_type']);
    $brief_description = sanitize_textarea_field($_POST['brief_description']);
    
    // Gemini API configuration
    $api_key = 'YOUR_API_KEY'; // Ganti dengan API key Anda
    $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    
    // Buat prompt yang spesifik dengan mempertimbangkan brief description jika ada
    $prompt = "Buatkan deskripsi bisnis dalam Bahasa Indonesia untuk {$business_type} bernama {$business_name}.";
    
    if (!empty($brief_description)) {
        $prompt .= "\n\nInformasi tambahan tentang bisnis:\n{$brief_description}";
    }
    
    $prompt .= "\n\nBuatkan deskripsi yang:
    - Profesional dan meyakinkan
    - Panjang 2-3 kalimat
    - Mencakup value proposition
    - Menggunakan bahasa yang mudah dipahami
    - Memasukkan informasi sekilas yang diberikan (jika ada)
    
    Format output: Langsung berikan deskripsi tanpa kata pengantar atau pelengkap.";
    
    $request_body = array(
        'contents' => array(
            array(
                'parts' => array(
                    array(
                        'text' => $prompt
                    )
                )
            )
        )
    );
    
    $response = wp_remote_post($api_url . '?key=' . $api_key, array(
        'headers' => array(
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_body),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        error_log('WP Error: ' . $response->get_error_message());
        wp_send_json_error('Error koneksi ke API: ' . $response->get_error_message());
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($response_code !== 200) {
        error_log('API Error Body: ' . $body);
        wp_send_json_error('API Error: ' . $body);
        return;
    }
    
    $result = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Error memproses response API');
        return;
    }
    
    // Extract text dari response Gemini
    $generated_text = '';
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $generated_text = $result['candidates'][0]['content']['parts'][0]['text'];
    }
    
    if (empty($generated_text)) {
        wp_send_json_error('Tidak dapat menghasilkan deskripsi');
        return;
    }
    
    wp_send_json_success($generated_text);
}

add_action('wp_ajax_abdg_generate_description', 'abdg_generate_description');
add_action('wp_ajax_nopriv_abdg_generate_description', 'abdg_generate_description');
