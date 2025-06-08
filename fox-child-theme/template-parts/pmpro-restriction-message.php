<?php
/**
 * Paid Memberships Pro - Özel Kısıtlama Mesajı Şablonu (ZeroHedge Stili + Üyelik Kartları - Revize)
 * - Ücretsiz üyelik kartı kaldırıldı.
 * - PDF uyarı barı kaldırıldı.
 *
 * Bu dosya, functions.php'deki filtre aracılığıyla çağrılır.
 *
 * @package Fox_Child
 */

global $post; // $post değişkenine erişim

// Gerekli URL'leri alalım
$login_url       = wp_login_url( get_permalink( $post ? $post->ID : null ) );
// $levels_page_url = function_exists('pmpro_url') ? pmpro_url( 'levels' ) : ''; // İsterseniz tüm seviyeler linki için

?>
<div class="pmpro-custom-restriction zerohedge-style">

    <div class="restriction-header">
        <h2><?php esc_html_e( 'BU İÇERİK PREMIUM ÜYELERİMİZE ÖZELDİR', 'fox-child' ); ?></h2>
        <p class="subtitle"><?php esc_html_e( 'Finans dünyasının derinliklerine dalmak ve özel analizlere erişmek için şimdi katılın!', 'fox-child' ); ?></p>
    </div>

    <div class="already-member">
        <p>
            <?php esc_html_e( 'Zaten Üye misiniz?', 'fox-child' ); ?>
            <a href="<?php echo esc_url( $login_url ); ?>" class="button-like"><?php esc_html_e( 'GİRİŞ YAPIN', 'fox-child' ); ?></a>
        </p>
    </div>

    <?php // --- Üyelik Seviyeleri HTML Başlangıcı --- ?>

    <?php /* --- KALDIRILDI: ÜST UYARI ---
    <div class="abonelik-uyari">
      ...
    </div>
    --- KALDIRILDI SONU --- */ ?>

    <!-- PLANLAR -->
    <div class="abone-kutular">

      <?php /* --- KALDIRILDI: Ücretsiz Üyelik ---
      <div class="abone-kutu uyelik">
        ...
      </div>
       --- KALDIRILDI SONU --- */ ?>

      <!-- Aylık Abonelik -->
      <div class="abone-kutu">
        <h2><?php esc_html_e( 'Aylık Abonelik', 'fox-child' ); ?></h2>
        <div class="fiyat">$8 / <?php esc_html_e( 'Ay', 'fox-child' ); ?></div>
        <ul>
          <li>✔ <?php esc_html_e( 'Güncel Portföy Dağılımı', 'fox-child' ); ?></li>
          <li>✔ <?php esc_html_e( 'Aylık Performans Raporu', 'fox-child' ); ?></li>
          <li>✔ <?php esc_html_e( 'Multi Asset İzleme Listesi', 'fox-child' ); ?></li>
          <li>✔ <?php esc_html_e( 'Araştırma ve Raporlar', 'fox-child' ); ?></li>
          <li>✔ <?php esc_html_e( 'Reklamsız Kullanım', 'fox-child' ); ?></li>
          <li>📬 <?php esc_html_e( 'Haftalık Bülten', 'fox-child' ); ?> <em>(<?php esc_html_e( 'yakında', 'fox-child' ); ?>)</em></li>
        </ul>
         <?php // WooCommerce kullanıyorsanız add-to-cart linki doğru olabilir.
               // PMP checkout kullanıyorsanız: pmpro_url('checkout', '?level=X') şeklinde olmalı (X = seviye ID'si) ?>
        <a class="buton" href="https://borsadabibasina.com/?add-to-cart=8550"><?php esc_html_e( 'Aylık Abone Ol', 'fox-child' ); ?></a>
      </div>

      <!-- Yıllık Abonelik -->
      <div class="abone-kutu">
        <h2><?php esc_html_e( 'Yıllık Abonelik', 'fox-child' ); ?></h2>
        <div class="fiyat">$72 / <?php esc_html_e( 'Yıl', 'fox-child' ); ?> <br><small>(<?php esc_html_e( '3 Ay Ücretsiz', 'fox-child' ); ?>)</small></div>
        <ul>
          <li>✔ <?php esc_html_e( 'Tüm Aylık Abonelik İçerikleri', 'fox-child' ); ?></li>
          <li>💰 <?php esc_html_e( 'Fiyat Avantajı', 'fox-child' ); ?></li>
        </ul>
        <?php // WooCommerce kullanıyorsanız add-to-cart linki doğru olabilir.
               // PMP checkout kullanıyorsanız: pmpro_url('checkout', '?level=Y') şeklinde olmalı (Y = seviye ID'si) ?>
        <a class="buton" href="https://borsadabibasina.com/?add-to-cart=8551"><?php esc_html_e( 'Yıllık Abone Ol', 'fox-child' ); ?></a>
      </div>
    </div>

    <!-- ALT AÇIKLAMA -->
    <div class="alt-bilgi">
      <?php esc_html_e( 'Tüm abonelikler iptal edilebilir. Ödeme sonrası hesabınızla giriş yaparak içeriğe erişebilirsiniz.', 'fox-child' ); ?>
      <?php printf(
            esc_html__( 'Herhangi bir sorun yaşarsanız %s adresine ulaşabilirsiniz.', 'fox-child' ),
            '<a href="mailto:info@borsadabibasina.com">info@borsadabibasina.com</a>'
        ); ?>
    </div>

    <?php // --- Üyelik Seviyeleri HTML Sonu --- ?>

    <?php /* --- KALDIRILDI: En alttaki "Already a member?" yazısı (Eğer buradaysa) ---
    <p class="pmpro_actions_nav">
        Already a member? <a href="<?php echo esc_url( wp_login_url( pmpro_url( "account" ) ) ); ?>">Login</a>
    </p>
    --- KALDIRILDI SONU --- */ ?>

</div>