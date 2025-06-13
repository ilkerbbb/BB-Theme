<?php
/**
 * Template Name: BB Theme
 * @package Fox_Child
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php wp_title( '|', true, 'right' ); ?></title>
	<?php wp_head(); ?>
</head>
<body <?php body_class('page-template-bb-theme'); ?>>

	<header class="site-header">
		<div class="header-content"> <div class="header-left"> <div class="logo"> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"> <?php if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) { the_custom_logo(); } else { bloginfo( 'name' ); } ?> </a> </div> <nav class="primary-navigation"> <ul> <li><a href="<?php echo esc_url( home_url('/hakkinda') ); ?>">Hakkında</a></li> <li><a href="<?php echo esc_url( home_url('/iletisim') ); ?>">İletişim</a></li> <li><a href="<?php echo esc_url( home_url('/yasal-uyari') ); ?>">Yasal Uyarı</a></li> <li><a href="<?php echo esc_url( home_url('/gizlilik') ); ?>">Gizlilik</a></li> <li><a href="<?php echo esc_url( home_url('/abonelik') ); ?>">Abonelik Seviyeleri</a></li> </ul> </nav> </div> <div class="header-right"> <button class="icon-button" aria-label="Search"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button> <a href="<?php echo esc_url( home_url('/hesabim') ); ?>" class="subscribe-button">Hesabım</a> <button class="theme-toggle-button icon-button" aria-label="<?php esc_attr_e( 'Temayı Değiştir', 'fox-child' ); ?>">🌙</button> </div> </div>
	</header>

	<?php get_template_part('template-parts/ticker-bar'); ?>

	<div class="main-container bb-theme-dashboard-container">
		<section class="content-header">
			<nav class="secondary-navigation"> <?php if ( has_nav_menu( 'bb_secondary_menu' ) ) { wp_nav_menu( array( 'theme_location' => 'bb_secondary_menu', 'container' => false, 'menu_class' => 'secondary-nav-list', 'depth' => 1 ) ); } else { echo '<ul class="secondary-nav-list"><li><a href="#">' . esc_html__( 'Menü Ata', 'fox-child' ) . '</a></li></ul>'; } ?> </nav>
		</section>

		<div class="content-body">
            <?php
            if ( have_posts() ) :
                while ( have_posts() ) : the_post();

                    // 1. WooCommerce'in kararını bir değişkene al.
                    ob_start();
                    the_content();
                    $original_content = ob_get_clean();

                    // 2. İçerikte kısıtlama mesajı var mı diye kontrol et.
                    $is_restricted_by_plugin = ( strpos( $original_content, 'wc-memberships-content-restricted' ) !== false );
                    
                    // Ana içerik sütununu aç
                    echo '<main class="main-column dashboard-main-content-v7">';

                    if ( $is_restricted_by_plugin ) {
                        // --- ERİŞİM YOK: Eklentinin oluşturduğu mesajı bas. ---
                        echo $original_content;
                    } else {
                        // --- ERİŞİM VAR: Dinamik yazı akışını göster. ---
                        
                        $dashboard_categories = 'arastirma,bulten';
                        $excluded_post_ids = array();
                        $podcast_rss_url = 'https://anchor.fm/s/98d8f0fc/podcast/rss';
                        $podcast_data = function_exists('fox_child_get_podcast_data') ? fox_child_get_podcast_data( $podcast_rss_url ) : null;
                        $latest_podcast_episode = ($podcast_data && !isset($podcast_data['error']) && isset( $podcast_data['latest_episode'] )) ? $podcast_data['latest_episode'] : null;
                        ?>
                        <div class="dashboard-grid-container-v7">

                            <div class="dashboard-hero-area-v7">
                                <?php
                                $args_hero_main = array( 'post_type' => 'post', 'post_status' => 'publish', 'category_name' => $dashboard_categories, 'posts_per_page' => 1, 'orderby' => 'date', 'order' => 'DESC', 'ignore_sticky_posts' => 1 );
                                $hero_main_query = new WP_Query( $args_hero_main );
                                if ( $hero_main_query->have_posts() ) : while ( $hero_main_query->have_posts() ) : $hero_main_query->the_post();
                                    $excluded_post_ids[] = get_the_ID();
                                    $hero_excerpt = has_excerpt() ? get_the_excerpt() : get_the_content();
                                ?>
                                <article class="hero-main-article-v7">
                                    <?php if ( has_post_thumbnail() ) : ?>
                                        <div class="post-thumbnail hero-main-thumbnail-v7">
                                            <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('large'); ?></a>
                                        </div>
                                    <?php endif; ?>
                                    <div class="hero-main-content-v7">
                                        <div class="hero-main-text">
                                            <h2 class="post-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                                            <div class="post-excerpt">
                                                <?php echo wp_trim_words( strip_shortcodes( strip_tags( $hero_excerpt ) ), 20, '...' ); ?>
                                            </div>
                                        </div>
                                        <?php if ($latest_podcast_episode): ?>
                                        <div class="hero-media-box-v7 hero-podcast-section">
                                            <div class="media-box-thumbnail">
                                                <span class="media-icon">🎧</span>
                                            </div>
                                            <div class="media-box-content">
                                                <a href="<?php echo esc_url($latest_podcast_episode['url']); ?>" target="_blank" rel="noopener noreferrer" class="media-box-title">
                                                    <?php echo esc_html($latest_podcast_episode['title']); ?>
                                                </a>
                                                <?php if (!empty($latest_podcast_episode['duration'])): ?>
                                                    <span class="media-box-duration"><?php echo esc_html($latest_podcast_episode['duration']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </article>
                                <?php endwhile; wp_reset_postdata(); endif; ?>
                            </div>

                            <?php
                            $args_research_v8 = array( 'post_type' => 'post', 'post_status' => 'publish', 'category_name' => 'arastirma', 'posts_per_page' => 3, 'orderby' => 'date', 'order' => 'DESC', 'post__not_in' => $excluded_post_ids, 'ignore_sticky_posts' => 1 );
                            $research_v8_query = new WP_Query( $args_research_v8 );
                            $research_v8_posts = $research_v8_query->get_posts();
                            if ( count( $research_v8_posts ) > 0 ) : ?>
                            <section class="dashboard-category-grid dashboard-research-grid-v8">
                                <div class="research-grid-content-v8">
                                    <?php
                                    $featured_post = array_shift( $research_v8_posts );
                                    $excluded_post_ids[] = $featured_post->ID;
                                    $featured_excerpt = has_excerpt($featured_post->ID) ? get_the_excerpt($featured_post->ID) : $featured_post->post_content;
                                    ?>
                                    <article class="research-item-featured">
                                        <?php if ( has_post_thumbnail( $featured_post->ID ) ) : ?>
                                            <div class="research-item-featured-thumb">
                                                <a href="<?php echo esc_url( get_permalink( $featured_post->ID ) ); ?>">
                                                    <?php echo get_the_post_thumbnail( $featured_post->ID, 'medium' ); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <div class="research-item-featured-content">
                                            <h3 class="post-title">
                                                <a href="<?php echo esc_url( get_permalink( $featured_post->ID ) ); ?>">
                                                    <?php echo esc_html( $featured_post->post_title ); ?>
                                                </a>
                                            </h3>
                                            <div class="post-excerpt">
                                                 <?php echo wp_trim_words( strip_shortcodes( strip_tags( $featured_excerpt ) ), 20, '...' ); ?>
                                            </div>
                                        </div>
                                    </article>
                                    <?php if ( ! empty( $research_v8_posts ) ) : ?>
                                    <div class="research-items-list">
                                        <ul>
                                            <?php foreach ( $research_v8_posts as $list_post ) : $excluded_post_ids[] = $list_post->ID; ?>
                                            <li><a href="<?php echo esc_url( get_permalink($list_post->ID) ); ?>"><?php echo esc_html( $list_post->post_title ); ?></a></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="section-more-link">
                                    <a href="<?php echo esc_url( home_url('/arastirma') ); ?>">Tüm Araştırmalar →</a>
                                </div>
                            </section>
                            <?php endif; ?>
                            
                            <?php
                            $args_bulten_v11 = array( 'post_type' => 'post', 'post_status' => 'publish', 'category_name' => 'bulten', 'posts_per_page' => 2, 'orderby' => 'date', 'order' => 'DESC', 'post__not_in' => $excluded_post_ids, 'ignore_sticky_posts' => 1 );
                            $bulten_v11_query = new WP_Query( $args_bulten_v11 );
                            if ( $bulten_v11_query->have_posts() ) : ?>
                            <section class="dashboard-category-grid dashboard-bulten-grid-v11">
                                <div class="bulten-grid-content-v11">
                                    <?php while ( $bulten_v11_query->have_posts() ) : $bulten_v11_query->the_post(); ?>
                                    <article class="bulten-grid-item-v11">
                                        <?php if ( has_post_thumbnail() ) : ?>
                                            <div class="bulten-item-thumbnail-v11">
                                                <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('medium_large'); ?></a>
                                            </div>
                                        <?php endif; ?>
                                        <h3 class="post-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                    </article>
                                    <?php endwhile; ?>
                                </div>
                                <div class="section-more-link">
                                    <a href="<?php echo esc_url( home_url('/bulten') ); ?>">Tüm Bültenler →</a>
                                </div>
                            </section>
                            <?php wp_reset_postdata(); endif; ?>

                            <?php
                            // ======================================================
                            // === GÜNCELLENMİŞ SON NOTLAR BÖLÜMÜ                 ===
                            // ======================================================
                            $args_notes = array( 'post_type' => 'bbb_note', 'post_status' => 'publish', 'posts_per_page' => 10, 'orderby' => 'date', 'order' => 'DESC' );
                            $notes_query = new WP_Query( $args_notes );

                            if ( $notes_query->have_posts() ) : ?>
                            <section class="dashboard-category-grid dashboard-latest-notes">
                                <h3 class="section-title notes-title"><?php esc_html_e( 'Son Notlar', 'fox-child' ); ?></h3>
                                <div class="notes-list-wrapper bloomberg-style-list">
                                    <div class="list-header">
                                        <span class="col-title"><?php esc_html_e('Başlık', 'fox-child'); ?></span>
                                        <span class="col-category"><?php esc_html_e('Kategori', 'fox-child'); ?></span>
                                        <span class="col-date"><?php esc_html_e('Tarih', 'fox-child'); ?></span>
                                    </div>
                                    <ul>
                                        <?php while ( $notes_query->have_posts() ) : $notes_query->the_post();
                                            $note_terms = get_the_terms( get_the_ID(), 'note_type' );
                                            $category_name = '';
                                            if ( ! empty( $note_terms ) && ! is_wp_error( $note_terms ) ) {
                                                $category_name = esc_html( $note_terms[0]->name );
                                            }
                                        ?>
                                            <li>
                                                <a href="<?php the_permalink(); ?>" class="note-row-link">
                                                    <span class="note-title"><?php the_title(); ?></span>
                                                    <span class="note-category"><?php echo $category_name; ?></span>
                                                    <span class="note-date"><?php echo get_the_date('d/m/Y'); ?></span>
                                                </a>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                </div>
                                <div class="section-more-link">
                                    <a href="<?php echo esc_url( home_url('/notlar') ); ?>"><?php esc_html_e( 'Tüm Notlar', 'fox-child' ); ?> →</a>
                                </div>
                            </section>
                            <?php
                                wp_reset_postdata();
                            endif;
                            ?>
                        </div>
                        <?php
                    }
                    echo '</main>';
                    
                    if ( ! $is_restricted_by_plugin ) {
                        get_template_part( 'template-parts/sidebar', 'bb' );
                    }

                endwhile;
            endif;
            ?>
		</div>
	</div>

	<?php wp_footer(); ?>
</body>
</html>