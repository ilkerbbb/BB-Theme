<?php
/**
 * Template Name: BB Theme - Bülten Listesi
 * !! Kısıtlama eklentilerinin doğru çalışması için YETKİ KONTROLÜ eklendi (PMP Entegrasyonu) !!
 * --- REVISED: Redesigned layout with 2-column card grid based on user request ---
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
        <!-- Header içeriği aynı kalıyor -->
        <div class="header-content"> <div class="header-left"> <div class="logo"> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"> <?php if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) { the_custom_logo(); } else { bloginfo( 'name' ); } ?> </a> </div> <nav class="primary-navigation"> <ul> <li><a href="https://borsadabibasina.com/hakkinda">Hakkında</a></li> <li><a href="https://borsadabibasina.com/iletisim">İletişim</a></li> <li><a href="https://borsadabibasina.com/yasal-uyari">Yasal Uyarı</a></li> <li><a href="https://borsadabibasina.com/gizlilik">Gizlilik</a></li> <li><a href="https://borsadabibasina.com/abonelik">Abonelik Seviyeleri</a></li> </ul> </nav> </div> <div class="header-right"> <button class="icon-button" aria-label="Search"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button> <a href="https://borsadabibasina.com/hesabim" class="subscribe-button">Hesabım</a> <button class="theme-toggle-button icon-button" aria-label="<?php esc_attr_e( 'Temayı Değiştir', 'fox-child' ); ?>">🌙</button> </div> </div>
	</header>

<?php get_template_part('template-parts/ticker-bar'); ?>

	<div class="main-container">

		<section class="content-header">
            <!-- Navigasyon aynı kalıyor -->
			<nav class="secondary-navigation"> <?php if ( has_nav_menu( 'bb_secondary_menu' ) ) { wp_nav_menu( array( 'theme_location' => 'bb_secondary_menu', 'container' => false, 'menu_class' => 'secondary-nav-list', 'depth' => 1 ) ); } else { echo '<ul class="secondary-nav-list"><li><a href="#">' . esc_html__( 'Menü Ata', 'fox-child' ) . '</a></li></ul>'; } ?> </nav>
		</section>

		<div class="content-body">

			<main class="main-column">

                <?php
                // === ANA WORDPRESS DÖNGÜSÜ VE YETKİ KONTROLÜ (PMP Entegrasyonu) ===
                if ( have_posts() ) :
                    while ( have_posts() ) :
                        the_post();

                        // ** PMP YETKİ KONTROLÜ **
                        $has_access = true;
                        if ( function_exists('pmpro_has_membership_access') ) {
                            $has_access = pmpro_has_membership_access( get_the_ID() );
                        }

                        if ( $has_access ) : // Eğer kullanıcı yetkiliyse...

                            // --- Sayfanın Kendi İçeriğini (Giriş Metni vb.) Göster ---
                             $page_content_intro = get_the_content();
                             if( !empty(trim($page_content_intro)) ) {
                                echo '<header class="page-header bulten-header">'; // Class adı güncellenebilir
                                echo '<div class="page-content bulten-intro">'; // Class adı güncellenebilir
                                echo apply_filters( 'the_content', $page_content_intro );
                                echo '</div>';
                                 echo '</header>';
                             } else {
                                 echo '<header class="page-header bulten-header"></header>'; // Class adı güncellenebilir
                             }
                            // --- Giriş Metni Sonu ---

                            // --- YENİ BÜLTEN KART LİSTESİNİ OLUŞTUR ---
                            ?>
                            <div class="bulten-list-container"> <?php // Ana konteyner ?>
                                <?php
                                $bulten_category_slug = 'bulten';
                                $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
                                $bulten_args = array(
                                    'post_type'      => 'post',
                                    'post_status'    => 'publish',
                                    'category_name'  => $bulten_category_slug,
                                    'posts_per_page' => 10, // Sayfa başına kart sayısı (CSS 2 sütun yapacak)
                                    'paged'          => $paged,
                                    'orderby'        => 'date',
                                    'order'          => 'DESC',
                                );
                                $bulten_query = new WP_Query( $bulten_args );

                                if ( $bulten_query->have_posts() ) :
                                    // === YENİ Grid Konteyner ===
                                    echo '<div class="bulten-grid-container">';
                                    while ( $bulten_query->have_posts() ) : $bulten_query->the_post();
                                        // === YENİ Kart Yapısı ===
                                        ?>
                                        <article id="post-<?php the_ID(); ?>" <?php post_class( 'bulten-card-item' ); ?>>
                                            <?php if ( has_post_thumbnail() ) : ?>
                                                <div class="bulten-card-thumbnail">
                                                    <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                                                        <?php the_post_thumbnail( 'medium_large' ); // Veya 'large', temanızdaki uygun boyutu seçin ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <div class="bulten-card-content">
                                                <div class="bulten-card-category">
                                                    <?php
                                                    // Yazının ilk kategorisini alıp link olarak gösterelim
                                                    $categories = get_the_category();
                                                    if ( ! empty( $categories ) ) {
                                                        echo '<a href="' . esc_url( get_category_link( $categories[0]->term_id ) ) . '">' . esc_html( $categories[0]->name ) . '</a>';
                                                    }
                                                    ?>
                                                </div>
                                                <h2 class="bulten-card-title">
                                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                                </h2>
                                                <div class="bulten-card-meta">
                                                    <span class="posted-on">
                                                        <time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
                                                            <?php echo esc_html( get_the_date() ); // WordPress tarih formatını kullanır ?>
                                                        </time>
                                                    </span>
                                                </div>
                                                <div class="bulten-card-excerpt">
                                                    <?php
                                                    // Özeti göster, yoksa içeriği kırp
                                                    if ( has_excerpt() ) {
                                                        the_excerpt();
                                                    } else {
                                                        echo wp_trim_words( get_the_content(), 25, '...' ); // Kelime sayısını ayarlayabilirsiniz
                                                    }
                                                    ?>
                                                </div>
                                                <div class="bulten-card-readmore">
                                                    <a href="<?php the_permalink(); ?>"><?php esc_html_e( 'Devamı', 'fox-child' ); ?> →</a> <?php // Sağ ok eklendi ?>
                                                </div>
                                            </div>
                                        </article>
                                        <?php
                                        // === Kart Yapısı Sonu ===
                                    endwhile;
                                    echo '</div>'; // === Grid Konteyner Sonu ===

                                    // Sayfalama (Aynı kalır)
                                    $big = 999999999;
                                    echo '<nav class="pagination bulten-pagination">'; // Class adı güncellenebilir
                                    echo paginate_links( array(
                                        'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                                        'format' => '?paged=%#%',
                                        'current' => max( 1, $paged ),
                                        'total' => $bulten_query->max_num_pages,
                                        'prev_text' => esc_html__( '« Önceki', 'fox-child' ),
                                        'next_text' => esc_html__( 'Sonraki »', 'fox-child' )
                                    ) );
                                    echo '</nav>';
                                else :
                                    echo '<p>' . esc_html__( 'Gösterilecek bülten yazısı bulunamadı.', 'fox-child' ) . '</p>';
                                endif;
                                wp_reset_postdata(); // Özel sorgudan sonra ana sorguyu sıfırla
                                ?>
                            </div>
                            <?php
                            // --- BÜLTEN LİSTESİ SONU ---

                        else : // Kullanıcı yetkili DEĞİLSE...
                            // PMP'nin kısıtlama mesajını göstermesi için the_content() çağır.
                            the_content();
                        endif; // Yetki kontrolü sonu

                    endwhile; // Ana döngüyü bitir
                else :
                     echo '<p>' . esc_html__( 'İçerik bulunamadı.', 'fox-child' ) . '</p>';
                endif; // Ana döngü kontrolünü bitir
                // === ANA WORDPRESS DÖNGÜSÜ SONU ===
                ?>

			</main> <?php // .main-column sonu ?>

			<?php
            // Sidebar'ı sadece yetkili kullanıcılar için göster
             if ( $has_access ) { // Yukarıdaki PMP kontrol sonucunu kullan
                 get_template_part( 'template-parts/sidebar', 'bb' );
             }
            ?>

		</div> <?php // .content-body sonu ?>

	</div> <?php // .main-container sonu ?>

	<?php // Footer bu şablonda yok ?>

	<?php wp_footer(); ?>
</body>
</html>