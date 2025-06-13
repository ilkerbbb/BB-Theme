<?php
/**
 * Template Name: BB Theme - Notlar Akışı (İki Bölmeli)
 *
 * @package Fox_Child
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php wp_title( '|', true, 'right' ); ?><?php bloginfo( 'name' ); ?></title>
	<?php wp_head(); ?>
</head>
<body <?php body_class('page-template-notes two-pane-layout'); ?>>

	<header class="site-header">
        <div class="header-content"> <div class="header-left"> <div class="logo"> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"> <?php if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) { the_custom_logo(); } else { bloginfo( 'name' ); } ?> </a> </div> <nav class="primary-navigation"> <ul> <li><a href="https://borsadabibasina.com/hakkinda">Hakkında</a></li> <li><a href="https://borsadabibasina.com/iletisim">İletişim</a></li> <li><a href="https://borsadabibasina.com/yasal-uyari">Yasal Uyarı</a></li> <li><a href="https://borsadabibasina.com/gizlilik">Gizlilik</a></li> <li><a href="https://borsadabibasina.com/abonelik">Abonelik Seviyeleri</a></li> </ul> </nav> </div> <div class="header-right"> <button class="icon-button" aria-label="Search"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button> <a href="https://borsadabibasina.com/hesabim" class="subscribe-button">Hesabım</a> <button class="theme-toggle-button icon-button" aria-label="<?php esc_attr_e( 'Temayı Değiştir', 'fox-child' ); ?>">🌙</button> </div> </div>
	</header>

    <?php get_template_part('template-parts/ticker-bar'); ?>

	<div class="main-container notes-page-container">

		<section class="content-header">
			<nav class="secondary-navigation"> <?php if ( has_nav_menu( 'bb_secondary_menu' ) ) { wp_nav_menu( array( 'theme_location' => 'bb_secondary_menu', 'container' => false, 'menu_class' => 'secondary-nav-list', 'depth' => 1 ) ); } else { echo '<ul class="secondary-nav-list"><li><a href="#">' . esc_html__( 'Menü Ata', 'fox-child' ) . '</a></li></ul>'; } ?> </nav>
		</section>

        <?php
        if ( have_posts() ) :
            while ( have_posts() ) : the_post();

                // 1. WooCommerce'in kararını bir değişkene al (ekrana basmadan).
                ob_start();
                the_content();
                $original_content = ob_get_clean();

                // 2. Değişkenin içinde kısıtlama mesajı var mı diye kontrol et.
                // WooCommerce, kısıtlama mesajını genellikle 'wc-memberships-content-restricted' içeren bir div içine koyar.
                // Biz sadece bu class'ın varlığına bakacağız.
                $is_restricted_by_plugin = false;
                if ( strpos( $original_content, 'wc-memberships-content-restricted' ) !== false ) {
                    $is_restricted_by_plugin = true;
                }

                // 3. Sonuca göre hareket et.
                if ( $is_restricted_by_plugin ) {
                    // --- ERİŞİM YOK: Eklentinin oluşturduğu kısıtlama mesajını bas. ---
                    echo '<main class="main-column">';
                    echo $original_content;
                    echo '</main>';
                } else {
                    // --- ERİŞİM VAR: Bizim özel Notlar arayüzümüzü bas. ---
                    ?>
                    <div class="content-body notes-content-body two-pane-body">
                        <aside class="notes-list-pane">
                            <div class="notes-list-pane-inner">
                                <div class="notes-filters">
                                    <div class="note-type-filters">
                                        <button class="filter-button active" data-filter-type="all"><?php esc_html_e('Tümü', 'fox-child'); ?></button>
                                        <?php
                                        $note_types = get_terms( array( 'taxonomy' => 'note_type', 'hide_empty' => true, 'orderby' => 'name', 'order' => 'ASC' ) );
                                        if ( ! is_wp_error($note_types) && ! empty($note_types) ) {
                                            foreach ( $note_types as $note_type ) {
                                                printf( '<button class="filter-button" data-filter-type="%s">%s</button>', esc_attr( $note_type->slug ), esc_html( $note_type->name ) );
                                            }
                                        }
                                        ?>
                                    </div>
                                    <div class="note-hashtag-filter-display" style="display: none;">
                                        <?php esc_html_e('Filtre:', 'fox-child'); ?> <span class="active-hashtag"></span>
                                        <button class="clear-hashtag-filter">×</button>
                                    </div>
                                </div>
                                <div class="notes-list-container">
                                    <ul class="notes-list">
                                        <?php
                                        $notes_args = array( 'post_type' => 'bbb_note', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC');
                                        $notes_query = new WP_Query( $notes_args );
                                        if ( $notes_query->have_posts() ) : while ( $notes_query->have_posts() ) : $notes_query->the_post();
                                            $note_id = get_the_ID();
                                            $note_terms = get_the_terms( $note_id, 'note_type' );
                                            $note_type_slugs = !is_wp_error($note_terms) && !empty($note_terms) ? wp_list_pluck( $note_terms, 'slug' ) : array();
                                            $note_type_classes = !empty($note_type_slugs) ? implode(' ', array_map( function($slug){ return 'note-type-' . $slug; }, $note_type_slugs) ) : '';
                                            preg_match_all('/(?<=\s|^|\W)#(\p{L}|\p{N})([\p{L}\p{N}_]*)/u', get_the_content(), $matches);
                                            $hashtags = !empty($matches[0]) ? array_map(function($tag) { return strtolower(ltrim(trim($tag), '#')); }, $matches[0]) : array();
                                            $hashtags_attr = !empty($hashtags) ? implode(',', array_unique($hashtags)) : '';
                                        ?>
                                            <li class="note-item <?php echo esc_attr($note_type_classes); ?>"
                                                data-note-id="<?php echo esc_attr($note_id); ?>"
                                                data-note-types='<?php echo json_encode($note_type_slugs); ?>'
                                                data-hashtags="<?php echo esc_attr($hashtags_attr); ?>">
                                                <div class="note-item-header">
                                                    <h3 class="note-item-title">
                                                        <a href="<?php the_permalink(); ?>" class="note-title-link" data-note-id="<?php echo esc_attr($note_id); ?>"><?php the_title(); ?></a>
                                                    </h3>
                                                    <span class="note-item-date"><?php echo esc_html( get_the_date() ); ?></span>
                                                </div>
                                            </li>
                                        <?php endwhile; wp_reset_postdata(); else : ?>
                                            <li class="no-notes-found"><?php esc_html_e( 'Gösterilecek not bulunamadı.', 'fox-child' ); ?></li>
                                        <?php endif; ?>
                                    </ul>
                                    <div class="notes-loader" style="display: none; text-align: center; padding: 20px;"><?php esc_html_e('Yükleniyor...', 'fox-child'); ?></div>
                                </div>
                            </div>
                        </aside>
                        <main class="notes-content-pane">
                            <div class="note-content-area">
                                <div class="note-content-placeholder"><p><?php esc_html_e('Okumak için soldaki listeden bir not seçin.', 'fox-child'); ?></p></div>
                                <div class="note-content-loader" style="display: none;"><p><?php esc_html_e('Not yükleniyor...', 'fox-child'); ?></p></div>
                                <div class="note-content-display" style="display: none;">
                                    <h2 id="note-content-title"></h2>
                                    <div class="note-content-meta" style="display: none;">
                                        <span class="meta-date"></span>
                                        <span class="meta-separator" style="margin: 0 8px;">|</span>
                                        <span class="meta-type"></span>
                                    </div>
                                    <div id="note-content-body"></div>
                                </div>
                                <div class="note-content-error" style="display: none;"><p><?php esc_html_e('Not yüklenirken bir hata oluştu.', 'fox-child'); ?></p></div>
                            </div>
                        </main>
                    </div>
                <?php
                }

            endwhile;
        endif;
        ?>
	</div>

	<?php wp_footer(); ?>
</body>
</html>