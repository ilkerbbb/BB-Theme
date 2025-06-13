<?php
/**
 * Fox Child Theme Functions and Definitions
 *
 * Includes:
 * - BB Theme Setup & Enqueuing
 * - Custom Post Type 'bbb_note' & Taxonomy 'note_type'
 * - REST API Endpoint for Notes
 * - Hashtag to Link Conversion for Note Content
 * - Podcast Feed Fetching & Formatting
 * - Latest News API Fetching & REST Endpoint
 * - Portfolio Price Automation (Google Finance Scraping v3)
 * - Dashboard Data Automation (Google Finance Scraping v2 - Ticker/Sidebar)
 * - Portfolio Settings Admin Page
 * - Dashboard Settings Admin Page
 * - Admin JS Loader Fix
 * - WooCommerce Memberships Custom Restriction Message Filter
 *
 * --- REVISED: Migrated all content restriction logic from Paid Memberships Pro to WooCommerce Memberships. ---
 *
 * @package Fox_Child
 */

// Alt tema versiyonunu tanımla
if ( ! defined( 'FOX_CHILD_VERSION' ) ) {
	$theme = wp_get_theme();
	// Use theme's version if available, otherwise fallback
	define( 'FOX_CHILD_VERSION', $theme->get( 'Version' ) ? $theme->get( 'Version' ) : '1.2.0' ); // Increment version
}

// Ons <-> Gram çevrim faktörü
define( 'OUNCES_TO_GRAMS', 31.1034768 );

// Utility: deterministically assign a color for a given slug
function fox_child_get_color_for_slug( $slug ) {
    $palette = array( '#f94144', '#f3722c', '#f9c74f', '#90be6d', '#577590', '#277da1', '#43aa8b', '#f9844a', '#9d4edd', '#7209b7' );
    $index   = abs( crc32( $slug ) ) % count( $palette );
    return $palette[ $index ];
}

/**
 * Gerekirse çeviri dosyalarını yükle, menü konumlarını kaydet ve tema desteği ekle.
 */
function fox_child_setup() {
	// Load translation files
	load_child_theme_textdomain( 'fox-child', get_stylesheet_directory() . '/languages' );

	// Register navigation menus
	register_nav_menus(
		array(
			'bb_secondary_menu' => esc_html__( 'BB Theme Secondary Menu', 'fox-child' ),
		)
	);

    // Add theme support for post thumbnails for posts and notes
    add_theme_support( 'post-thumbnails', array('post', 'bbb_note') );
}
add_action( 'after_setup_theme', 'fox_child_setup' );


/**
 * Parent ve Child tema stillerini doğru şekilde yükle.
 * BB Theme özel varlıklarını koşullu olarak yükle.
 * JS verilerini yerelleştirir (Portföy, Son Haberler, Notlar).
 */
add_action( 'wp_enqueue_scripts', 'fox_child_enqueue_styles_scripts', 20 );
function fox_child_enqueue_styles_scripts() {
    // Parent and Child Styles
    $parent_style_handle = 'parent-style'; // Adjust if parent theme uses a different handle
	wp_enqueue_style( 'fox-child-style', get_stylesheet_uri(), array( /* $parent_style_handle */ ), FOX_CHILD_VERSION );

    // BB Theme Specific Assets
	$load_bb_assets = false;
    $bb_page_templates = array(
        'bb-theme.php', 'template-research.php', 'template-bb-blank.php',
        'template-portfolio.php', 'template-bulten.php', 'template-bbb-news.php',
        'template-notes.php' // Notes template
    );
    $bb_single_post_categories = array( 'arastirma', 'bulten' );
    global $post;
    $is_bb_single = false;
    if ( is_single() && $post ) {
        // Load for specific categories OR the 'bbb_note' CPT
        if ( get_post_type($post) === 'bbb_note' || has_category( $bb_single_post_categories, $post ) ) {
             $is_bb_single = true;
        }
    }

    // === DEĞİŞİKLİK BAŞLANGICI: Şablon kontrolünü daha sağlam hale getir ===
    // Kısıtlama eklentileri is_page_template() fonksiyonunu bozabilir.
    // Bu yüzden veritabanından şablonu doğrudan kontrol eden bir yedek ekliyoruz.
    $current_template = '';
    if ( is_page() && $post ) {
        $current_template = get_post_meta( $post->ID, '_wp_page_template', true );
    }

    // Load for specific templates, single posts/notes, or note archives/taxonomies
    if ( is_page_template( $bb_page_templates ) || in_array( $current_template, $bb_page_templates ) || $is_bb_single || is_post_type_archive('bbb_note') || is_tax('note_type') ) {
        $load_bb_assets = true;
    }
    // === DEĞİŞİKLİK SONU ===

	if ( $load_bb_assets ) {
		wp_enqueue_style( 'bb-theme-specific-style', get_stylesheet_directory_uri() . '/css/bb-theme-style.css', array( 'fox-child-style' ), FOX_CHILD_VERSION );
		// Dark Mode Toggle JS
        $dark_mode_script_path = get_stylesheet_directory() . '/js/bb-theme-dark-mode.js';
        if ( file_exists( $dark_mode_script_path ) ) {
            wp_enqueue_script( 'bb-theme-dark-mode-toggle', get_stylesheet_directory_uri() . '/js/bb-theme-dark-mode.js', array(), FOX_CHILD_VERSION, true );
        }
        // Podcast Player JS
        $podcast_player_script_path = get_stylesheet_directory() . '/js/bb-theme-podcast-player.js';
        if ( file_exists( $podcast_player_script_path ) ) {
            wp_enqueue_script( 'bb-theme-podcast-player', get_stylesheet_directory_uri() . '/js/bb-theme-podcast-player.js', array(), FOX_CHILD_VERSION, true );
        }
        // Latest News JS & Data
        $latest_news_script_path = get_stylesheet_directory() . '/js/bb-theme-latest-news.js';
        if ( file_exists( $latest_news_script_path ) ) {
            wp_enqueue_script( 'bb-theme-latest-news', get_stylesheet_directory_uri() . '/js/bb-theme-latest-news.js', array( 'jquery' ), FOX_CHILD_VERSION, true );
            wp_localize_script( 'bb-theme-latest-news', 'bbLatestNewsData', array(
                'rest_url' => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'api_endpoint' => 'bbb-news/v1/latest',
                'update_interval' => 300000, // 5 minutes
                'text_loading' => esc_html__( 'Yükleniyor...', 'fox-child' ),
                'text_no_news' => esc_html__( 'Gösterilecek haber bulunamadı.', 'fox-child' ),
                'text_error' => esc_html__( 'Haberler yüklenirken bir hata oluştu.', 'fox-child' ),
                'news_limit' => 6
            ) );
        }
	}

    // Portfolio Page JS & Data
	if ( is_page_template( 'template-portfolio.php' ) || $current_template === 'template-portfolio.php' ) {
		wp_enqueue_script( 'chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.1', true );
        $portfolio_logic_script_path = get_stylesheet_directory() . '/js/bb-theme-portfolio.js';
        if ( file_exists( $portfolio_logic_script_path ) ) {
            wp_enqueue_script( 'bb-theme-portfolio-logic', get_stylesheet_directory_uri() . '/js/bb-theme-portfolio.js', array( 'chartjs', 'jquery' ), FOX_CHILD_VERSION, true );
            
            // Yetki kontrolü buradan kaldırıldı. Sayfa zaten kısıtlıysa bu JS verisi de yüklenmeyecektir.
            $portfolio_options = get_option( 'fox_child_portfolio_options', [] );
            $assets = $portfolio_options['assets'] ?? [];
            $performance_data_raw = $portfolio_options['performance'] ?? [];
            // Chart data preparation logic (same as before)
            $chart_data = array( 'pieChart' => [ 'labels' => [], 'data' => [] ], 'monthlyChart' => [ 'labels' => [], 'portfolioData' => [] ], 'cumulativeChart' => [ 'labels' => [], 'portfolioData' => [], 'xu100Data' => [], 'sp500tlData' => [] ] );
            $total_portfolio_value = 0;
            if ( !empty($assets) ) { foreach ($assets as $asset) { $quantity = $asset['quantity'] ?? 0; $current_price = $asset['current_price'] ?? 0; if (is_numeric($quantity) && is_numeric($current_price)) { $total_portfolio_value += floatval($quantity) * floatval($current_price); } } if ($total_portfolio_value > 0) { foreach ( $assets as $asset ) { $label = !empty( $asset['symbol'] ) ? $asset['symbol'] : ( !empty($asset['name']) ? $asset['name'] : 'N/A' ); $quantity = $asset['quantity'] ?? 0; $current_price = $asset['current_price'] ?? 0; if ( $label !== 'N/A' && is_numeric($quantity) && is_numeric($current_price) ) { $asset_value = floatval($quantity) * floatval($current_price); $asset_weight = ($asset_value / $total_portfolio_value) * 100; $chart_data['pieChart']['labels'][] = $label; $chart_data['pieChart']['data'][] = round($asset_weight, 1); } } } }
            if ( !empty($performance_data_raw) ) { $cumulative_portfolio = 100.0; $cumulative_xu100 = 100.0; $cumulative_sp500tl = 100.0; foreach ( $performance_data_raw as $perf ) { $label = $perf['label'] ?? null; $monthly_portfolio = $perf['portfolio'] ?? 0; $monthly_xu100 = $perf['xu100'] ?? 0; $monthly_sp500tl = $perf['sp500tl'] ?? 0; if ( $label && is_numeric($monthly_portfolio) && is_numeric($monthly_xu100) && is_numeric($monthly_sp500tl) ) { $chart_data['monthlyChart']['labels'][] = $label; $chart_data['monthlyChart']['portfolioData'][] = floatval($monthly_portfolio); $cumulative_portfolio *= (1 + floatval($monthly_portfolio) / 100); $cumulative_xu100 *= (1 + floatval($monthly_xu100) / 100); $cumulative_sp500tl *= (1 + floatval($monthly_sp500tl) / 100); $cumulative_portfolio_percent = $cumulative_portfolio - 100.0; $cumulative_xu100_percent = $cumulative_xu100 - 100.0; $cumulative_sp500tl_percent = $cumulative_sp500tl - 100.0; $chart_data['cumulativeChart']['labels'][] = $label; $chart_data['cumulativeChart']['portfolioData'][] = round($cumulative_portfolio_percent, 1); $chart_data['cumulativeChart']['xu100Data'][] = round($cumulative_xu100_percent, 1); $chart_data['cumulativeChart']['sp500tlData'][] = round($cumulative_sp500tl_percent, 1); } } $monthly_labels_count = count($chart_data['monthlyChart']['labels']); if ($monthly_labels_count > 12) { $chart_data['monthlyChart']['labels'] = array_slice($chart_data['monthlyChart']['labels'], -12); $chart_data['monthlyChart']['portfolioData'] = array_slice($chart_data['monthlyChart']['portfolioData'], -12); } }
            wp_localize_script( 'bb-theme-portfolio-logic', 'portfolioData', $chart_data );
        }
	}

    // Notes Page JS & Data
    if ( is_page_template( 'template-notes.php' ) || $current_template === 'template-notes.php' ) {
        $notes_script_path = get_stylesheet_directory() . '/js/notes-page.js';
        if ( file_exists( $notes_script_path ) ) {
            wp_enqueue_script( 'bbb-notes-page', get_stylesheet_directory_uri() . '/js/notes-page.js', array( 'jquery' ), FOX_CHILD_VERSION, true );
            wp_localize_script( 'bbb-notes-page', 'bbbNotesData', array(
                'rest_url'      => esc_url_raw( rest_url() ),
                'nonce'         => wp_create_nonce( 'wp_rest' ),
                'api_endpoint'  => 'bbb-notes/v1/note/',  // Note ID will be appended by JS
                'list_endpoint' => 'bbb-notes/v1/list',
                'posts_per_page'=> 10,
                'text_loading'  => esc_html__( 'Yükleniyor...', 'fox-child' ),
                'text_error'    => esc_html__( 'Not yüklenirken bir hata oluştu.', 'fox-child' ),
            ) );
        } else {
             error_log("Notes Page JS not found: " . $notes_script_path);
        }
    }

} // End: fox_child_enqueue_styles_scripts()


