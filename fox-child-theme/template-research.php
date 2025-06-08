<?php
/**
 * Template Name: BB Theme - Araştırma Listesi
 * !! Kısıtlama eklentilerinin doğru çalışması için YETKİ KONTROLÜ eklendi (PMP Entegrasyonu) !!
 * @package Fox_Child
 */

// --- PMP Entegrasyonu: Özel fonksiyonu kaldırdık, doğrudan PMP fonksiyonunu kullanacağız ---
/*
// --- EKLENTİYE ÖZEL YETKİ KONTROLÜ ---
// Kullandığınız eklentinin fonksiyonunu buraya girin.
function fox_child_can_user_view_content() {
    // !! GERÇEK EKLENTİ FONKSİYONUNUZU BURAYA EKLEYİN !!
    // Örnek Placeholder:
    // return is_user_logged_in();
    return false; // VEYA eklenti yoksa true dönebilirsiniz: return true;
}
// --- EKLENTİ KONTROLÜ SONU ---
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
        <div class="header-content">
			<div class="header-left"> <div class="logo"> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"> <?php if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) { the_custom_logo(); } else { bloginfo( 'name' ); } ?> </a> </div> <nav class="primary-navigation"> <ul> <li><a href="https://borsadabibasina.com/hakkinda">Hakkında</a></li> <li><a href="https://borsadabibasina.com/iletisim">İletişim</a></li> <li><a href="https://borsadabibasina.com/yasal-uyari">Yasal Uyarı</a></li> <li><a href="https://borsadabibasina.com/gizlilik">Gizlilik</a></li> <li><a href="https://borsadabibasina.com/abonelik">Abonelik Seviyeleri</a></li> </ul> </nav> </div>
			<div class="header-right"> <button class="icon-button" aria-label="Search"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button> <a href="https://borsadabibasina.com/hesabim" class="subscribe-button">Hesabım</a> <button class="theme-toggle-button icon-button" aria-label="<?php esc_attr_e( 'Temayı Değiştir', 'fox-child' ); ?>">🌙</button> </div>
		</div>
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
                        // Varsayılan olarak erişim var sayalım, PMP yoksa veya erişim varsa true kalır
                        $has_access = true;
                        if ( function_exists('pmpro_has_membership_access') ) {
                            $has_access = pmpro_has_membership_access( get_the_ID() );
                        }

                        if ( $has_access ) : // Eğer kullanıcı yetkiliyse...

                            // --- Sayfanın Kendi İçeriğini (Giriş Metni vb.) Göster ---
                             $page_content_intro = get_the_content(); // İçeriği al
                             if( !empty(trim($page_content_intro)) ) {
                                echo '<header class="page-header research-header">';
                                echo '<div class="page-content research-intro">';
                                // PMP filtrelerinin çalışmaması için apply_filters kullanmak daha güvenli olabilir
                                // Eğer sayfa içeriğinde de PMP kısa kodu vs. varsa the_content() kullanın.
                                echo apply_filters( 'the_content', $page_content_intro );
                                // the_content(); // Alternatif olarak bunu kullanabilirsiniz
                                echo '</div>';
                                 echo '</header>';
                             } else {
                                 // İçerik yoksa boş başlık alanı yine de basılabilir veya kaldırılabilir
                                 echo '<header class="page-header research-header"></header>';
                             }
                            // --- Giriş Metni Sonu ---

                            // --- ARAŞTIRMA LİSTESİNİ OLUŞTUR ---
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
                                        // Not: Buradaki yazıların kendisi de PMP ile kısıtlı olabilir.
                                        // Ancak bu liste görünümünde sadece başlık/özet gösterildiği için
                                        // genellikle ek bir PMP kontrolü GEREKMEZ. Kullanıcı tıkladığında
                                        // tekil yazı şablonu (bb-theme-single-post.php) PMP tarafından korunacaktır.
                                    ?>
                                        <article id="post-<?php the_ID(); ?>" <?php post_class( 'research-article-item' ); ?>>
                                            <?php if ( has_post_thumbnail() ) : ?>
                                                <div class="research-article-thumbnail"> <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"> <?php the_post_thumbnail( 'thumbnail-large' ); ?> </a> </div>
                                            <?php endif; ?>
                                            <div class="research-article-content">
                                                <h2 class="research-article-title"> <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a> </h2>
                                                <div class="research-article-meta"> <span class="posted-on"><time class="entry-date published updated" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></span> </div>
                                                <div class="research-article-excerpt"> <?php if ( has_excerpt() ) { the_excerpt(); } else { $excerpt_content = get_the_content(); $excerpt_content = strip_shortcodes( $excerpt_content ); $excerpt_content = wp_strip_all_tags( $excerpt_content ); echo wp_trim_words( $excerpt_content, 25, '...' ); } ?> </div>
                                            </div>
                                        </article>
                                    <?php endwhile;
                                    echo '</div>';
                                    // Sayfalama
                                    $big = 999999999; echo '<nav class="pagination research-pagination">'; echo paginate_links( array( 'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ), 'format' => '?paged=%#%', 'current' => max( 1, $paged ), 'total' => $research_query->max_num_pages, 'prev_text' => esc_html__( '« Önceki', 'fox-child' ), 'next_text' => esc_html__( 'Sonraki »', 'fox-child' ) ) ); echo '</nav>';
                                else :
                                    echo '<p>' . esc_html__( 'Gösterilecek araştırma yazısı bulunamadı.', 'fox-child' ) . '</p>';
                                endif;
                                wp_reset_postdata(); // Özel sorgudan sonra ana sorguyu sıfırla
                                ?>
                            </div>
                            <?php
                            // --- ARAŞTIRMA LİSTESİ SONU ---

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
            // $has_access değişkeni döngü içinde tanımlandığı için burada tekrar kontrol edemeyiz.
            // Sidebar kendi içinde kontrol yapacak.
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