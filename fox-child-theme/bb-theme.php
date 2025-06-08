<?php
/**
 * Template Name: BB Theme
 *
 * Bu şablon, belirli sayfaların özel BB Theme tasarımını kullanmasını sağlar.
 * Ana içerik alanı artık Elementor yerine dinamik yazı akışı gösterir (Bloomberg stili).
 * Sidebar içeriği template-parts/sidebar-bb.php dosyasından çağrılır.
 * Footer bölümü bu şablondan kaldırılmıştır.
 * !! Kısıtlama eklentilerinin doğru çalışması için içerik kontrolü eklendi (PMP Entegrasyonu) !!
 * --- REVISED: Replaced the_content() with dynamic post fetching for Bloomberg-like layout ---
 * --- REVISED 2: Implemented a more accurate Bloomberg layout (Featured + Side Headlines + Grid) ---
 * --- REVISED 3: Detailed Bloomberg structure implementation based on user description ---
 * --- REVISED 4: Fine-tuned queries and structure for closer Bloomberg resemblance ---
 * --- REVISED 5: Focused on Hero Section layout (Thumbnail Left, Text Right) & Page Width ---
 * --- REVISED 6: Removed Hero Side Headlines, adjusted Hero Main layout, narrowed page width ---
 * --- REVISED 7 (Bloomberg Hero): Adjusted hero layout, font sizes, excerpt length, added podcast box. ---
 * --- REVISED 8 (Bloomberg Hero Refined): Pushed podcast box down, removed latest 3 headlines row. ---
 * --- REVISED 9 (Bloomberg Layout): Adjusted hero thumb size, aligned podcast box bottom. Redesigned Research section. ---
 * --- REVISED 10 (Alignment & Refinement): Align hero title top, added excerpt to featured research, centered research list. ---
 * --- REVISED 11 (Bulten Section & Link Fix): Added new centered 2-column Bulten section, fixed link colors. ---
 *
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
<body <?php body_class('page-template-bb-theme'); // Ensure body class is present ?>>

	<header class="site-header">
		<?php // Header içeriği aynı kalıyor ?>
		<div class="header-content"> <div class="header-left"> <div class="logo"> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"> <?php if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) { the_custom_logo(); } else { bloginfo( 'name' ); } ?> </a> </div> <nav class="primary-navigation"> <ul> <li><a href="https://borsadabibasina.com/hakkinda">Hakkında</a></li> <li><a href="https://borsadabibasina.com/iletisim">İletişim</a></li> <li><a href="https://borsadabibasina.com/yasal-uyari">Yasal Uyarı</a></li> <li><a href="https://borsadabibasina.com/gizlilik">Gizlilik</a></li> <li><a href="https://borsadabibasina.com/abonelik">Abonelik Seviyeleri</a></li> </ul> </nav> </div> <div class="header-right"> <button class="icon-button" aria-label="Search"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button> <a href="https://borsadabibasina.com/hesabim" class="subscribe-button">Hesabım</a> <button class="theme-toggle-button icon-button" aria-label="<?php esc_attr_e( 'Temayı Değiştir', 'fox-child' ); ?>">🌙</button> </div> </div>
	</header>

	<?php get_template_part('template-parts/ticker-bar'); ?>

	<div class="main-container bb-theme-dashboard-container"> <?php // Özel class ekleyelim ?>

		<section class="content-header">
            <?php // Navigasyon aynı kalıyor ?>
			<nav class="secondary-navigation"> <?php if ( has_nav_menu( 'bb_secondary_menu' ) ) { wp_nav_menu( array( 'theme_location' => 'bb_secondary_menu', 'container' => false, 'menu_class' => 'secondary-nav-list', 'depth' => 1 ) ); } else { echo '<ul class="secondary-nav-list"><li><a href="#">' . esc_html__( 'Menü Ata', 'fox-child' ) . '</a></li></ul>'; } ?> </nav>
		</section>

		<div class="content-body">

            <?php // ===> CLASS AYNI: dashboard-main-content-v7 <=== ?>
			<main class="main-column dashboard-main-content-v7">

                <?php
                // === ANA WORDPRESS DÖNGÜSÜ VE YETKİ KONTROLÜ (PMP Entegrasyonu) ===
                if ( have_posts() ) : // Sayfanın kendisi için döngü
                    while ( have_posts() ) :
                        the_post();

                        // ** PMP YETKİ KONTROLÜ **
                        $has_access = true;
                        if ( function_exists('pmpro_has_membership_access') ) {
                            $has_access = pmpro_has_membership_access( get_the_ID() );
                        }

                        if ( $has_access ) : // Eğer kullanıcı yetkiliyse...

                            // --- Dinamik Yazı Akışı (Bloomberg Stili V11 - Bülten Eklendi) ---
                            $dashboard_categories = 'arastirma,bulten'; // Gösterilecek ana kategoriler
                            $excluded_post_ids = array(); // Gösterilen ID'leri tut

                            // ===> PODCAST VERİSİNİ BAŞTA ÇEKELİM <===
                            $podcast_rss_url = 'https://anchor.fm/s/98d8f0fc/podcast/rss';
                            $podcast_data = null;
                            if (function_exists('fox_child_get_podcast_data')) {
                                $podcast_data = fox_child_get_podcast_data( $podcast_rss_url );
                            }
                            $latest_podcast_episode = ($podcast_data && !isset($podcast_data['error']) && isset( $podcast_data['latest_episode'] )) ? $podcast_data['latest_episode'] : null;
                            // ===> PODCAST VERİSİ SONU <===

                            ?>
                            <?php // ===> CLASS AYNI: dashboard-grid-container-v7 <=== ?>
                            <div class="dashboard-grid-container-v7">

                                <?php // ===> CLASS AYNI: dashboard-hero-area-v7 <=== ?>
                                <div class="dashboard-hero-area-v7">
                                    <?php // --- Bölüm 1: Ana Manşet ---
                                    $args_hero_main = array(
                                        'post_type'      => 'post', 'post_status'    => 'publish',
                                        'category_name'  => $dashboard_categories, 'posts_per_page' => 1,
                                        'orderby'        => 'date', 'order'          => 'DESC',
                                        'ignore_sticky_posts' => 1,
                                    );
                                    $hero_main_query = new WP_Query( $args_hero_main );

                                    if ( $hero_main_query->have_posts() ) :
                                        while ( $hero_main_query->have_posts() ) : $hero_main_query->the_post();
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
                                    <?php
                                        endwhile;
                                        wp_reset_postdata();
                                    endif;
                                    ?>
                                </div> <?php // .dashboard-hero-area-v7 sonu ?>


                                <?php // --- Bölüm 2: ARAŞTIRMA GRİDİ (V8) ---
                                $args_research_v8 = array(
                                    'post_type'      => 'post',
                                    'post_status'    => 'publish',
                                    'category_name'  => 'arastirma',
                                    'posts_per_page' => 3,
                                    'orderby'        => 'date',
                                    'order'          => 'DESC',
                                    'post__not_in'   => $excluded_post_ids,
                                    'ignore_sticky_posts' => 1,
                                );
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
                                                <?php foreach ( $research_v8_posts as $list_post ) :
                                                    $excluded_post_ids[] = $list_post->ID;
                                                ?>
                                                <li>
                                                    <a href="<?php echo esc_url( get_permalink($list_post->ID) ); ?>">
                                                        <?php echo esc_html( $list_post->post_title ); ?>
                                                    </a>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="section-more-link">
                                        <a href="https://borsadabibasina.com/arastirma">Tüm Araştırmalar →</a>
                                    </div>
                                </section>
                                <?php
                                endif;
                                ?>
                                <?php // === ARAŞTIRMA GRİDİ SONU === ?>


                                <?php
                                // ======================================================
                                // === BÖLÜM 3: YENİ BÜLTEN GRİDİ (Ortalanmış 2'li)   ===
                                // ======================================================
                                $args_bulten_v11 = array(
                                    'post_type'      => 'post',
                                    'post_status'    => 'publish',
                                    'category_name'  => 'bulten', // Sadece Bülten
                                    'posts_per_page' => 2, // Sadece 2 yazı
                                    'orderby'        => 'date',
                                    'order'          => 'DESC',
                                    'post__not_in'   => $excluded_post_ids, // Öncekileri hariç tut
                                    'ignore_sticky_posts' => 1,
                                );
                                $bulten_v11_query = new WP_Query( $args_bulten_v11 );

                                if ( $bulten_v11_query->have_posts() ) : ?>
                                <?php // ===> YENİ CLASS: dashboard-bulten-grid-v11 <=== ?>
                                <section class="dashboard-category-grid dashboard-bulten-grid-v11">
                                    <?php // Opsiyonel Başlık: <h2 class="section-title"><?php esc_html_e( 'Bülten', 'fox-child' ); ? ></h2> ?>
                                    <?php // ===> YENİ CLASS: bulten-grid-content-v11 <=== ?>
                                    <div class="bulten-grid-content-v11">
                                        <?php while ( $bulten_v11_query->have_posts() ) : $bulten_v11_query->the_post();
                                            // $excluded_post_ids[] = get_the_ID(); // Zaten hariç tutuldu
                                        ?>
                                        <?php // ===> YENİ CLASS: bulten-grid-item-v11 <=== ?>
                                        <article class="bulten-grid-item-v11">
                                            <?php if ( has_post_thumbnail() ) : ?>
                                                <?php // ===> YENİ CLASS: bulten-item-thumbnail-v11 <=== ?>
                                                <div class="bulten-item-thumbnail-v11">
                                                    <a href="<?php the_permalink(); ?>">
                                                        <?php the_post_thumbnail('medium_large'); // Boyut ayarlanabilir ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <h3 class="post-title">
                                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                            </h3>
                                        </article>
                                        <?php endwhile; ?>
                                    </div>
                                    <?php // ===> BÜLTEN LİNKİ GÜNCELLENDİ <=== ?>
                                    <div class="section-more-link">
                                        <a href="https://borsadabibasina.com/bulten">Tüm Bültenler →</a>
                                    </div>
                                </section>
                                <?php
                                    wp_reset_postdata();
                                endif; // End bulten_v11_query check
                                ?>
                                <?php // === YENİ BÜLTEN GRİDİ SONU === ?>


                            </div> <?php // .dashboard-grid-container-v7 sonu ?>

                            <?php
                            // --- Dinamik İçerik Sonu ---

                        else : // Kullanıcı yetkili DEĞİLSE...
                            the_content(); // PMP Mesajını göster
                        endif; // Yetki kontrolü sonu

                    endwhile; // Ana döngüyü bitir
                else :
                    get_template_part( 'template-parts/content', 'none' );
                endif; // Ana döngü kontrolünü bitir
                ?>

			</main> <?php // .main-column sonu ?>

			<?php
            // Sidebar'ı sadece yetkili kullanıcılar için göster
            if ( $has_access ) {
                get_template_part( 'template-parts/sidebar', 'bb' );
            }
            ?>

		</div> <?php // .content-body sonu ?>

	</div> <?php // .main-container sonu ?>

	<?php // Footer bölümü kaldırıldı ?>

	<?php wp_footer(); ?>

</body>
</html>