/**
 * Özel BB Theme şablonlarının doğru yüklenmesini garanti altına alan filtre.
 * Bu, hem Sayfalar hem de özel tekil yazılar/notlar için çalışır.
 * Kısıtlama eklentilerinin neden olduğu 'template_hierarchy' sorunlarını by-pass eder.
 */
function fox_child_force_bb_theme_template( $template ) {
    global $post;
    if ( ! $post ) {
        return $template;
    }
    
    // SADECE 'bbb_note' özel içerik türünün tekil sayfalarını hedef al.
    if ( is_singular('bbb_note') ) {
        $new_template = get_stylesheet_directory() . '/bb-theme-single-post.php';
        if ( file_exists( $new_template ) ) {
            return $new_template;
        }
    }
    
    // 'arastirma' veya 'bulten' kategorisindeki standart yazıları hedef al.
    if ( is_single() && !is_attachment() && has_category( array('arastirma', 'bulten'), $post ) ) {
         $new_template = get_stylesheet_directory() . '/bb-theme-single-post.php';
        if ( file_exists( $new_template ) ) {
            return $new_template;
        }
    }

    // Eğer koşullar sağlanmıyorsa, WordPress'in orijinal seçimine dokunma.
    return $template;
}
add_filter( 'template_include', 'fox_child_force_bb_theme_template', 99 );


// =============================================
// === Notlar CPT ve Taksonomi Kaydı ===
// =============================================
function fox_child_register_note_cpt() {
    $labels = array(
        'name'                  => _x( 'Notlar', 'Post type general name', 'fox-child' ),
        'singular_name'         => _x( 'Not', 'Post type singular name', 'fox-child' ),
        'menu_name'             => _x( 'Notlar', 'Admin Menu text', 'fox-child' ),
        'name_admin_bar'        => _x( 'Not', 'Add New on Toolbar', 'fox-child' ),
        'add_new'               => __( 'Yeni Not Ekle', 'fox-child' ),
        'add_new_item'          => __( 'Yeni Not Ekle', 'fox-child' ),
        'new_item'              => __( 'Yeni Not', 'fox-child' ),
        'edit_item'             => __( 'Notu Düzenle', 'fox-child' ),
        'view_item'             => __( 'Notu Görüntüle', 'fox-child' ),
        'all_items'             => __( 'Tüm Notlar', 'fox-child' ),
        'search_items'          => __( 'Not Ara', 'fox-child' ),
        'parent_item_colon'     => __( 'Üst Not:', 'fox-child' ),
        'not_found'             => __( 'Not bulunamadı.', 'fox-child' ),
        'not_found_in_trash'    => __( 'Çöp kutusunda not bulunamadı.', 'fox-child' ),
        'featured_image'        => _x( 'Not Görseli', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'fox-child' ),
        'set_featured_image'    => _x( 'Not görseli ayarla', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'fox-child' ),
        'remove_featured_image' => _x( 'Not görselini kaldır', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'fox-child' ),
        'use_featured_image'    => _x( 'Not görseli olarak kullan', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'fox-child' ),
        'archives'              => _x( 'Not Arşivleri', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'fox-child' ),
        'insert_into_item'      => _x( 'Nota ekle', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'fox-child' ),
        'uploaded_to_this_item' => _x( 'Bu nota yüklendi', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'fox-child' ),
        'filter_items_list'     => _x( 'Not listesini filtrele', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'fox-child' ),
        'items_list_navigation' => _x( 'Not listesi dolaşımı', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'fox-child' ),
        'items_list'            => _x( 'Not listesi', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'fox-child' ),
    );
    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'notlar' ),
        'capability_type'    => 'post',
        'has_archive'        => 'not-arsivi',
        'hierarchical'       => false,
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-sticky',
        'supports'           => array( 'title', 'editor', 'thumbnail', 'author', 'excerpt', 'custom-fields' ),
        'show_in_rest'       => true,
        'taxonomies'         => array( 'note_type' ),
    );
    register_post_type( 'bbb_note', $args );
}
add_action( 'init', 'fox_child_register_note_cpt' );

function fox_child_register_note_taxonomy() {
    $labels = array(
        'name'              => _x( 'Not Türleri', 'taxonomy general name', 'fox-child' ),
        'singular_name'     => _x( 'Not Türü', 'taxonomy singular name', 'fox-child' ),
        'search_items'      => __( 'Not Türü Ara', 'fox-child' ),
        'all_items'         => __( 'Tüm Not Türleri', 'fox-child' ),
        'parent_item'       => __( 'Üst Not Türü', 'fox-child' ),
        'parent_item_colon' => __( 'Üst Not Türü:', 'fox-child' ),
        'edit_item'         => __( 'Not Türünü Düzenle', 'fox-child' ),
        'update_item'       => __( 'Not Türünü Güncelle', 'fox-child' ),
        'add_new_item'      => __( 'Yeni Not Türü Ekle', 'fox-child' ),
        'new_item_name'     => __( 'Yeni Not Türü Adı', 'fox-child' ),
        'menu_name'         => __( 'Not Türleri', 'fox-child' ),
    );
    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'not-turu' ),
        'show_in_rest'      => true,
    );
    register_taxonomy( 'note_type', array( 'bbb_note' ), $args );
}
add_action( 'init', 'fox_child_register_note_taxonomy' );

function fox_child_rewrite_flush() {
    fox_child_register_note_cpt();
    fox_child_register_note_taxonomy();
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'fox_child_rewrite_flush' );

// =============================================
// === Not İçeriği Hashtag Filtresi ===
// =============================================
function fox_child_convert_hashtags_to_links( $content ) {
    if ( is_singular('bbb_note') && in_the_loop() && is_main_query() ) {
        $pattern = '/(?<=\s|^|\W)#(\p{L}|\p{N})([\p{L}\p{N}_]*)/u';
        $replacement = '<a href="#" class="note-hashtag-link" data-hashtag="$1$2">#$1$2</a>';
        $content = preg_replace( $pattern, $replacement, $content );
    }
    return $content;
}
add_filter( 'the_content', 'fox_child_convert_hashtags_to_links', 11 );

// =============================================
// === Not İçeriği REST API Endpoint ===
// =============================================
function fox_child_register_note_api_endpoint() {
    register_rest_route( 'bbb-notes/v1', '/note/(?P<id>\d+)', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'fox_child_get_note_data_for_modal',
        'permission_callback' => '__return_true',
        'args'                => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric( $param );
                }
            ),
        ),
    ) );
}
add_action( 'rest_api_init', 'fox_child_register_note_api_endpoint' );

function fox_child_register_note_list_api_endpoint() {
    register_rest_route( 'bbb-notes/v1', '/list', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'fox_child_get_notes_list',
        'permission_callback' => '__return_true',
        'args'                => array(
            'page' => array(
                'validate_callback' => 'is_numeric',
                'default'           => 1,
            ),
            'per_page' => array(
                'validate_callback' => 'is_numeric',
                'default'           => 10,
            ),
            'note_type' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'search' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'order' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );
}
add_action( 'rest_api_init', 'fox_child_register_note_list_api_endpoint' );

function fox_child_get_notes_list( WP_REST_Request $request ) {
    $page     = max( 1, intval( $request['page'] ) );
    $per_page = max( 1, intval( $request['per_page'] ) );

    $args = array(
        'post_type'      => 'bbb_note',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'orderby'        => 'date',
        'order'          => ( strtolower( $request['order'] ) === 'asc' ) ? 'ASC' : 'DESC',
    );

    if ( ! empty( $request['search'] ) ) {
        $args['s'] = sanitize_text_field( $request['search'] );
    }

    if ( ! empty( $request['note_type'] ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'note_type',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $request['note_type'] ),
            ),
        );
    }

    $query = new WP_Query( $args );

    $items = array();
    foreach ( $query->posts as $post ) {
        $terms = get_the_terms( $post->ID, 'note_type' );
        $types = array();
        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            foreach ( $terms as $term ) {
                $types[] = array(
                    'slug'  => $term->slug,
                    'name'  => $term->name,
                    'color' => fox_child_get_color_for_slug( $term->slug ),
                );
            }
        }
        $items[] = array(
            'id'        => $post->ID,
            'title'     => get_the_title( $post ),
            'date'      => get_the_date( '', $post ),
            'timestamp' => get_the_date( 'U', $post ),
            'types'     => $types,
        );
    }

    return new WP_REST_Response(
        array(
            'notes'     => $items,
            'max_pages' => $query->max_num_pages,
        ),
        200
    );
}

function fox_child_get_note_data_for_modal( WP_REST_Request $request ) {
    $note_id = (int) $request['id'];
    $note = get_post( $note_id );

    if ( ! $note || $note->post_type !== 'bbb_note' ) {
        return new WP_Error( 'rest_note_not_found', __( 'Not bulunamadı.', 'fox-child' ), array( 'status' => 404 ) );
    }

    $raw_content = $note->post_content;

    // Apply hashtag linking
    $pattern_hashtag = '/(?<=\s|^|\W)#(\p{L}|\p{N})([\p{L}\p{N}_]*)/u';
    $replacement_hashtag = '<a href="#" class="note-hashtag-link" data-hashtag="$1$2">#$1$2</a>';
    $content_with_hashtags = preg_replace( $pattern_hashtag, $replacement_hashtag, $raw_content );

    // Apply 'the_content' filters (for oEmbed, shortcodes, etc.)
    $content_final = apply_filters( 'the_content', $content_with_hashtags );
    
    // Clean up srcset and sizes attributes from images to prevent layout shifts in the modal
    $pattern_cleanup = '/(<img[^>]+(?:>|\/>))\s*(srcset="[^"]*"\s*sizes="[^"]*")/is';
    $content_final = preg_replace( $pattern_cleanup, '$1', $content_final );

    // Get note types
    $note_terms = get_the_terms( $note_id, 'note_type' );
    $note_types_data = array();
    if ( ! is_wp_error($note_terms) && ! empty($note_terms) ) {
        foreach ( $note_terms as $term ) {
            $note_types_data[] = array(
                'name'  => esc_html( $term->name ),
                'slug'  => esc_html( $term->slug ),
                'color' => fox_child_get_color_for_slug( $term->slug ),
            );
        }
    }

    $response_data = array(
        'id'      => $note->ID,
        'title'   => get_the_title( $note ),
        'content' => $content_final,
        'date'    => get_the_date( '', $note ),
        'note_types' => $note_types_data,
    );

    return new WP_REST_Response( $response_data, 200 );
}

