<?php
/**
 * BB Theme Sidebar Template Part
 * Includes Dynamic Market Summary widget.
 * Includes Podcast Widget with custom player interface.
 * Includes Dynamic Latest News widget via API (PHP Proxy).
 * !! Kısıtlama eklentileri için içerik kontrolü güncellendi (WooCommerce Memberships) !!
 * --- REVISED: Removed access control logic from this file. The parent template (`bb-theme.php`) is now responsible for deciding if the sidebar should be loaded. ---
 *
 * @package Fox_Child
 */

// --- YETKİ KONTROLÜ BU DOSYADAN KALDIRILDI ---
// Bu sidebar dosyası, sadece çağrıldığı ana şablon dosyası (`bb-theme.php` gibi)
// zaten yetki kontrolünü geçtiyse yüklenir. Bu nedenle buradaki kontrol gereksizdir.

?>
<aside class="right-sidebar"> <?php // BB Theme sağ sidebar ?>

    <?php // === Dinamik Piyasa Özeti Widget ===
    // Transient'tan veriyi çekelim. Veri yoksa veya geçersizse boş bir dizi olsun.
    $sidebar_data = get_transient('fox_child_sidebar_data');
     if ( ! is_array($sidebar_data) ) {
        $sidebar_data = []; // Hatalı transient durumunda boş dizi ata
    }
    ?>
    <section class="widget market-data-widget">
         <h3 class="widget-title"><?php esc_html_e('Piyasalar Özeti', 'fox-child'); ?></h3>
         <?php if ( ! empty( $sidebar_data ) ) : ?>
             <ul class="market-summary-list">
                 <?php foreach ( $sidebar_data as $item ) :
                        // Yön sınıfını belirle (CSS'e göre 'change-up'/'change-down' olmalı)
                        $direction_class = '';
                         if ( isset($item['direction']) ) { // 'direction' anahtarının varlığını kontrol et
                            if ( $item['direction'] === 'up' ) {
                                $direction_class = 'change-up';
                            } elseif ( $item['direction'] === 'down' ) {
                                $direction_class = 'change-down';
                            }
                        }
                        // Yüzdelik değişim metnini al, yoksa boş string
                        $change_text = isset($item['change_pct']) ? $item['change_pct'] : '';
                 ?>
                     <li>
                         <span><?php echo esc_html( $item['name'] ?? 'N/A' ); ?></span>
                         <span class="value"><?php echo esc_html( $item['value'] ?? '...' ); ?></span>
                         <?php // Sadece değişim metni varsa span'ı göster ?>
                         <?php if ( ! empty( $change_text ) ) : ?>
                            <span class="change <?php echo esc_attr( $direction_class ); ?>"><?php echo esc_html( $change_text ); ?></span>
                         <?php endif; ?>
                     </li>
                 <?php endforeach; ?>
             </ul>
         <?php else : ?>
            <?php // Veri henüz yüklenmemişse veya transient boşsa gösterilecek fallback ?>
            <p><?php esc_html_e('Piyasa verileri yükleniyor...', 'fox-child'); ?></p>
         <?php endif; ?>
    </section>
    <?php // === SON: Dinamik Piyasa Özeti Widget === ?>


    <section class="widget polymarket-widget">
         <h3 class="widget-title">Polymarket: US Recession 2025</h3>
         <div id="polymarket-market-embed"> <script type="module" src="https://unpkg.com/@polymarket/embeds@latest/dist/index.js"></script> <polymarket-market-embed market="us-recession-in-2025" volume="true" chart="false" theme="light"></polymarket-market-embed> </div>
    </section>

    <?php // === Dinamik Son Haberler Widget === ?>
    <section class="widget latest-news-widget">
         <h3 class="widget-title">Son Haberler</h3>
         <div class="latest-news-list-container"> <div class="loading-news" style="padding: 15px 0; text-align: center; color: var(--secondary-text-color);"><?php esc_html_e( 'Yükleniyor...', 'fox-child' ); ?></div> <ul class="latest-news-list" style="display: none;"> </ul> <div class="error-news" style="display: none;"></div> <div class="see-all-news" style="display: none;"> <a href="<?php echo esc_url(home_url('/bbb-news')); // Doğru URL'yi kullan ?>"> <?php esc_html_e('Tüm haberlere bak', 'fox-child'); ?> → </a> </div> </div>
    </section>
    <?php // === SON: Dinamik Son Haberler Widget === ?>


    <?php // === PODCAST WIDGET BÖLÜMÜ === ?>
    <section class="widget podcast-widget">
        <h3 class="widget-title">Podcast: Borsada bi' Başına</h3>
        <?php
        // Podcast verisini çek (functions.php'deki fonksiyonu kullanır)
        $podcast_rss_url = 'https://anchor.fm/s/98d8f0fc/podcast/rss';
        $podcast_data = null; // Başlangıç değeri
        if (function_exists('fox_child_get_podcast_data')) {
             $podcast_data = fox_child_get_podcast_data( $podcast_rss_url );
        }

        // Veri varsa ve hata yoksa göster
        if ( $podcast_data && !isset($podcast_data['error']) && isset( $podcast_data['latest_episode'] ) ) :
            $latest = $podcast_data['latest_episode'];
            $episode_list = isset($podcast_data['episode_list']) ? $podcast_data['episode_list'] : array();
        ?>
            <div class="podcast-player-wrapper">
                <div class="current-episode-info">
                    <span class="current-episode-label">SON BÖLÜM:</span>
                    <h4 id="bb-podcast-current-title"><?php echo esc_html($latest['title']); ?></h4>
                </div>
                <div class="bb-audio-player" id="bb-podcast-player-container">
                    <audio id="bb-podcast-audio-element" preload="metadata">
                        <?php if ( !empty($latest['url']) ) : ?>
                            <source src="<?php echo esc_url($latest['url']); ?>" type="audio/mpeg">
                        <?php endif; ?>
                    </audio>
                    <button class="bb-play-pause-button" aria-label="Oynat/Duraklat" <?php disabled( empty( $latest['url'] ) ); ?>>
                        <svg class="play-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"></path></svg>
                        <svg class="pause-icon" viewBox="0 0 24 24" fill="currentColor" style="display:none;"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"></path></svg>
                    </button>
                    <div class="bb-time-indicator">
                        <span class="current-time">0:00</span> / <span class="total-duration"><?php echo esc_html($latest['duration'] ? $latest['duration'] : '0:00'); ?></span>
                    </div>
                </div>
            </div>
            <?php if ( ! empty( $episode_list ) ) : ?>
                <div class="podcast-episode-list-wrapper">
                    <h4>Önceki Bölümler</h4>
                    <ul class="podcast-episode-list">
                        <?php foreach ( $episode_list as $episode ) : ?>
                            <?php if ( ! empty( $episode['url'] ) ) : ?>
                                <li class="podcast-episode-item" data-audio-src="<?php echo esc_attr( $episode['url'] ); ?>" data-audio-title="<?php echo esc_attr( $episode['title'] ); ?>" data-audio-duration="<?php echo esc_attr( $episode['duration'] ); ?>" tabindex="0" role="button">
                                    <span class="episode-title"><?php echo esc_html($episode['title']); ?></span>
                                    <?php if ( !empty($episode['duration']) ) : ?>
                                        <span class="episode-duration"><?php echo esc_html($episode['duration']); ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; // episode_list kontrolü ?>
        <?php else : // Podcast verisi yoksa veya hata varsa ?>
            <p><?php esc_html_e('Podcast bölümleri yüklenirken bir sorun oluştu veya henüz bölüm yok.', 'fox-child'); ?></p>
        <?php endif; // podcast_data kontrolü ?>
    </section>
    <?php // === PODCAST WIDGET BÖLÜMÜ SONU === ?>

</aside>