<?php
/**
 * Template Name: BB Theme - Blank (No Sidebar)
 *
 * Bu şablon, BB Theme header, ticker ve ikincil menüsünü içerir ancak sidebar
 * veya bb-theme.php'ye özgü diğer içerik bölümlerini içermez.
 * Sayfa düzenleyicisinden eklenen içerik veya kısa kodlar tam genişlikte gösterilir.
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
<body <?php body_class(); ?>>

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
				<button class="theme-toggle-button icon-button" aria-label="<?php esc_attr_e( 'Temayı Değiştir', 'fox-child' ); ?>">
					🌙
				</button>
			</div>
		</div>
	</header>

<?php get_template_part('template-parts/ticker-bar'); ?>

	<div class="main-container">

		<section class="content-header">
			<nav class="secondary-navigation">
				<?php
				if ( has_nav_menu( 'bb_secondary_menu' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'bb_secondary_menu',
							'container'      => false,
							'menu_class'     => 'secondary-nav-list',
							'depth'          => 1,
						)
					);
				} else {
					echo '<ul class="secondary-nav-list"><li><a href="#">' . esc_html__( 'Menü Ata', 'fox-child' ) . '</a></li></ul>';
				}
				?>
			</nav>
		</section>

		<div class="content-body blank-content-body">
			<main class="main-column full-width">
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'bb-theme-blank-page' ); ?>>
					<div class="entry-content blank-page-content">
						<?php
						// Bu şablon, kısıtlama kurallarını da doğal olarak destekler.
						// the_content() çağrısı, eklentinin kararını (içeriği veya kısıtlama mesajını gösterme) ekrana basar.
						if ( have_posts() ) :
							while ( have_posts() ) :
								the_post();
								the_content();
							endwhile;
						endif;
						?>
					</div>
				</article>
			</main>
		</div>

	</div>

	<?php wp_footer(); ?>
</body>
</html>