// =============================================
// === Podcast Fonksiyonları ===
// =============================================
function fox_child_get_podcast_data( $feed_url, $cache_duration = HOUR_IN_SECONDS ) {
    if ( ! function_exists( 'fetch_feed' ) ) {
        include_once( ABSPATH . WPINC . '/feed.php' );
    }
    if ( ! filter_var( $feed_url, FILTER_VALIDATE_URL ) ) {
        error_log( 'Invalid Podcast Feed URL: ' . $feed_url );
        return false;
    }
    $transient_key = 'podcast_feed_' . md5( $feed_url );
    $podcast_data = get_transient( $transient_key );
    if ( false === $podcast_data || ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
        $feed = fetch_feed( $feed_url );
        if ( is_wp_error( $feed ) ) {
            error_log( 'Podcast Feed Error (' . $feed_url . '): ' . $feed->get_error_message() );
            set_transient( $transient_key, array( 'error' => true, 'message' => $feed->get_error_message() ), MINUTE_IN_SECONDS * 5 );
            return false;
        }
        if ( ! $feed->get_item_quantity() ) {
            error_log( 'Podcast Feed Error (' . $feed_url . '): No items found.' );
            set_transient( $transient_key, array( 'error' => true, 'no_items' => true ), MINUTE_IN_SECONDS * 15 );
            return false;
        }
        $podcast_data = array( 'latest_episode' => null, 'episode_list' => array() );
        $items = $feed->get_items( 0, 105 ); // Fetch a good number of items
        foreach ( $items as $item ) {
            $enclosure = $item->get_enclosure();
            if ( $enclosure && $enclosure->get_link() ) {
                $episode_data = array(
                    'title'    => esc_html( $item->get_title() ),
                    'url'      => esc_url( $enclosure->get_link() ),
                    'duration' => fox_child_format_duration( $enclosure->get_duration() ),
                );
                if ( ! empty( $episode_data['url'] ) ) {
                    $podcast_data['episode_list'][] = $episode_data;
                    if ( $podcast_data['latest_episode'] === null ) {
                        $podcast_data['latest_episode'] = $episode_data;
                    }
                }
            }
        }
        if ( ! empty( $podcast_data['latest_episode'] ) || ! empty( $podcast_data['episode_list'] ) ) {
            set_transient( $transient_key, $podcast_data, $cache_duration );
        } else {
            error_log( 'Podcast Feed Warning (' . $feed_url . '): No valid enclosures found in items.' );
            $podcast_data = array( 'error' => true, 'no_enclosures' => true );
            set_transient( $transient_key, $podcast_data, MINUTE_IN_SECONDS * 15 );
        }
        if ( method_exists( $feed, '__destruct' ) ) {
            $feed->__destruct();
        }
        unset( $feed );
    }
    if ( is_array( $podcast_data ) && isset( $podcast_data['error'] ) ) {
        return array( 'latest_episode' => null, 'episode_list' => array() );
    }
    return $podcast_data;
}

function fox_child_format_duration( $duration ) {
    if ( empty( $duration ) ) { return ''; }
    $seconds = 0;
    if ( is_string( $duration ) && strpos( $duration, ':' ) !== false ) {
        $parts = array_reverse( explode( ':', $duration ) );
        if ( isset( $parts[0] ) ) { $seconds += (int) $parts[0]; }
        if ( isset( $parts[1] ) ) { $seconds += ( (int) $parts[1] * 60 ); }
        if ( isset( $parts[2] ) ) { $seconds += ( (int) $parts[2] * 3600 ); }
    } elseif ( is_numeric( $duration ) ) {
        $seconds = (int) $duration;
    } else {
        return '';
    }
    if ( $seconds <= 0 ) { return ''; }
    $minutes = floor( $seconds / 60 );
    $remaining_seconds = $seconds % 60;
    return sprintf( '%01d:%02d', $minutes, $remaining_seconds );
}

// =============================================
// === Son Haberler API Fonksiyonları ===
// =============================================
function fox_child_get_latest_news_data( $count = 6, $cache_duration = 300 ) {
    $count = max( 1, min( 50, intval( $count ) ) );
    $transient_key = 'latest_news_data_' . $count;
    $cached_data = get_transient( $transient_key );
    if ( false === $cached_data || ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
        $base_api_url = 'https://borsadabibasina.com/wp-json/bbb-news/v1/latest';
        $api_url = add_query_arg( 'per_page', 100, $base_api_url );
        $args = array( 'timeout' => 20, 'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ), 'sslverify' => true, );
        $response = wp_remote_get( $api_url, $args );
        if ( is_wp_error( $response ) ) {
            error_log( 'BBB News API wp_remote_get Error: ' . $response->get_error_message() . ' | URL: ' . $api_url );
            set_transient( $transient_key, $response, MINUTE_IN_SECONDS );
            return $response;
        }
        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $data_all = json_decode( $response_body, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            error_log( '[ERROR] Failed to decode JSON from external API. Error: ' . json_last_error_msg() . ' | URL: ' . $api_url . ' | Body: ' . $response_body );
            $error = new WP_Error( 'json_decode_error', 'API\'den gelen veri çözümlenemedi.', array( 'status' => 500 ) );
            set_transient( $transient_key, $error, MINUTE_IN_SECONDS );
            return $error;
        }
        $actual_news_items = null;
        if ( is_array( $data_all ) ) {
            if ( isset( $data_all[0]['title'] ) && isset( $data_all[0]['link'] ) && isset( $data_all[0]['date'] ) ) {
                $actual_news_items = $data_all;
            } elseif ( isset( $data_all['data'] ) && is_array( $data_all['data'] ) ) {
                $actual_news_items = $data_all['data'];
            } elseif ( isset( $data_all['posts'] ) && is_array( $data_all['posts'] ) ) {
                $actual_news_items = $data_all['posts'];
            } else {
                $actual_news_items = $data_all;
            }
        }
        if ( $response_code >= 400 || ! is_array( $actual_news_items ) ) {
            error_log( '[ERROR] Invalid response code or data format from external API. Code: ' . $response_code . ' | Expected array, got: ' . gettype( $actual_news_items ) . ' | Body: ' . $response_body );
            $error_message = 'API isteği başarısız oldu (Kod: ' . $response_code . ').';
            $error_code = 'api_error';
            $status_code = $response_code >= 400 ? $response_code : 500;
            if ( is_array( $data_all ) && isset( $data_all['message'] ) ) {
                $error_message = sanitize_text_field( $data_all['message'] );
                if ( isset( $data_all['code'] ) ) {
                    $error_code = sanitize_key( $data_all['code'] );
                }
            } elseif ( ! is_array( $actual_news_items ) && $response_code < 400 ) {
                $error_message = 'API\'den beklenen veri formatı alınamadı.';
                $error_code = 'api_data_format_error';
            }
            $error = new WP_Error( $error_code, $error_message, array( 'status' => $status_code ) );
            set_transient( $transient_key, $error, MINUTE_IN_SECONDS );
            return $error;
        }
        $data_sliced = array_slice( $actual_news_items, 0, $count );
        set_transient( $transient_key, $data_sliced, $cache_duration );
        $cached_data = $data_sliced;
    }
    if ( is_wp_error( $cached_data ) ) {
        return $cached_data;
    }
    if ( ! is_array( $cached_data ) ) {
        error_log( '[WARNING] Cached data for news is not an array, returning empty array. Transient key: ' . $transient_key );
        return [];
    }
    return $cached_data;
}

add_action( 'rest_api_init', 'fox_child_register_latest_news_endpoint' );
function fox_child_register_latest_news_endpoint() {
    register_rest_route( 'bbb-news/v1', '/latest', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'fox_child_rest_get_latest_news',
        'permission_callback' => '__return_true',
        'args'                => array(
            'per_page' => array(
                'description'       => __( 'Döndürülecek maksimum öğe sayısı.', 'fox-child' ),
                'type'              => 'integer',
                'default'           => 6,
                'sanitize_callback' => 'absint',
                'validate_callback' => function( $param, $request, $key ) {
                    return is_numeric( $param ) && $param > 0 && $param <= 50;
                },
            ),
        ),
    ) );
}

function fox_child_rest_get_latest_news( WP_REST_Request $request ) {
    $count = $request->get_param( 'per_page' );
    $news_data = fox_child_get_latest_news_data( $count );
    if ( is_wp_error( $news_data ) ) {
        $error_data = $news_data->get_error_data();
        $status_code = isset( $error_data['status'] ) && is_int( $error_data['status'] ) ? $error_data['status'] : 500;
        return new WP_REST_Response( array(
            'success' => false,
            'message' => $news_data->get_error_message(),
            'code'    => $news_data->get_error_code(),
        ), $status_code );
    }
    if ( empty( $news_data ) || ! is_array( $news_data ) ) {
        return new WP_REST_Response( array(
            'success' => true,
            'data'    => [],
            'message' => esc_html__( 'Gösterilecek haber bulunamadı.', 'fox-child' ),
            'code'    => 'no_data_found',
        ), 200 );
    }
    $processed_data = array();
    foreach ( $news_data as $item ) {
        $is_title_set = isset( $item['title'] ) && ! empty( trim( $item['title'] ) );
        $is_link_set = isset( $item['link'] );
        $is_link_valid = $is_link_set && filter_var( $item['link'], FILTER_VALIDATE_URL );
        $is_date_set = isset( $item['date'] ) && ! empty( $item['date'] );
        if ( $is_title_set && $is_link_set && $is_link_valid && $is_date_set ) {
            $time_ago_string = fox_child_time_ago( $item['date'] );
            $processed_item = array(
                'title'    => wp_strip_all_tags( $item['title'] ),
                'link'     => esc_url( $item['link'] ),
                'time_ago' => $time_ago_string,
            );
            $processed_data[] = $processed_item;
        }
    }
    if ( empty( $processed_data ) ) {
        return new WP_REST_Response( array(
            'success' => true,
            'data'    => [],
            'message' => esc_html__( 'Gösterilecek haber bulunamadı.', 'fox-child' ),
            'code'    => 'no_valid_data_found',
        ), 200 );
    }
    return new WP_REST_Response( array(
        'success' => true,
        'data'    => $processed_data,
    ), 200 );
}

function fox_child_time_ago( $datetime_string ) {
    if ( empty( $datetime_string ) ) {
        return '';
    }
    try {
        $timestamp = false;
        $timestamp_maybe_gmt = strtotime( $datetime_string );
        if ( false !== $timestamp_maybe_gmt ) {
            $timestamp = $timestamp_maybe_gmt;
        } else {
            $site_timezone = wp_timezone();
            $date = date_create( $datetime_string, $site_timezone );
            if ( $date ) {
                $timestamp = $date->getTimestamp();
            } else {
                $date_utc = date_create( $datetime_string, new DateTimeZone('UTC') );
                if ( $date_utc ) {
                    $timestamp = $date_utc->getTimestamp();
                } else {
                    throw new Exception( 'Invalid date format or unable to parse: ' . $datetime_string );
                }
            }
        }
        if ( false === $timestamp ) {
            throw new Exception( 'Could not convert date string to timestamp: ' . $datetime_string );
        }
        $current_gmt_timestamp = current_time( 'timestamp', true );
        $time_diff = $current_gmt_timestamp - $timestamp;
        if ( $time_diff < 0 ) {
            return '';
        }
        if ( $time_diff < MINUTE_IN_SECONDS ) {
            return __( 'az önce', 'fox-child' );
        } elseif ( $time_diff < HOUR_IN_SECONDS ) {
            $mins = round( $time_diff / MINUTE_IN_SECONDS );
            return sprintf( _n( '%s dk', '%s dk', $mins, 'fox-child' ), $mins );
        } elseif ( $time_diff < DAY_IN_SECONDS ) {
            $hours = round( $time_diff / HOUR_IN_SECONDS );
            return sprintf( _n( '%s sa', '%s sa', $hours, 'fox-child' ), $hours );
        } elseif ( $time_diff < ( DAY_IN_SECONDS * 7 ) ) {
            $days = round( $time_diff / DAY_IN_SECONDS );
            return sprintf( _n( '%s gün', '%s gün', $days, 'fox-child' ), $days );
        } else {
            return date_i18n( 'j M', $timestamp );
        }
    } catch ( Exception $e ) {
        error_log( 'Time Ago Error: ' . $e->getMessage() . ' | Input: ' . $datetime_string );
        return '';
    }
}

