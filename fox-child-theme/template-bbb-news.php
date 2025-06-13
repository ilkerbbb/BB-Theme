<?php
/**
 * Template Name: BB Theme - BBB News
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
<body <?php body_class('bbb-news-template'); ?>>

	<header class="site-header">
        <div class="header-content">
			<div class="header-left">
                <div class="logo">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
                        <?php if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) { the_custom_logo(); } else { bloginfo( 'name' ); } ?>
                    </a>
                </div>
				<nav class="primary-navigation">
					<ul>
						<li><a href="https://borsadabibasina.com/hakkinda">Hakkında</a></li>
						<li><a href="https://borsadabibasina.com/iletisim">İletişim</a></li>
						<li><a href="https://borsadabibasina.com/yasal-uyari">Yasal Uyarı</a></li>
						<li><a href="https://borsadabibasina.com/gizlilik">Gizlilik</a></li>
						<li><a href="https://borsadabibasina.com/abonelik">Abonelik Seviyeleri</a></li>
					</ul>
				</nav>
			</div>
			<div class="header-right">
				<button class="icon-button" aria-label="Search"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
				<a href="https://borsadabibasina.com/hesabim" class="subscribe-button">Hesabım</a>
				<button class="theme-toggle-button icon-button" aria-label="<?php esc_attr_e( 'Temayı Değiştir', 'fox-child' ); ?>">🌙</button>
			</div>
		</div>
	</header>

<?php get_template_part('template-parts/ticker-bar'); ?>

	<div class="main-container bbb-news-page-container">

		<section class="content-header">
            <nav class="secondary-navigation">
				<?php if ( has_nav_menu( 'bb_secondary_menu' ) ) { wp_nav_menu( array( 'theme_location' => 'bb_secondary_menu', 'container' => false, 'menu_class' => 'secondary-nav-list', 'depth' => 1 ) ); } else { echo '<ul class="secondary-nav-list"><li><a href="#">' . esc_html__( 'Menü Ata', 'fox-child' ) . '</a></li></ul>'; } ?>
			</nav>
		</section>

		<div class="content-body bbb-news-layout">
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
                    echo '<main class="main-column bbb-news-content">';

                    if ( $is_restricted_by_plugin ) {
                        // --- ERİŞİM YOK: Eklentinin oluşturduğu mesajı bas. ---
                        echo $original_content;
                    } else {
                        // --- ERİŞİM VAR: Sayfanın asıl içeriğini (kısa kodlar vb.) göster. ---
                        ?>
                        <article id="post-<?php the_ID(); ?>" <?php post_class( 'bbb-news-page-article' ); ?>>
                            <div class="entry-content bbb-news-page-content">
                                <?php echo do_shortcode($original_content); // İçeriği tekrar çalıştırarak kısa kodların işlemesini sağla ?>
                            </div>
                        </article>
                        <?php
                    }
                    
                    // Ana içerik sütununu kapat
                    echo '</main>';

                    // --- SIDEBAR KONTROLÜ ---
                    // Sidebar, sadece içerik kısıtlı DEĞİLSE gösterilir.
                    if ( ! $is_restricted_by_plugin ) : ?>
                        <aside class="right-sidebar bbb-news-sidebar">
                            <section class="widget keyword-cloud-widget">
                                <h3 class="widget-title"><?php esc_html_e( 'Günün Öne Çıkan Kelimeleri', 'fox-child' ); ?></h3>
                                <?php echo do_shortcode('[bbb_word_cloud]'); ?>
                            </section>
                            <section class="widget sentiment-gauge-widget">
                                <?php echo do_shortcode('[bbb_gauge title="Piyasa Hissiyatı"]'); ?>
                            </section>
                            <section class="widget sentiment-timeseries-widget">
                                 <h3 class="widget-title"><?php esc_html_e( 'Günlük Duyarlılık Zaman Serisi', 'fox-child' ); ?></h3>
                                <?php echo do_shortcode('[bbb_sentiment_chart]'); ?>
                            </section>
                        </aside>
                    <?php endif;

                endwhile;
            endif;
            ?>
		</div>

	</div>

	<?php wp_footer(); ?>
</body>
</html>