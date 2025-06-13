<?php
/**
 * Template Name: BB Theme - Araştırma Listesi
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
<body <?php body_class(); ?>>

	<header class="site-header">
        <div class="header-content">
			<div class="header-left"> <div class="logo"> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"> <?php if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) { the_custom_logo(); } else { bloginfo( 'name' ); } ?> </a> </div> <nav class="primary-navigation"> <ul> <li><a href="https://borsadabibasina.com/hakkinda">Hakkında</a></li> <li><a href="https://borsadabibasina.com/iletisim">İletişim</a></li> <li><a href="https://borsadabibasina.com/yasal-uyari">Yasal Uyarı</a></li> <li><a href="https://borsadabibasina.com/gizlilik">Gizlilik</a></li> <li><a href="https://borsadabibasina.com/abonelik">Abonelik Seviyeleri</a></li> </ul> </nav> </div>
			<div class="header-right"> <button class="icon-button" aria-label="Search"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button> <a href="https://borsadabibasina.com/hesabim" class="subscribe-button">Hesabım</a> <button class="theme-toggle-button icon-button" aria-label="<?php esc_attr_e( 'Temayı Değiştir', 'fox-child' ); ?>">🌙</button> </div>
		</div>
	</header>

<?php get_template_part('template-parts/ticker-bar'); ?>

	<div class="main-container">

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
                        echo '<main class="main-column">';

                        if ( $is_restricted_by_plugin ) {
                            // --- ERİŞİM YOK: Eklentinin oluşturduğu mesajı bas. ---
                            echo $original_content;
                        } else {
                            // --- ERİŞİM VAR: Araştırma listesini göster. ---
                            ?>
                            <div class="research-list-container">
                                <?php
                                $research_category_slug = 'arastirma';
                                $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
                                $research_args = array(
                                    'post_type'      => 'post',
                                    'post_status'    => 'publish',
                                    'category_name'  => $research_category_slug,
                                    'posts_per_page' => 10,
                                    'paged'          => $paged,
                                    'orderby'        => 'date',
                                    'order'          => 'DESC',
                                );
                                $research_query = new WP_Query( $research_args );

                                if ( $research_query->have_posts() ) :
                                    echo '<div class="research-article-list">';
                                    while ( $research_query->have_posts() ) : $research_query->the_post();
                                    ?>
                                        <article id="post-<?php the_ID(); ?>" <?php post_class( 'research-article-item' ); ?>>
                                            <?php if ( has_post_thumbnail() ) : ?>
                                                <div class="research-article-thumbnail"> <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"> <?php the_post_thumbnail( 'large' ); ?> </a> </div>
                                            <?php endif; ?>
                                            <div class="research-article-content">
                                                <h2 class="research-article-title"> <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a> </h2>
                                                <div class="research-article-meta"> <span class="posted-on"><time class="entry-date published updated" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></span> </div>
                                                <div class="research-article-excerpt"> <?php if ( has_excerpt() ) { the_excerpt(); } else { echo wp_trim_words( get_the_content(), 30, '...' ); } ?> </div>
                                            </div>
                                        </article>
                                    <?php 
                                    endwhile;
                                    echo '</div>'; // .research-article-list
                                    
                                    // Sayfalama
                                    $big = 999999999; 
                                    echo '<nav class="pagination research-pagination">';
                                    echo paginate_links( array(
                                        'base'    => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                                        'format'  => '?paged=%#%',
                                        'current' => max( 1, $paged ),
                                        'total'   => $research_query->max_num_pages,
                                        'prev_text' => esc_html__( '« Önceki', 'fox-child' ),
                                        'next_text' => esc_html__( 'Sonraki »', 'fox-child' ),
                                    ) );
                                    echo '</nav>';
                                else :
                                    echo '<p>' . esc_html__( 'Gösterilecek araştırma yazısı bulunamadı.', 'fox-child' ) . '</p>';
                                endif;
                                wp_reset_postdata();
                                ?>
                            </div>
                            <?php
                        }

                        // Ana içerik sütununu kapat
                        echo '</main>';

                        // --- SIDEBAR KONTROLÜ ---
                        // Sidebar, sadece içerik kısıtlı DEĞİLSE gösterilir.
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