// =============================================
// === Portföy Fiyat Otomasyonu ===
// =============================================
function fox_child_scrape_google_finance_price( $google_finance_code ) {
    if ( empty( $google_finance_code ) ) { return false; }
    $url = sprintf( 'https://www.google.com/finance/quote/%s?hl=tr', esc_attr( $google_finance_code ) );
    $args = array( 'timeout' => 15, 'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36', 'sslverify' => true, );
    $response = wp_remote_get( $url, $args );
    if ( is_wp_error( $response ) ) {
        error_log( '[Portfolio Price Scrape] wp_remote_get Error for ' . $google_finance_code . ': ' . $response->get_error_message() );
        return false;
    }
    $response_code = wp_remote_retrieve_response_code( $response );
    if ( $response_code !== 200 ) {
        error_log( '[Portfolio Price Scrape] Non-200 Response Code for ' . $google_finance_code . ': ' . $response_code );
        return false;
    }
    $body = wp_remote_retrieve_body( $response );
    if ( empty( $body ) ) {
        error_log( '[Portfolio Price Scrape] Empty response body for ' . $google_finance_code );
        return false;
    }
    libxml_use_internal_errors( true );
    $dom = new DOMDocument();
    @$dom->loadHTML( $body );
    libxml_clear_errors();
    $xpath = new DOMXPath( $dom );
    $price_query = "//div[contains(@class, 'YMlKec') and contains(@class, 'fxKbKc')]";
    $price_elements = $xpath->query( $price_query );
    if ( $price_elements === false || $price_elements->length === 0 ) {
        error_log( '[Portfolio Price Scrape] Price element not found with query "' . $price_query . '" for ' . $google_finance_code );
        return false;
    }
    $price_string = $price_elements->item( 0 )->nodeValue;
    $cleaned_price = preg_replace( '/[^\d,.]/', '', $price_string );
    if ( strpos( $cleaned_price, ',' ) !== false && strpos( $cleaned_price, '.' ) !== false ) {
        if ( strrpos( $cleaned_price, ',' ) > strrpos( $cleaned_price, '.' ) ) {
            $cleaned_price = str_replace( '.', '', $cleaned_price );
            $cleaned_price = str_replace( ',', '.', $cleaned_price );
        } else {
            $cleaned_price = str_replace( ',', '', $cleaned_price );
        }
    } elseif ( strpos( $cleaned_price, ',' ) !== false ) {
        $cleaned_price = str_replace( ',', '.', $cleaned_price );
    }
    $price_float = floatval( $cleaned_price );
    if ( $price_float > 0 ) {
        return $price_float;
    } else {
        error_log( '[Portfolio Price Scrape] Could not parse price string "' . $price_string . '" to float for ' . $google_finance_code );
        return false;
    }
}

function fox_child_run_portfolio_price_update() {
    error_log( '[CRON/MANUAL V3] Starting portfolio price update job.' );
    $options = get_option( 'fox_child_portfolio_options' );
    $results = array( 'success' => 0, 'error' => 0, 'no_change' => 0 );
    if ( ! isset( $options['assets'] ) || ! is_array( $options['assets'] ) || empty( $options['assets'] ) ) {
        error_log( '[CRON/MANUAL V3] No assets found in portfolio options. Exiting job.' );
        return $results;
    }
    $usd_try_rate = fox_child_scrape_google_finance_price( 'USD-TRY' );
    if ( $usd_try_rate === false ) {
        error_log( '[CRON/MANUAL V3] FATAL: Failed to fetch USD-TRY rate. Cannot perform conversions. Exiting job.' );
        $results['error'] = count( $options['assets'] );
        return $results;
    }
    $updated = false;
    foreach ( $options['assets'] as $index => $asset ) {
        $symbol = $asset['symbol'] ?? '';
        $currency = $asset['currency'] ?? 'TRY';
        $current_try_price = null;
        $fetch_failed = false;
        if ( empty( $symbol ) ) {
            continue;
        }
        if ( $currency === 'TRY' && ( $asset['class'] ?? '' ) === 'Nakit' ) {
            $scraped_price = 1.0;
        } else {
            $scraped_price = fox_child_scrape_google_finance_price( $symbol );
        }
        if ( $scraped_price === false ) {
            error_log( '[CRON/MANUAL V3] Failed to fetch price for ' . $symbol . '. Keeping old price.' );
            $results['error']++;
            $fetch_failed = true;
            usleep( 200000 );
            continue;
        }
        try {
            switch ( $currency ) {
                case 'TRY':
                    $current_try_price = $scraped_price;
                    break;
                case 'USD':
                    $current_try_price = $scraped_price * $usd_try_rate;
                    break;
                case 'USD/Ounce':
                    if ( defined( 'OUNCES_TO_GRAMS' ) && OUNCES_TO_GRAMS > 0 ) {
                        $current_try_price = ( $scraped_price / OUNCES_TO_GRAMS ) * $usd_try_rate;
                    } else {
                        error_log( "[CRON/MANUAL V3] OUNCES_TO_GRAMS constant not defined or invalid for {$symbol}. Skipping conversion." );
                        $results['error']++;
                        $fetch_failed = true;
                    }
                    break;
                default:
                    error_log( "[CRON/MANUAL V3] Skipping {$symbol} - Unknown currency selected: {$currency}" );
                    $results['error']++;
                    $fetch_failed = true;
            }
        } catch ( Exception $e ) {
            error_log( "[CRON/MANUAL V3] Exception processing {$symbol}: " . $e->getMessage() );
            $results['error']++;
            $fetch_failed = true;
        }
        if ( $fetch_failed ) {
            usleep( 200000 );
            continue;
        }
        if ( $current_try_price !== null ) {
            $old_price = isset( $asset['current_price'] ) ? floatval( $asset['current_price'] ) : 0;
            if ( abs( $current_try_price - $old_price ) > 0.001 ) {
                $options['assets'][ $index ]['current_price'] = round( $current_try_price, 4 );
                $updated = true;
                $results['success']++;
            } else {
                $results['no_change']++;
            }
        }
        usleep( 500000 );
    }
    if ( $updated || $results['error'] > 0 ) {
        $options['last_auto_update'] = current_time( 'timestamp', true );
        update_option( 'fox_child_portfolio_options', $options );
        if ( $updated ) {
            error_log( '[CRON/MANUAL V3] Portfolio prices updated. Success: ' . $results['success'] . ', No Change: ' . $results['no_change'] . ', Error: ' . $results['error'] . '. Options saved.' );
        } else {
            error_log( '[CRON/MANUAL V3] No significant price changes, but errors occurred (' . $results['error'] . '). Options saved with updated timestamp.' );
        }
    } else {
        error_log( '[CRON/MANUAL V3] No significant price changes and no errors. Options not saved.' );
    }
    error_log( '[CRON/MANUAL V3] Finished portfolio price update job.' );
    return $results;
}

function fox_child_update_portfolio_prices_cron_job() {
    fox_child_run_portfolio_price_update();
}
add_action( 'fox_child_update_portfolio_prices_event', 'fox_child_update_portfolio_prices_cron_job' );

add_action( 'admin_action_fox_child_manual_update_prices', 'fox_child_handle_manual_price_update' );
function fox_child_handle_manual_price_update() {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'fox_child_manual_update_prices_nonce' ) ) {
        wp_die( esc_html__( 'Güvenlik kontrolü başarısız.', 'fox-child' ) );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Bu işlemi yapma yetkiniz yok.', 'fox-child' ) );
    }
    $results = fox_child_run_portfolio_price_update();
    set_transient( 'fox_child_manual_update_results', $results, 60 );
    wp_redirect( admin_url( 'admin.php?page=fox_child_portfolio_settings&updated=manual' ) );
    exit;
}

add_action( 'admin_notices', 'fox_child_show_manual_update_notices' );
function fox_child_show_manual_update_notices() {
    if ( ! function_exists( 'get_current_screen' ) || ! get_current_screen() || get_current_screen()->id !== 'toplevel_page_fox_child_portfolio_settings' ) {
        return;
    }
    $results = get_transient( 'fox_child_manual_update_results' );
    if ( $results !== false ) {
        delete_transient( 'fox_child_manual_update_results' );
        $message = '';
        $type = 'info';
        if ( $results['success'] > 0 && $results['error'] === 0 ) {
            $message = sprintf( esc_html__( 'Portföy fiyatları manuel olarak başarıyla güncellendi. %d varlık güncellendi, %d varlıkta değişiklik yok.', 'fox-child' ), $results['success'], $results['no_change'] );
            $type = 'success';
        } elseif ( $results['success'] > 0 && $results['error'] > 0 ) {
            $message = sprintf( esc_html__( 'Portföy fiyatları kısmen güncellendi. %d varlık güncellendi, %d varlıkta değişiklik yok, %d varlık güncellenemedi. Detaylar için hata loglarını kontrol edin.', 'fox-child' ), $results['success'], $results['no_change'], $results['error'] );
            $type = 'warning';
        } elseif ( $results['success'] === 0 && $results['error'] > 0 ) {
            $message = sprintf( esc_html__( 'Portföy fiyatları güncellenemedi. %d varlıkta hata oluştu. Detaylar için hata loglarını kontrol edin.', 'fox-child' ), $results['error'] );
            $type = 'error';
        } elseif ( $results['success'] === 0 && $results['error'] === 0 && $results['no_change'] > 0 ) {
            $message = esc_html__( 'Manuel güncelleme çalıştı ancak önemli bir fiyat değişikliği tespit edilmedi.', 'fox-child' );
            $type = 'info';
        } else {
            $message = esc_html__( 'Manuel güncelleme işlemi bilinmeyen bir durumla sonuçlandı.', 'fox-child' );
            $type = 'warning';
        }
        add_settings_error( 'fox_child_manual_update_notices', esc_attr( 'settings_updated' ), $message, $type );
    }
    settings_errors( 'fox_child_manual_update_notices' );
}

function fox_child_schedule_portfolio_update_cron() {
    if ( ! wp_next_scheduled( 'fox_child_update_portfolio_prices_event' ) ) {
        wp_schedule_event( time(), '15_minutes', 'fox_child_update_portfolio_prices_event' );
        error_log( '[CRON V3] Portfolio price update event scheduled.' );
    }
}
add_action( 'after_switch_theme', 'fox_child_schedule_portfolio_update_cron' );

function fox_child_clear_portfolio_update_cron() {
    wp_clear_scheduled_hook( 'fox_child_update_portfolio_prices_event' );
    error_log( '[CRON V3] Portfolio price update event cleared.' );
}
add_action( 'switch_theme', 'fox_child_clear_portfolio_update_cron' );

