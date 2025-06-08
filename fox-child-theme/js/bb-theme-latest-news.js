/**
 * BB Theme - Latest News Widget Updater
 *
 * Fetches latest news from the custom REST endpoint and updates the sidebar widget.
 * Handles loading, error states, and formats the output with time ago.
 * Limits display to the number specified by PHP (or defaults if needed).
 */
jQuery(document).ready(function ($) {
    // Widget öğelerini seç
    const $widgetContainer = $('.latest-news-widget'); // Ana widget kapsayıcısı
    if (!$widgetContainer.length) {
        // console.log('Latest News Widget container not found.');
        return; // Widget yoksa çık
    }

    const $listContainer = $widgetContainer.find('.latest-news-list'); // Haberlerin ekleneceği UL
    const $loadingIndicator = $widgetContainer.find('.loading-news'); // Yükleniyor mesajı
    const $errorIndicator = $widgetContainer.find('.error-news'); // Hata mesajı
    const $seeAllLink = $widgetContainer.find('.see-all-news'); // Tümünü gör linki

    // Haber yok mesajı için yer tutucu (dinamik olarak eklenecek)
    const noNewsText = bbLatestNewsData.text_no_news || 'Gösterilecek haber bulunamadı.';
    const noNewsIndicatorHTML = '<div class="no-news" style="display: none;">' + noNewsText + '</div>';
    if ($widgetContainer.find('.no-news').length === 0) {
        // Insert after the list container, before the 'see all' link if possible
        if ($seeAllLink.length) {
             $seeAllLink.before(noNewsIndicatorHTML);
        } else {
            $listContainer.after(noNewsIndicatorHTML);
        }
    }
    const $noNewsIndicator = $widgetContainer.find('.no-news');

    // Kaç haber gösterileceği (PHP'den gelen bilgi, yedek olarak 6)
    const newsLimit = parseInt(bbLatestNewsData.news_limit || 6, 10);

    // Haberleri yükleme fonksiyonu
    function loadLatestNews() {
        // console.log('Fetching latest news...'); // Debug
        $loadingIndicator.show(); // Yükleniyor göster
        $errorIndicator.hide().text(''); // Hata mesajını gizle/temizle
        $noNewsIndicator.hide(); // Haber yok mesajını gizle
        $listContainer.hide().empty(); // Listeyi gizle ve temizle
        $seeAllLink.hide(); // Tümünü gör linkini gizle

        $.ajax({
            url: bbLatestNewsData.rest_url + bbLatestNewsData.api_endpoint, // örn: /wp-json/bbb-news/v1/latest
            method: 'GET',
            dataType: 'json',
            cache: false, // Prevent caching issues
            data: {
                // API endpoint'i zaten per_page parametresini (varsayılan 6) dikkate almalı.
                // 'per_page': newsLimit
            },
            // beforeSend: function (xhr) {
            //     xhr.setRequestHeader('X-WP-Nonce', bbLatestNewsData.nonce); // Public endpoint için gerekmeyebilir
            // },
            success: function (response) {
                // console.log('News data received:', response); // Debug
                $loadingIndicator.hide(); // Yükleniyor gizle

                if (response.success && response.data && Array.isArray(response.data) && response.data.length > 0) {

                    // Gelen veriyi işle (API zaten 6 tane göndermeli ama JS'de de kontrol edelim)
                    const newsToShow = response.data.slice(0, newsLimit);

                    $.each(newsToShow, function (index, item) {
                        // Gerekli veriler var mı kontrol et
                        const title = item.title || '';
                        const link = item.link || '#';
                        const timeAgo = item.time_ago || '';

                        // Liste öğesini oluştur
                        const listItem = $('<li></li>');

                        // Zaman span'ını oluştur ve ekle
                        const timeSpan = $('<span class="time"></span>').text(timeAgo);

                        // Eğer zaman "dk", "sa" veya "az önce" içeriyorsa özel class ekle
                        if (timeAgo.includes(' dk') || timeAgo.includes(' sa') || timeAgo.includes('az önce')) {
                            timeSpan.addClass('is-recent');
                        }
                        listItem.append(timeSpan);

                        // Link (a) öğesini oluştur ve ekle (jQuery ile güvenli text ekleme)
                        const linkElement = $('<a></a>')
                            .attr('href', link)
                            .attr('target', '_blank')
                            .attr('rel', 'noopener noreferrer')
                            .text(title); // Use .text() for security
                        listItem.append(linkElement);

                        // Oluşturulan li'yi listeye ekle
                        $listContainer.append(listItem);
                    });

                    $listContainer.show(); // Yeni listeyi göster
                    $seeAllLink.show(); // Tümünü gör linkini göster
                } else {
                    // Başarılı ama veri yoksa veya format yanlışsa
                    // console.log('No news data to display.'); // Debug
                    $noNewsIndicator.show(); // "Haber yok" mesajı
                    $seeAllLink.show(); // Tümünü gör linkini yine de göster
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Error fetching news:', textStatus, errorThrown, jqXHR.responseJSON); // Hata detayını logla
                $loadingIndicator.hide(); // Yükleniyor gizle
                $listContainer.empty().hide(); // Listeyi temizle ve gizle
                $noNewsIndicator.hide();

                // Hata mesajını göster
                let errorMessage = bbLatestNewsData.text_error || 'Bir hata oluştu.';
                 if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    errorMessage = jqXHR.responseJSON.message;
                } else if (jqXHR.responseJSON && jqXHR.responseJSON.code) {
                     errorMessage += ` (Kod: ${jqXHR.responseJSON.code})`;
                } else if (errorThrown) {
                    errorMessage += ` (${errorThrown})`;
                }
                $errorIndicator.text(errorMessage).show(); // Hata mesajını göster
                $seeAllLink.show(); // Hata durumunda da tümünü gör linkini göster
            }
        });
    }

    // Sayfa yüklendiğinde haberleri yükle
    loadLatestNews();

    // Belirli aralıklarla haberleri güncelle (isteğe bağlı)
    const updateInterval = parseInt(bbLatestNewsData.update_interval, 10);
    if (updateInterval && updateInterval > 0) {
        setInterval(loadLatestNews, updateInterval);
    }
});