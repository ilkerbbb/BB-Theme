<?php
/**
 * BB Theme - Tekil Yazı Şablonu
 *
 * Belirli kategorilerdeki tekil yazıların BB Theme görünümüyle gösterilmesini sağlar.
 * Ana yazı başlığı GÖSTERİLİR.
 * Öne çıkan görsel (thumbnail) KALDIRILDI.
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
                        <?php
                        if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) {
                            the_custom_logo();
                        } else {
                            bloginfo( 'name' );
                        }
                        ?>
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

		<div class="content-body">

			<main class="main-column">

				<?php
				if ( have_posts() ) :
					while ( have_posts() ) :
						the_post();
						?>
						<article id="post-<?php the_ID(); ?>" <?php post_class( 'bb-theme-single-article' ); ?>>

							<header class="entry-header single-entry-header">
								<?php the_title( '<h1 class="entry-title">', '</h1>' ); // Başlık GÖSTERİLİYOR ?>

								<div class="entry-meta single-entry-meta">
									<span class="author vcard"><?php printf( '<a class="url fn n" href="%1$s">%2$s</a>', esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ), esc_html( get_the_author() ) ); ?></span>
									<span class="separator">|</span>
									<span class="posted-on"><time class="entry-date published updated" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></span>
									<?php
									$categories_list = get_the_category_list( ', ' );
									if ( $categories_list ) {
										printf( '<span class="separator">|</span> <span class="categories-links">%s</span>', $categories_list ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									}
									?>
								</div><!-- .entry-meta -->
							</header><!-- .entry-header -->

							<?php /* ÖNE ÇIKAN GÖRSEL BÖLÜMÜ KALDIRILDI
							<?php if ( has_post_thumbnail() ) : ?>
								<div class="post-thumbnail single-post-thumbnail">
									<?php the_post_thumbnail( 'large' ); ?>
								</div><!-- .post-thumbnail -->
							<?php endif; ?>
							*/ ?>

							<div class="entry-content single-entry-content">
								<?php
								the_content();

								wp_link_pages(
									array(
										'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'fox-child' ),
										'after'  => '</div>',
									)
								);
								?>
							</div><!-- .entry-content -->

							<footer class="entry-footer single-entry-footer">
								<?php
								$tags_list = get_the_tag_list( '', ', ' );
								if ( $tags_list ) {
									printf( '<span class="tags-links">' . esc_html__( 'Etiketler: %1$s', 'fox-child' ) . '</span>', $tags_list ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>
							</footer><!-- .entry-footer -->

						</article><!-- #post-<?php the_ID(); ?> -->
						<?php

						if ( comments_open() || get_comments_number() ) :
							comments_template();
						endif;

					endwhile;
				else :
					get_template_part( 'template-parts/content', 'none' );
				endif;
				?>

			</main> <?php // .main-column sonu ?>

            <?php get_template_part( 'template-parts/sidebar', 'bb' ); ?>

		</div> <?php // .content-body sonu ?>

	</div> <?php // .main-container sonu ?>

	<?php // Footer bu şablonda yok ?>

	<?php wp_footer(); ?>
</body>
</html>