// =============================================
// === Gösterge Paneli Veri Otomasyonu ===
// =============================================
function fox_child_parse_finance_number( $number_string ) {
    if ( empty( $number_string ) ) return false;
    $cleaned = preg_replace( '/[^\d,.-]/', '', $number_string );
    $last_comma = strrpos( $cleaned, ',' );
    $last_dot = strrpos( $cleaned, '.' );
    if ( $last_comma !== false && $last_dot !== false ) {
        if ( $last_comma > $last_dot ) {
            $cleaned = str_replace( '.', '', $cleaned );
            $cleaned = str_replace( ',', '.', $cleaned );
        } else {
            $cleaned = str_replace( ',', '', $cleaned );
        }
    } elseif ( $last_comma !== false ) {
        $cleaned = str_replace( ',', '.', $cleaned );
    }
    if ( is_numeric( $cleaned ) ) {
        return floatval( $cleaned );
    } else {
        error_log( '[Finance Parse] Could not parse "' . $number_string . '" to float. Cleaned: "' . $cleaned . '"' );
        return false;
    }
}

function fox_child_scrape_google_finance_quote( $google_finance_code ) {
    if ( empty( $google_finance_code ) ) { return false; }
    $url = sprintf( 'https://www.google.com/finance/quote/%s?hl=tr', esc_attr( $google_finance_code ) );
    $args = array(
        'timeout' => 15,
        'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36',
        'sslverify'   => true,
        'headers'     => [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control'   => 'max-age=0',
            'Referer'         => 'https://www.google.com/',
        ],
    );
    $response = wp_remote_get( $url, $args );
    if ( is_wp_error( $response ) ) {
        error_log( '[Dashboard Scrape v2] wp_remote_get Error for ' . $google_finance_code . ': ' . $response->get_error_message() );
        return false;
    }
    $response_code = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );
    if ( $response_code !== 200 || empty( $body ) ) {
        error_log( '[Dashboard Scrape v2] Non-200 Response Code or Empty Body for ' . $google_finance_code . ': Code ' . $response_code );
        return false;
    }
    libxml_use_internal_errors( true );
    $dom = new DOMDocument();
    @$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $body );
    libxml_clear_errors();
    $xpath = new DOMXPath( $dom );
    $price_query = "//div[contains(@class, 'YMlKec') and contains(@class, 'fxKbKc')]";
    $price_elements = $xpath->query( $price_query );
    $price_string = ( $price_elements && $price_elements->length > 0 ) ? trim( $price_elements->item( 0 )->nodeValue ) : null;
    $price_float = fox_child_parse_finance_number( $price_string );
    if ( $price_float === false ) {
        error_log( '[Dashboard Scrape v2] Current Price not found or could not be parsed for ' . $google_finance_code . ' using query: ' . $price_query );
        return false;
    }
    $prev_close_query = '//*[@id="yDmH0d"]/c-wiz[2]/div/div[4]/div/main/div[2]/div[2]/div/div[1]/div[2]/div';
    $prev_close_elements = $xpath->query( $prev_close_query );
    $prev_close_string = ( $prev_close_elements && $prev_close_elements->length > 0 ) ? trim( $prev_close_elements->item( 0 )->nodeValue ) : null;
    $prev_close_float = fox_child_parse_finance_number( $prev_close_string );
    if ($prev_close_float === false) {
        error_log( '[Dashboard Scrape v2] Previous Close not found or could not be parsed for ' . $google_finance_code . ' using query: ' . $prev_close_query . '. Change calculation skipped.' );
    }
    $change_pct_string = '';
    $direction = 'none';
    if ( $prev_close_float !== false && $prev_close_float > 0 ) {
        $change_pct_float = ( ( $price_float - $prev_close_float ) / $prev_close_float ) * 100;
        $change_pct_string = sprintf( '%+.2f%%', $change_pct_float );
        $change_pct_string = str_replace( '.', ',', $change_pct_string );
        if ( $change_pct_float > 0.001 ) {
            $direction = 'up';
        } elseif ( $change_pct_float < -0.001 ) {
            $direction = 'down';
        } else {
            $direction = 'none';
        }
    } else {
        $change_pct_string = '';
        $direction = 'none';
    }
    $formatted_price = $price_string ? preg_replace('/[^\d,.]/','',$price_string) : 'N/A';
    return [
        'price' => $formatted_price,
        'change_pct' => $change_pct_string,
        'direction'  => $direction,
    ];
}

function fox_child_update_dashboard_data_cron_job() {
    error_log( '[DASHBOARD CRON v2] Starting Ticker/Sidebar data update job.' );
    $ticker_options = get_option( 'fox_child_ticker_options', ['items' => []] );
    $sidebar_options = get_option( 'fox_child_sidebar_options', ['items' => []] );
    $ticker_data = [];
    $sidebar_data = [];
    $fetch_errors = 0;
    if ( isset( $ticker_options['items'] ) && is_array( $ticker_options['items'] ) ) {
        foreach ( $ticker_options['items'] as $item ) {
            $display_name = $item['display_name'] ?? '';
            $google_code = $item['google_code'] ?? '';
            if ( ! empty( $display_name ) && ! empty( $google_code ) ) {
                $quote = fox_child_scrape_google_finance_quote( $google_code );
                if ( $quote !== false ) {
                    $ticker_data[] = [
                        'name' => $display_name,
                        'value' => $quote['price'],
                        'change_pct' => $quote['change_pct'],
                        'direction'  => $quote['direction'],
                    ];
                } else {
                    $fetch_errors++;
                    error_log( '[DASHBOARD CRON v2] Failed to fetch quote for Ticker item: ' . $google_code );
                    $ticker_data[] = ['name' => $display_name, 'value' => 'N/A', 'change_pct' => '', 'direction' => 'none'];
                }
                usleep( 300000 );
            }
        }
    }
    if ( isset( $sidebar_options['items'] ) && is_array( $sidebar_options['items'] ) ) {
        foreach ( $sidebar_options['items'] as $item ) {
            $display_name = $item['display_name'] ?? '';
            $google_code = $item['google_code'] ?? '';
            if ( ! empty( $display_name ) && ! empty( $google_code ) ) {
                $quote = fox_child_scrape_google_finance_quote( $google_code );
                if ( $quote !== false ) {
                    $sidebar_data[] = [
                        'name' => $display_name,
                        'value' => $quote['price'],
                        'change_pct' => $quote['change_pct'],
                        'direction'  => $quote['direction'],
                    ];
                } else {
                    $fetch_errors++;
                    error_log( '[DASHBOARD CRON v2] Failed to fetch quote for Sidebar item: ' . $google_code );
                    $sidebar_data[] = ['name' => $display_name, 'value' => 'N/A', 'change_pct' => '', 'direction' => 'none'];
                }
                usleep( 300000 );
            }
        }
    }
    set_transient( 'fox_child_ticker_data', $ticker_data, MINUTE_IN_SECONDS * 10 );
    set_transient( 'fox_child_sidebar_data', $sidebar_data, MINUTE_IN_SECONDS * 10 );
    if ( $fetch_errors > 0 ) {
        error_log( '[DASHBOARD CRON v2] Finished Ticker/Sidebar data update job with ' . $fetch_errors . ' fetch errors.' );
    } else {
        error_log( '[DASHBOARD CRON v2] Finished Ticker/Sidebar data update job successfully.' );
    }
}
add_action( 'fox_child_update_dashboard_data_event', 'fox_child_update_dashboard_data_cron_job' );

add_filter( 'cron_schedules', 'fox_child_add_5min_cron_interval' );
function fox_child_add_5min_cron_interval( $schedules ) {
    if ( ! isset( $schedules['5_minutes'] ) ) {
        $schedules['5_minutes'] = array(
            'interval' => 300,
            'display'  => esc_html__( 'Every 5 Minutes' )
        );
    }
    if ( ! isset( $schedules['15_minutes'] ) ) {
        $schedules['15_minutes'] = array(
            'interval' => 900,
            'display'  => esc_html__( 'Every 15 Minutes' )
        );
    }
    return $schedules;
}

// =============================================
// === (BİTİŞ) Gösterge Paneli Veri Otomasyonu ===
// =============================================

// =============================================
// === Portföy Ayarları Admin Sayfası ===
// =============================================
function fox_child_get_portfolio_currencies() {
    return array(
        'TRY'       => __('Türk Lirası (TRY)', 'fox-child'),
        'USD'       => __('ABD Doları (USD)', 'fox-child'),
        'USD/Ounce' => __('ABD Doları / Ons (Altın vb.)', 'fox-child'),
    );
}
function fox_child_get_asset_classes() {
    return array('Hisse', 'Emtia', 'Kripto', 'ETF', 'Nakit', 'Fon', 'Diğer');
}

add_action( 'admin_menu', 'fox_child_portfolio_admin_menu' );
function fox_child_portfolio_admin_menu() {
    add_menu_page(
        __('Portföy Ayarları', 'fox-child'),
        __('Portföy Ayarları', 'fox-child'),
        'manage_options',
        'fox_child_portfolio_settings',
        'fox_child_portfolio_settings_page_html',
        'dashicons-chart-pie',
        25
    );
}

add_action( 'admin_init', 'fox_child_portfolio_settings_init' );
function fox_child_portfolio_settings_init() {
    register_setting( 'fox_child_portfolio_settings_group', 'fox_child_portfolio_options', 'fox_child_portfolio_options_sanitize' );
    
    add_settings_section( 'fox_child_portfolio_stats_section', __('Genel İstatistikler', 'fox-child'), 'fox_child_portfolio_stats_section_callback', 'fox_child_portfolio_settings' );
    add_settings_field( 'total_return_text', __('Toplam Getiri Metni', 'fox-child'), 'fox_child_portfolio_field_callback', 'fox_child_portfolio_settings', 'fox_child_portfolio_stats_section', ['id' => 'total_return_text', 'type' => 'text', 'group' => 'stats'] );
    add_settings_field( 'benchmark_outperformance_text', __('Benchmark Üzeri Metni', 'fox-child'), 'fox_child_portfolio_field_callback', 'fox_child_portfolio_settings', 'fox_child_portfolio_stats_section', ['id' => 'benchmark_outperformance_text', 'type' => 'text', 'group' => 'stats'] );
    add_settings_field( 'real_return_text', __('Reel Getiri Metni', 'fox-child'), 'fox_child_portfolio_field_callback', 'fox_child_portfolio_settings', 'fox_child_portfolio_stats_section', ['id' => 'real_return_text', 'type' => 'text', 'group' => 'stats'] );
    add_settings_field( 'last_updated_date', __('Son Güncelleme Tarihi (Manuel)', 'fox-child'), 'fox_child_portfolio_field_callback', 'fox_child_portfolio_settings', 'fox_child_portfolio_stats_section', ['id' => 'last_updated_date', 'type' => 'date', 'group' => 'stats', 'description' => __('Portföy sayfasında gösterilen manuel güncelleme tarihi.', 'fox-child')] );
    
    add_settings_section( 'fox_child_portfolio_assets_section', __('Portföy Varlıkları', 'fox-child'), 'fox_child_portfolio_assets_section_callback', 'fox_child_portfolio_settings' );
    
    add_settings_section( 'fox_child_portfolio_performance_section', __('Performans Verileri (Aylık/Dönemlik)', 'fox-child'), 'fox_child_portfolio_performance_section_callback', 'fox_child_portfolio_settings' );
}

function fox_child_portfolio_stats_section_callback() {
    echo '<p>' . esc_html__('Portföy sayfasının üst kısmında gösterilecek genel bilgileri girin.', 'fox-child') . '</p>';
}

