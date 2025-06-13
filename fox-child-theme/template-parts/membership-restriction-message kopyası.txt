<?php
/**
 * WooCommerce Memberships - Özel Kısıtlama Mesajı Şablonu
 * Bu dosya, bir kısa kod aracılığıyla çağrılır.
 *
 * @package Fox_Child
 */

global $post;
$login_url = wp_login_url( get_permalink( $post ? $post->ID : null ) );
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

    <div class="abone-kutular">
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
        <a class="buton" href="https://borsadabibasina.com/?add-to-cart=8551"><?php esc_html_e( 'Yıllık Abone Ol', 'fox-child' ); ?></a>
      </div>
    </div>

    <div class="alt-bilgi">
      <?php esc_html_e( 'Tüm abonelikler iptal edilebilir. Ödeme sonrası hesabınızla giriş yaparak içeriğe erişebilirsiniz.', 'fox-child' ); ?>
      <?php printf(
            esc_html__( ' Herhangi bir sorun yaşarsanız %s adresine ulaşabilirsiniz.', 'fox-child' ),
            '<a href="mailto:info@borsadabibasina.com">info@borsadabibasina.com</a>'
        ); ?>
    </div>
</div>