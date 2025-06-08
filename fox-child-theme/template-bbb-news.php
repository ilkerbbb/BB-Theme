<?php
/**
 * Template Name: BB Theme - BBB News
 * Description: Custom template for the BBB News page with a specific sidebar layout and wider content area.
 * --- REVISED: Added PMP access check ---
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
<body <?php body_class('bbb-news-template'); // Özel bir body class ekleyelim ?>>

	<header class="site-header">
		<?php // BB Theme Header içeriği ?>
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
			<?php // BB Theme İkincil Menü ?>
            <nav class="secondary-navigation">
				<?php
				if ( has_nav_menu( 'bb_secondary_menu' ) ) {
					wp_nav_menu( array( 'theme_location' => 'bb_secondary_menu', 'container' => false, 'menu_class' => 'secondary-nav-list', 'depth' => 1 ) );
				} else {
					echo '<ul class="secondary-nav-list"><li><a href="#">' . esc_html__( 'Menü Ata', 'fox-child' ) . '</a></li></ul>';
				}
				?>
			</nav>
		</section>

		<div class="content-body bbb-news-layout">

			<main class="main-column bbb-news-content">
                <?php
                // === ANA WORDPRESS DÖNGÜSÜ VE YETKİ KONTROLÜ (PMP Entegrasyonu) ===
                if ( have_posts() ) :
                    while ( have_posts() ) :
                        the_post();

                        // ** PMP YETKİ KONTROLÜ **
                        $has_access = true; // Varsayılan olarak erişim var sayalım
                        if ( function_exists('pmpro_has_membership_access') ) {
                            // Fonksiyon varsa, mevcut sayfa için erişimi kontrol et
                            $has_access = pmpro_has_membership_access( get_the_ID() );
                        }

                        if ( $has_access ) : // Eğer kullanıcı yetkiliyse...
                            ?>
                            <article id="post-<?php the_ID(); ?>" <?php post_class( 'bbb-news-page-article' ); ?>>
                                <?php // Sayfa başlığını gizleyebilir veya gösterebiliriz ?>
                                <?php // the_title( '<h1 class="entry-title bbb-news-page-title">', '</h1>' ); ?>

                                <div class="entry-content bbb-news-page-content">
                                    <?php
                                    // Sayfanın kendi içeriğini (filtreler, liste vb.) gösterir
                                    the_content();
                                    ?>
                                </div><!-- .entry-content -->
                            </article>
                            <?php
                        else : // Kullanıcı yetkili DEĞİLSE...
                            // PMP'nin kısıtlama mesajını göstermesi için the_content() çağır.
                            // Bu, functions.php'deki filtremiz aracılığıyla özel mesajımızı tetikleyecektir.
                            the_content();
                        endif; // Yetki kontrolü sonu

                    endwhile; // Ana döngüyü bitir
                else :
                    // İçerik bulunamazsa
                    get_template_part( 'template-parts/content', 'none' );
                endif; // Ana döngü kontrolünü bitir
                // === ANA WORDPRESS DÖNGÜSÜ SONU ===
                ?>
			</main> <?php // .main-column sonu ?>

            <?php
            // Sidebar'ı sadece yetkili kullanıcılar için göster
            if ( $has_access ) : ?>
                <aside class="right-sidebar bbb-news-sidebar">
                    <?php // Widget'ları kısa kodlarla ekliyoruz ?>

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

                </aside> <?php // .right-sidebar sonu ?>
            <?php endif; // $has_access kontrolü sonu (sidebar için) ?>

		</div> <?php // .content-body sonu ?>

	</div> <?php // .main-container sonu ?>

	<?php // Footer bu şablonda yok ?>

	<?php wp_footer(); ?>
</body>
</html>