function fox_child_portfolio_field_callback( $args ) {
    $options = get_option('fox_child_portfolio_options');
    $group = $args['group'] ?? null;
    $id = $args['id'];
    $type = $args['type'] ?? 'text';
    $value = '';
    if ( $group && isset($options[$group][$id]) ) {
        $value = $options[$group][$id];
    } elseif ( ! $group && isset($options[$id]) ) {
        $value = $options[$id];
    }
    
    $field_name = 'fox_child_portfolio_options';
    if ($group) {
        $field_name .= '[' . esc_attr($group) . ']';
    }
    $field_name .= '[' . esc_attr($id) . ']';

    switch ($type) {
        case 'date':
            printf('<input type="date" id="%1$s" name="%2$s" value="%3$s" class="regular-text"/>', esc_attr($id), esc_attr($field_name), esc_attr($value));
            break;
        case 'number':
            printf('<input type="number" id="%1$s" name="%2$s" value="%3$s" step="any" class="regular-text"/>', esc_attr($id), esc_attr($field_name), esc_attr($value));
            break;
        case 'textarea':
            printf('<textarea id="%1$s" name="%2$s" rows="5" class="large-text">%3$s</textarea>', esc_attr($id), esc_attr($field_name), esc_textarea($value));
            break;
        case 'text':
        default:
            printf('<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text"/>', esc_attr($id), esc_attr($field_name), esc_attr($value));
            break;
    }

    if (isset($args['description'])) {
        printf('<p class="description">%s</p>', wp_kses_post($args['description']));
    }
}

function fox_child_portfolio_assets_section_callback() {
    $options = get_option('fox_child_portfolio_options');
    $assets = isset($options['assets']) && is_array($options['assets']) ? $options['assets'] : array();
    $asset_classes = fox_child_get_asset_classes();
    $currencies = fox_child_get_portfolio_currencies();
    $last_auto_update_timestamp = $options['last_auto_update'] ?? 0;
    $last_auto_update_display = $last_auto_update_timestamp ? sprintf(esc_html__('Fiyatlar en son %s tarihinde otomatik güncellendi.', 'fox-child'), wp_date(get_option('date_format') . ' ' . get_option('time_format'), $last_auto_update_timestamp)) : esc_html__('Fiyatlar henüz otomatik güncellenmedi.', 'fox-child');

    echo '<p>' . esc_html__('Portföydeki varlıkları girin. Ağırlık ve Kar/Zarar ön yüzde otomatik hesaplanacaktır.', 'fox-child') . '</p>';
    echo '<p><em>' . $last_auto_update_display . '</em></p>';
    echo '<p><strong>' . esc_html__('Sembol Formatları:', 'fox-child') . '</strong><ul>';
    echo '<li>' . esc_html__('Google Finance\'da bulunan geçerli bir kod girin (örn: VAKKO:IST, AAPL:NASDAQ, BTC-USD, GCW00:COMEX, USD-TRY).', 'fox-child') . '</li>';
    echo '<li>' . esc_html__('Aşağıdaki "Fiyat Birimi"ni doğru seçtiğinizden emin olun.', 'fox-child') . '</li>';
    echo '</ul></p>';
    ?>
    <div id="portfolio-assets-repeater">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:12%;"><?php esc_html_e('Varlık Sınıfı', 'fox-child'); ?></th>
                    <th style="width:13%;"><?php esc_html_e('Sembol', 'fox-child'); ?></th>
                    <th style="width:12%;"><?php esc_html_e('Fiyat Birimi', 'fox-child'); ?></th>
                    <th style="width:20%;"><?php esc_html_e('Varlık Adı', 'fox-child'); ?></th>
                    <th style="width:12%; text-align:right;"><?php esc_html_e('Miktar', 'fox-child'); ?></th>
                    <th style="width:12%; text-align:right;"><?php esc_html_e('Ort. Alış Fiyatı (TRY)', 'fox-child'); ?></th>
                    <th style="width:12%; text-align:right;"><?php esc_html_e('Güncel Fiyat (TRY)', 'fox-child'); ?><br><small>(Otomatik)</small></th>
                    <th style="width:5%;"><?php esc_html_e('İşlem', 'fox-child'); ?></th>
                </tr>
            </thead>
            <tbody id="assets-tbody">
                <?php if (!empty($assets)) : foreach ($assets as $index => $asset) : 
                    $current_class = $asset['class'] ?? '';
                    $current_currency = $asset['currency'] ?? 'TRY';
                ?>
                    <tr class="asset-row">
                        <td>
                            <select name="fox_child_portfolio_options[assets][<?php echo $index; ?>][class]" class="widefat">
                                <?php 
                                echo '<option value="">' . esc_html__('-- Seçin --', 'fox-child') . '</option>';
                                foreach ($asset_classes as $class_name) {
                                    echo '<option value="' . esc_attr($class_name) . '" ' . selected($current_class, $class_name, false) . '>' . esc_html($class_name) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                        <td><input type="text" name="fox_child_portfolio_options[assets][<?php echo $index; ?>][symbol]" value="<?php echo esc_attr($asset['symbol'] ?? ''); ?>" class="widefat" placeholder="örn: AAPL:NASDAQ"/></td>
                        <td>
                            <select name="fox_child_portfolio_options[assets][<?php echo $index; ?>][currency]" class="widefat">
                                <?php foreach ($currencies as $code => $label) : ?>
                                    <option value="<?php echo esc_attr($code); ?>" <?php selected($current_currency, $code); ?>><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="text" name="fox_child_portfolio_options[assets][<?php echo $index; ?>][name]" value="<?php echo esc_attr($asset['name'] ?? ''); ?>" class="widefat"/></td>
                        <td><input type="number" step="any" name="fox_child_portfolio_options[assets][<?php echo $index; ?>][quantity]" value="<?php echo esc_attr($asset['quantity'] ?? ''); ?>" class="widefat" style="text-align:right;"/></td>
                        <td><input type="number" step="any" name="fox_child_portfolio_options[assets][<?php echo $index; ?>][avg_cost]" value="<?php echo esc_attr($asset['avg_cost'] ?? ''); ?>" class="widefat" style="text-align:right;"/></td>
                        <td><input type="number" step="any" name="fox_child_portfolio_options[assets][<?php echo $index; ?>][current_price]" value="<?php echo esc_attr($asset['current_price'] ?? ''); ?>" class="widefat" style="text-align:right;" readonly/></td>
                        <td><button type="button" class="button button-secondary remove-asset-row"><?php esc_html_e('Kaldır', 'fox-child'); ?></button></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        <template id="asset-row-template">
            <tr class="asset-row">
                <td>
                    <select name="fox_child_portfolio_options[assets][__INDEX__][class]" class="widefat">
                        <?php 
                        echo '<option value="">' . esc_html__('-- Seçin --', 'fox-child') . '</option>';
                        foreach ($asset_classes as $class_name) {
                            echo '<option value="' . esc_attr($class_name) . '">' . esc_html($class_name) . '</option>';
                        }
                        ?>
                    </select>
                </td>
                <td><input type="text" name="fox_child_portfolio_options[assets][__INDEX__][symbol]" value="" class="widefat" placeholder="örn: AAPL:NASDAQ"/></td>
                <td>
                    <select name="fox_child_portfolio_options[assets][__INDEX__][currency]" class="widefat">
                        <?php foreach ($currencies as $code => $label) : ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected('TRY', $code); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="text" name="fox_child_portfolio_options[assets][__INDEX__][name]" value="" class="widefat"/></td>
                <td><input type="number" step="any" name="fox_child_portfolio_options[assets][__INDEX__][quantity]" value="" class="widefat" style="text-align:right;"/></td>
                <td><input type="number" step="any" name="fox_child_portfolio_options[assets][__INDEX__][avg_cost]" value="" class="widefat" style="text-align:right;"/></td>
                <td><input type="number" step="any" name="fox_child_portfolio_options[assets][__INDEX__][current_price]" value="" class="widefat" style="text-align:right;" readonly/></td>
                <td><button type="button" class="button button-secondary remove-asset-row"><?php esc_html_e('Kaldır', 'fox-child'); ?></button></td>
            </tr>
        </template>
        <p><button type="button" id="add-asset-row" class="button button-primary"><?php esc_html_e('Yeni Varlık Ekle', 'fox-child'); ?></button></p>
    </div>
    <?php
}

function fox_child_portfolio_performance_section_callback() {
    $options = get_option('fox_child_portfolio_options');
    $performance_data = isset($options['performance']) && is_array($options['performance']) ? $options['performance'] : array();
    echo '<p>' . esc_html__('Portföyün ve benchmarkların dönemsel (örn: aylık) getirilerini % olarak girin. Kümülatif ve aylık grafikler bu verilere göre oluşturulacaktır.', 'fox-child') . '</p>';
    ?>
    <div id="portfolio-performance-repeater">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:30%;"><?php esc_html_e('Etiket (Ay/Dönem)', 'fox-child'); ?></th>
                    <th style="width:18%; text-align:right;"><?php esc_html_e('Portföy Getirisi %', 'fox-child'); ?></th>
                    <th style="width:18%; text-align:right;"><?php esc_html_e('XU100 (%)', 'fox-child'); ?></th>
                    <th style="width:18%; text-align:right;"><?php esc_html_e('S&P 500 (TL) (%)', 'fox-child'); ?></th>
                    <th style="width:5%;"><?php esc_html_e('İşlem', 'fox-child'); ?></th>
                </tr>
            </thead>
            <tbody id="performance-tbody">
                <?php if (!empty($performance_data)) : foreach ($performance_data as $index => $perf) : ?>
                    <tr class="performance-row">
                        <td><input type="text" name="fox_child_portfolio_options[performance][<?php echo $index; ?>][label]" value="<?php echo esc_attr($perf['label'] ?? ''); ?>" class="widefat"/></td>
                        <td><input type="number" step="any" name="fox_child_portfolio_options[performance][<?php echo $index; ?>][portfolio]" value="<?php echo esc_attr($perf['portfolio'] ?? ''); ?>" class="widefat" style="text-align:right;"/></td>
                        <td><input type="number" step="any" name="fox_child_portfolio_options[performance][<?php echo $index; ?>][xu100]" value="<?php echo esc_attr($perf['xu100'] ?? ''); ?>" class="widefat" style="text-align:right;"/></td>
                        <td><input type="number" step="any" name="fox_child_portfolio_options[performance][<?php echo $index; ?>][sp500tl]" value="<?php echo esc_attr($perf['sp500tl'] ?? ''); ?>" class="widefat" style="text-align:right;"/></td>
                        <td><button type="button" class="button button-secondary remove-performance-row"><?php esc_html_e('Kaldır', 'fox-child'); ?></button></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        <template id="performance-row-template">
            <tr class="performance-row">
                <td><input type="text" name="fox_child_portfolio_options[performance][__INDEX__][label]" value="" class="widefat"/></td>
                <td><input type="number" step="any" name="fox_child_portfolio_options[performance][__INDEX__][portfolio]" value="" class="widefat" style="text-align:right;"/></td>
                <td><input type="number" step="any" name="fox_child_portfolio_options[performance][__INDEX__][xu100]" value="" class="widefat" style="text-align:right;"/></td>
                <td><input type="number" step="any" name="fox_child_portfolio_options[performance][__INDEX__][sp500tl]" value="" class="widefat" style="text-align:right;"/></td>
                <td><button type="button" class="button button-secondary remove-performance-row"><?php esc_html_e('Kaldır', 'fox-child'); ?></button></td>
            </tr>
        </template>
        <p><button type="button" id="add-performance-row" class="button button-primary"><?php esc_html_e('Yeni Dönem Ekle', 'fox-child'); ?></button></p>
    </div>
    <?php
}

function fox_child_portfolio_options_sanitize( $input ) {
    $current_options = get_option('fox_child_portfolio_options', array());
    $new_input = $current_options;
    $allowed_asset_classes = fox_child_get_asset_classes();
    $allowed_currencies = array_keys(fox_child_get_portfolio_currencies());

    if ( isset($input['stats']) && is_array($input['stats']) ) {
        $new_input['stats'] = $new_input['stats'] ?? array();
        $new_input['stats']['total_return_text'] = isset($input['stats']['total_return_text']) ? sanitize_text_field($input['stats']['total_return_text']) : ($new_input['stats']['total_return_text'] ?? '');
        $new_input['stats']['benchmark_outperformance_text'] = isset($input['stats']['benchmark_outperformance_text']) ? sanitize_text_field($input['stats']['benchmark_outperformance_text']) : ($new_input['stats']['benchmark_outperformance_text'] ?? '');
        $new_input['stats']['real_return_text'] = isset($input['stats']['real_return_text']) ? sanitize_text_field($input['stats']['real_return_text']) : ($new_input['stats']['real_return_text'] ?? '');
        $new_input['stats']['last_updated_date'] = isset($input['stats']['last_updated_date']) ? sanitize_text_field($input['stats']['last_updated_date']) : ($new_input['stats']['last_updated_date'] ?? '');
    }

    if ( isset($input['assets']) && is_array($input['assets']) ) {
        $new_input['assets'] = array();
        foreach ($input['assets'] as $key => $asset) {
            if ( empty($asset['class']) && empty($asset['symbol']) && empty($asset['currency']) && empty($asset['name']) && (!isset($asset['quantity']) || $asset['quantity'] === '') && (!isset($asset['avg_cost']) || $asset['avg_cost'] === '') ) {
                continue;
            }
            $new_asset = array();
            $submitted_class = isset($asset['class']) ? sanitize_text_field($asset['class']) : '';
            $new_asset['class'] = in_array($submitted_class, $allowed_asset_classes) ? $submitted_class : '';
            $new_asset['symbol'] = isset($asset['symbol']) ? sanitize_text_field($asset['symbol']) : '';
            $submitted_currency = isset($asset['currency']) ? sanitize_text_field($asset['currency']) : 'TRY';
            $new_asset['currency'] = in_array($submitted_currency, $allowed_currencies) ? $submitted_currency : 'TRY';
            $new_asset['name'] = isset($asset['name']) ? sanitize_text_field($asset['name']) : '';
            $new_asset['quantity'] = isset($asset['quantity']) && is_numeric(str_replace(',', '.', $asset['quantity'])) ? floatval(str_replace(',', '.', $asset['quantity'])) : 0;
            $new_asset['avg_cost'] = isset($asset['avg_cost']) && is_numeric(str_replace(',', '.', $asset['avg_cost'])) ? floatval(str_replace(',', '.', $asset['avg_cost'])) : 0;
            $new_asset['current_price'] = $current_options['assets'][$key]['current_price'] ?? 0;
            $new_input['assets'][] = $new_asset;
        }
    } elseif (isset($input['assets'])) {
        $new_input['assets'] = array();
    }

    if ( isset($input['performance']) && is_array($input['performance']) ) {
        $new_input['performance'] = array();
        foreach ($input['performance'] as $key => $perf) {
            if ( empty($perf['label']) && (!isset($perf['portfolio']) || $perf['portfolio'] === '') && (!isset($perf['xu100']) || $perf['xu100'] === '') && (!isset($perf['sp500tl']) || $perf['sp500tl'] === '') ) {
                continue;
            }
            $new_perf = array();
            $new_perf['label'] = isset($perf['label']) ? sanitize_text_field($perf['label']) : '';
            $new_perf['portfolio'] = isset($perf['portfolio']) && is_numeric(str_replace(',', '.', $perf['portfolio'])) ? floatval(str_replace(',', '.', $perf['portfolio'])) : 0;
            $new_perf['xu100'] = isset($perf['xu100']) && is_numeric(str_replace(',', '.', $perf['xu100'])) ? floatval(str_replace(',', '.', $perf['xu100'])) : 0;
            $new_perf['sp500tl'] = isset($perf['sp500tl']) && is_numeric(str_replace(',', '.', $perf['sp500tl'])) ? floatval(str_replace(',', '.', $perf['sp500tl'])) : 0;
            $new_input['performance'][] = $new_perf;
        }
    } elseif (isset($input['performance'])) {
        $new_input['performance'] = array();
    }

    return $new_input;
}

function fox_child_portfolio_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Bu sayfayı görüntüleme yetkiniz yok.', 'fox-child' ) );
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ccd0d4;">
            <input type="hidden" name="action" value="fox_child_manual_update_prices">
            <?php wp_nonce_field('fox_child_manual_update_prices_nonce'); ?>
            <p><?php esc_html_e('Portföydeki varlıkların güncel fiyatlarını Google Finance\'dan hemen çekmek için aşağıdaki butonu kullanabilirsiniz. Bu işlem birkaç dakika sürebilir.', 'fox-child'); ?></p>
            <?php submit_button(__('Fiyatları Şimdi Manuel Güncelle', 'fox-child'), 'secondary', 'manual_update_submit', false); ?>
        </form>
        <hr>
        <form action="options.php" method="post">
            <?php settings_fields( 'fox_child_portfolio_settings_group' ); ?>
            <?php do_settings_sections( 'fox_child_portfolio_settings' ); ?>
            <?php submit_button( __('Portföy Ayarlarını Kaydet', 'fox-child') ); ?>
        </form>
    </div>
    <?php
}

