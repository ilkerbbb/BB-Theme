<?php
/**
 * Template Part for Displaying the Ticker Bar
 * Fetches data from transient set by cron job.
 * Displays price and percentage change.
 *
 * @package Fox_Child
 */

// Transient'tan veriyi çekelim. Veri yoksa veya geçersizse boş bir dizi olsun.
$ticker_data = get_transient('fox_child_ticker_data');
if ( ! is_array($ticker_data) ) {
    $ticker_data = []; // Hatalı transient durumunda boş dizi ata
}
?>
<div class="ticker-bar">
    <div class="ticker-content">
        <?php if ( ! empty( $ticker_data ) ) : ?>
            <ul class="ticker-list">
                <?php foreach ( $ticker_data as $item ) :
                    // Yön sınıfını belirle
                    $direction_class = '';
                    if ( isset($item['direction']) ) { // 'direction' anahtarının varlığını kontrol et
                        if ( $item['direction'] === 'up' ) {
                            $direction_class = 'ticker-item__change--up';
                        } elseif ( $item['direction'] === 'down' ) {
                            $direction_class = 'ticker-item__change--down';
                        }
                    }
                    // Yüzdelik değişim metnini al, yoksa boş string
                    $change_text = isset($item['change_pct']) ? $item['change_pct'] : '';
                ?>
                    <li class="ticker-item"> <?php // data-symbol attribute'u isteğe bağlı ?>
                        <span class="ticker-item__name"><?php echo esc_html( $item['name'] ?? 'N/A' ); ?></span>
                        <span class="ticker-item__value"><?php echo esc_html( $item['value'] ?? '...' ); ?></span>
                        <?php // Sadece değişim metni varsa span'ı göster ?>
                        <?php if ( ! empty( $change_text ) ) : ?>
                            <span class="ticker-item__change <?php echo esc_attr( $direction_class ); ?>"><?php echo esc_html( $change_text ); ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <?php // Veri henüz yüklenmemişse veya transient boşsa gösterilecek fallback ?>
            <ul class="ticker-list">
                 <li class="ticker-item"><span class="ticker-item__name"><?php esc_html_e('Veri yükleniyor...', 'fox-child'); ?></span></li>
            </ul>
        <?php endif; ?>
    </div>
</div>