// === GÖSTERGE PANELİ YÖNETİCİ MENÜSÜ (GÜNCELLENMİŞ) ===
add_action( 'admin_menu', 'fox_child_dashboard_settings_admin_menu' );
function fox_child_dashboard_settings_admin_menu() {
    // Ana menü öğesini oluştur
    add_menu_page(
        __('Gösterge Paneli Ayarları', 'fox-child'),
        __('Gösterge Paneli', 'fox-child'),
        'manage_options',
        'fox_child_dashboard_settings',
        'fox_child_ticker_settings_page_html',
        'dashicons-dashboard',
        26
    );

    // Ticker Bar alt menüsünü ekle
    add_submenu_page(
        'fox_child_dashboard_settings',
        __('Ticker Bar Ayarları', 'fox-child'),
        __('Ticker Bar', 'fox-child'),
        'manage_options',
        'fox_child_ticker_settings',
        'fox_child_ticker_settings_page_html'
    );

    // Sidebar alt menüsünü ekle
    add_submenu_page(
        'fox_child_dashboard_settings',
        __('Kenar Çubuğu Piyasa Özeti Ayarları', 'fox-child'),
        __('Sidebar Piyasa Özeti', 'fox-child'),
        'manage_options',
        'fox_child_sidebar_settings',
        'fox_child_sidebar_settings_page_html'
    );

    // === YENİ: Veri Çekme Ayarları (Scraper) alt menüsü ===
    add_submenu_page(
        'fox_child_dashboard_settings',
        __('Veri Çekme Ayarları', 'fox-child'),
        __('Veri Çekme Ayarları', 'fox-child'),
        'manage_options',
        'fox_child_scraper_settings', // Yeni sayfanın slug'ı
        'fox_child_scraper_settings_page_html' // Yeni sayfanın callback fonksiyonu
    );

    remove_submenu_page('fox_child_dashboard_settings', 'fox_child_dashboard_settings');
}

add_action( 'admin_init', 'fox_child_dashboard_settings_init' );
function fox_child_dashboard_settings_init() {
    // Ticker Ayarları
    register_setting('fox_child_ticker_settings_group', 'fox_child_ticker_options', 'fox_child_dashboard_options_sanitize');
    add_settings_section('fox_child_ticker_items_section', __('Ticker Bar Öğeleri', 'fox-child'), '__return_false', 'fox_child_ticker_settings');
    add_settings_field('ticker_items', __('Öğeler', 'fox-child'), 'fox_child_dashboard_items_field_callback', 'fox_child_ticker_settings', 'fox_child_ticker_items_section', ['option_name' => 'fox_child_ticker_options']);

    // Sidebar Ayarları
    register_setting('fox_child_sidebar_settings_group', 'fox_child_sidebar_options', 'fox_child_dashboard_options_sanitize');
    add_settings_section('fox_child_sidebar_items_section', __('Kenar Çubuğu Piyasa Özeti Öğeleri', 'fox-child'), '__return_false', 'fox_child_sidebar_settings');
    add_settings_field('sidebar_items', __('Öğeler', 'fox-child'), 'fox_child_dashboard_items_field_callback', 'fox_child_sidebar_settings', 'fox_child_sidebar_items_section', ['option_name' => 'fox_child_sidebar_options']);
}
// =============================================
// === Veri Çekme (Scraper) Ayarları Sayfası ===
// =============================================
add_action( 'admin_init', 'fox_child_scraper_settings_init' );
function fox_child_scraper_settings_init() {
    register_setting(
        'fox_child_scraper_settings_group',      // Ayar grubu adı
        'fox_child_scraper_options',             // Veritabanındaki option adı
        'fox_child_scraper_options_sanitize'     // Kaydetmeden önce veriyi temizleyecek fonksiyon
    );

    add_settings_section(
        'fox_child_scraper_xpath_section',       // Bölüm ID'si
        __('Google Finance XPath Sorguları', 'fox-child'), // Bölüm başlığı
        'fox_child_scraper_section_callback',    // Bölüm açıklamasını basan fonksiyon
        'fox_child_scraper_settings'             // Sayfa slug'ı
    );

    add_settings_field(
        'price_xpath',                           // Alan ID'si
        __('Güncel Fiyat Sorgusu (XPath)', 'fox-child'), // Alan etiketi
        'fox_child_scraper_field_callback',      // Alanı basan fonksiyon
        'fox_child_scraper_settings',            // Sayfa slug'ı
        'fox_child_scraper_xpath_section',       // Bölüm ID'si
        ['id' => 'price_xpath', 'default' => "//div[contains(@class, 'YMlKec') and contains(@class, 'fxKbKc')]"] // Argümanlar
    );

    add_settings_field(
        'prev_close_xpath',
        __('Önceki Kapanış Sorgusu (XPath)', 'fox-child'),
        'fox_child_scraper_field_callback',
        'fox_child_scraper_settings',
        'fox_child_scraper_xpath_section',
        ['id' => 'prev_close_xpath', 'default' => '//*[@id="yDmH0d"]/c-wiz[2]/div/div[4]/div/main/div[2]/div[2]/div/div[1]/div[2]/div']
    );
}

function fox_child_scraper_section_callback() {
    echo '<p>' . esc_html__('Google Finance sayfa yapısı değiştiğinde, veri çekme işleminin devam etmesi için buradaki XPath sorgularını güncelleyebilirsiniz.', 'fox-child') . '</p>';
    echo '<p><strong>' . esc_html__('UYARI:', 'fox-child') . '</strong> ' . esc_html__('Bu alanlar çok hassastır. Sadece tarayıcınızın "Copy > Copy XPath" özelliğini kullanarak aldığınız geçerli bir XPath sorgusu girin. Hatalı bir sorgu veri akışını durdurabilir.', 'fox-child') . '</p>';
}

function fox_child_scraper_field_callback($args) {
    $options = get_option('fox_child_scraper_options');
    $id = $args['id'];
    $value = isset($options[$id]) && !empty($options[$id]) ? $options[$id] : '';
    $default = $args['default'];

    printf(
        '<textarea id="%1$s" name="fox_child_scraper_options[%1$s]" rows="2" class="large-text">%2$s</textarea>',
        esc_attr($id),
        esc_textarea($value)
    );
    echo '<p class="description">' . sprintf(esc_html__('Boş bırakılırsa varsayılan sorgu kullanılır: %s', 'fox-child'), '<code>' . esc_html($default) . '</code>') . '</p>';
}

function fox_child_scraper_options_sanitize($input) {
    $new_input = [];
    if (isset($input['price_xpath'])) {
        $new_input['price_xpath'] = sanitize_text_field($input['price_xpath']);
    }
    if (isset($input['prev_close_xpath'])) {
        $new_input['prev_close_xpath'] = sanitize_text_field($input['prev_close_xpath']);
    }
    return $new_input;
}

function fox_child_scraper_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('fox_child_scraper_settings_group');
            do_settings_sections('fox_child_scraper_settings');
            submit_button(__('Ayarları Kaydet', 'fox-child'));
            ?>
        </form>
    </div>
    <?php
}

function fox_child_dashboard_items_field_callback( $args ) {
    $option_name = $args['option_name'];
    $options = get_option($option_name, ['items' => []]);
    $items = $options['items'] ?? [];
    ?>
    <div class="dashboard-items-repeater">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:40%;"><?php esc_html_e('Görünen Ad', 'fox-child'); ?></th>
                    <th style="width:40%;"><?php esc_html_e('Google Finance Kodu', 'fox-child'); ?><br><small>(örn: SPY, GCW00:COMEX, BTC-USD)</small></th>
                    <th style="width:10%;"><?php esc_html_e('İşlem', 'fox-child'); ?></th>
                </tr>
            </thead>
            <tbody class="items-tbody">
                <?php if (!empty($items)) : foreach ($items as $index => $item) : ?>
                    <tr class="item-row">
                        <td><input type="text" name="<?php echo esc_attr($option_name); ?>[items][<?php echo $index; ?>][display_name]" value="<?php echo esc_attr($item['display_name'] ?? ''); ?>" class="widefat"/></td>
                        <td><input type="text" name="<?php echo esc_attr($option_name); ?>[items][<?php echo $index; ?>][google_code]" value="<?php echo esc_attr($item['google_code'] ?? ''); ?>" class="widefat"/></td>
                        <td><button type="button" class="button button-secondary remove-dashboard-item-row"><?php esc_html_e('Kaldır', 'fox-child'); ?></button></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        <template class="item-row-template">
            <tr class="item-row">
                <td><input type="text" name="<?php echo esc_attr($option_name); ?>[items][__INDEX__][display_name]" value="" class="widefat"/></td>
                <td><input type="text" name="<?php echo esc_attr($option_name); ?>[items][__INDEX__][google_code]" value="" class="widefat"/></td>
                <td><button type="button" class="button button-secondary remove-dashboard-item-row"><?php esc_html_e('Kaldır', 'fox-child'); ?></button></td>
            </tr>
        </template>
        <p><button type="button" class="button button-primary add-dashboard-item-row"><?php esc_html_e('Yeni Öğe Ekle', 'fox-child'); ?></button></p>
    </div>
    <?php
}

function fox_child_dashboard_options_sanitize( $input ) {
    $new_input = ['items' => []];
    if ( isset($input['items']) && is_array($input['items']) ) {
        foreach ($input['items'] as $key => $item) {
            if (empty($item['display_name']) && empty($item['google_code'])) {
                continue;
            }
            $new_item = array();
            $new_item['display_name'] = isset($item['display_name']) ? sanitize_text_field($item['display_name']) : '';
            $new_item['google_code'] = isset($item['google_code']) ? sanitize_text_field($item['google_code']) : '';
            if (!empty($new_item['display_name']) && !empty($new_item['google_code'])) {
                $new_input['items'][] = $new_item;
            }
        }
    }
    return $new_input;
}

function fox_child_ticker_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Bu sayfayı görüntüleme yetkiniz yok.', 'fox-child' ) );
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Ticker Bar Ayarları', 'fox-child'); ?></h1>
        <p><?php esc_html_e('Sitenin üst kısmında kayan barda gösterilecek öğeleri buradan yönetin.', 'fox-child'); ?></p>
        <form action="options.php" method="post">
            <?php settings_fields( 'fox_child_ticker_settings_group' ); ?>
            <?php do_settings_sections( 'fox_child_ticker_settings' ); ?>
            <?php submit_button( __('Ticker Ayarlarını Kaydet', 'fox-child') ); ?>
        </form>
    </div>
    <?php
}

function fox_child_sidebar_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Bu sayfayı görüntüleme yetkiniz yok.', 'fox-child' ) );
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Kenar Çubuğu Piyasa Özeti Ayarları', 'fox-child'); ?></h1>
        <p><?php esc_html_e('Sağ kenar çubuğundaki "Piyasalar Özeti" widget\'ında gösterilecek öğeleri buradan yönetin.', 'fox-child'); ?></p>
        <form action="options.php" method="post">
            <?php settings_fields( 'fox_child_sidebar_settings_group' ); ?>
            <?php do_settings_sections( 'fox_child_sidebar_settings' ); ?>
            <?php submit_button( __('Sidebar Ayarlarını Kaydet', 'fox-child') ); ?>
        </form>
    </div>
    <?php
}

// =============================================
// === Yönetici Paneli JS Yükleyici ===
// =============================================
add_action( 'admin_enqueue_scripts', 'fox_child_dashboard_admin_scripts' );
function fox_child_dashboard_admin_scripts( $hook_suffix ) {
    $is_portfolio_page = ( strpos($hook_suffix, 'fox_child_portfolio_settings') !== false );
    $is_dashboard_settings_page = ( strpos($hook_suffix, 'fox_child_ticker_settings') !== false || strpos($hook_suffix, 'fox_child_sidebar_settings') !== false );

    if ( ! $is_portfolio_page && ! $is_dashboard_settings_page ) {
        return;
    }

    if ( $is_portfolio_page ) {
        $portfolio_repeater_js_path = get_stylesheet_directory() . '/js/admin-portfolio-repeater.js';
        if ( file_exists($portfolio_repeater_js_path) ) {
            wp_enqueue_script('fox-child-portfolio-repeater', get_stylesheet_directory_uri() . '/js/admin-portfolio-repeater.js', array('jquery'), FOX_CHILD_VERSION, true);
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('Portföy ayarları için gerekli JavaScript dosyası (admin-portfolio-repeater.js) bulunamadı. Tekrarlayıcı alanlar (Varlık/Performans Ekle) düzgün çalışmayabilir.', 'fox-child') . '</p></div>';
            });
        }
    }

    if ( $is_dashboard_settings_page ) {
        $dashboard_repeater_js_path = get_stylesheet_directory() . '/js/admin-dashboard-repeater.js';
        if ( file_exists($dashboard_repeater_js_path) ) {
            wp_enqueue_script('fox-child-dashboard-repeater', get_stylesheet_directory_uri() . '/js/admin-dashboard-repeater.js', array('jquery'), FOX_CHILD_VERSION, true);
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('Gösterge paneli ayarları (Ticker/Sidebar) için gerekli JavaScript dosyası (admin-dashboard-repeater.js) bulunamadı. Tekrarlayıcı alanlar (Öğe Ekle) düzgün çalışmayabilir.', 'fox-child') . '</p></div>';
            });
        }
    }
}

/**
 * Özel üyelik kısıtlama mesajı şablonunu göstermek için bir kısa kod [bbb_restriction_message] oluşturur.
 */
function fox_child_membership_restriction_shortcode() {
    
    // Göstereceğimiz şablon dosyasının yolu. Adını 'membership-restriction-message.php' yapmıştık.
    $template_path = get_stylesheet_directory() . '/template-parts/membership-restriction-message.php';

    if ( file_exists( $template_path ) ) {
        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    return 'Kısıtlama mesajı şablonu bulunamadı.';
}
add_shortcode( 'bbb_restriction_message', 'fox_child_membership_restriction_shortcode' );

// =============================================
// === Gösterge Paneli Veri Yönetimi (Dirençli Versiyon) ===
// =============================================

/**
 * Gösterge paneli (Ticker/Sidebar) cron görevini kontrol eder ve yoksa kurar.
 * Bu fonksiyon, tema her yüklendiğinde çalışarak cron'un kaybolmasını engeller.
 */
add_action( 'init', 'fox_child_ensure_dashboard_cron_is_scheduled' );
function fox_child_ensure_dashboard_cron_is_scheduled() {
    if ( ! wp_next_scheduled( 'fox_child_update_dashboard_data_event' ) ) {
        wp_schedule_event( time(), '5_minutes', 'fox_child_update_dashboard_data_event' );
        error_log('[DASHBOARD CRON KONTROL] Cron görevi bulunamadı ve yeniden kuruldu.');
    }
}

/**
 * Tema değiştirildiğinde cron görevini temizler.
 * Bu, gereksiz görevlerin birikmesini önler.
 */
add_action( 'switch_theme', 'fox_child_clear_dashboard_update_cron' );
function fox_child_clear_dashboard_update_cron() {
    wp_clear_scheduled_hook( 'fox_child_update_dashboard_data_event' );
    error_log( '[DASHBOARD CRON KONTROL] Dashboard data update event cleared on theme switch.' );
}

/**
 * Manuel veri güncelleme için admin sayfasını ve aksiyonunu ekler.
 */
add_action( 'admin_menu', 'fox_child_add_manual_update_page' );
function fox_child_add_manual_update_page() {
    add_submenu_page(
        'fox_child_dashboard_settings',      // Ana menü slug'ı
        __('Veri Güncelleme', 'fox-child'), // Sayfa başlığı
        __('Veri Güncelleme', 'fox-child'), // Menü başlığı
        'manage_options',                  // Yetki
        'fox_child_manual_data_update',    // Sayfa slug'ı
        'fox_child_manual_update_page_html'// Sayfayı oluşturan fonksiyon
    );
}

// Manuel güncelleme sayfasının HTML içeriği
function fox_child_manual_update_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Manuel Veri Güncelleme', 'fox-child' ); ?></h1>
        <p><?php esc_html_e( 'Aşağıdaki butona tıklayarak Ticker Bar ve Sidebar piyasa verilerini hemen güncellemeyi tetikleyebilirsiniz.', 'fox-child' ); ?></p>
        <p><?php esc_html_e( 'Bu işlem, Google Finance\'dan veri çektiği için birkaç saniye sürebilir.', 'fox-child' ); ?></p>
        
        <?php if ( isset( $_GET['updated'] ) && $_GET['updated'] == 'true' ) : ?>
            <div id="message" class="updated notice is-dismissible">
                <p><?php esc_html_e( 'Veri güncelleme görevi başarıyla tetiklendi! Verilerin ön yüzde görünmesi birkaç dakika sürebilir.', 'fox-child' ); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="fox_child_manual_dashboard_update">
            <?php wp_nonce_field( 'manual_dashboard_update_nonce' ); ?>
            <?php submit_button( __( 'Piyasa Verilerini Şimdi Güncelle', 'fox-child' ) ); ?>
        </form>
    </div>
    <?php
}

// Form gönderildiğinde bu aksiyonu çalıştır
add_action( 'admin_post_fox_child_manual_dashboard_update', 'fox_child_handle_manual_dashboard_update' );
function fox_child_handle_manual_dashboard_update() {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'manual_dashboard_update_nonce' ) ) {
        wp_die( 'Güvenlik kontrolü başarısız oldu.' );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Bu işlemi yapma yetkiniz yok.' );
    }

    // Cron görevini hemen çalıştır
    fox_child_update_dashboard_data_cron_job();

    // Kullanıcıyı bir başarı mesajıyla geri yönlendir
    wp_redirect( admin_url( 'admin.php?page=fox_child_manual_data_update&updated=true' ) );
    exit;
}

?>
