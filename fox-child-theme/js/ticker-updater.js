// ----- Ayarlar -----
const apiKey = '5W9YVWMQV330GTL1'; // Alpha Vantage API Anahtarınız (Kendi anahtarınızla değiştirin!)
const tickerUpdateInterval = 5 * 60 * 1000; // Ticker Güncelleme Aralığı (5 Dakika)
const newsUpdateInterval = 10 * 60 * 1000; // Haber Akışı Güncelleme Aralığı (10 Dakika)

// ----- İzlenecek Ticker Sembolleri (Alpha Vantage için) -----
const symbolsToFetch = ['SPY', 'QQQ', '^FTSE', 'USO', 'GLD'];

// ----- DOM Elementleri -----
const newsListElement = document.querySelector('.latest-news-list'); // Haber listesi UL elementi

// =============================================
// == TICKER BAR GÜNCELLEYİCİ (Alpha Vantage) ==
// =============================================

function updateTickerItem(symbol, data) {
    const quote = data['Global Quote'];
    if (!quote || Object.keys(quote).length === 0) {
        // console.warn(`'${symbol}' için API'den geçerli 'Global Quote' verisi alınamadı veya veri boş.`);
        const listItem = document.querySelector(`.ticker-item[data-symbol="${symbol}"]`);
        if (listItem) {
            listItem.querySelector('.ticker-item__value').textContent = 'N/A';
            listItem.querySelector('.ticker-item__change').textContent = '';
        }
        return;
    }
    const listItem = document.querySelector(`.ticker-item[data-symbol="${symbol}"]`);
    if (!listItem) return;

    const valueElement = listItem.querySelector('.ticker-item__value');
    const changeElement = listItem.querySelector('.ticker-item__change');
    const price = quote['05. price'];
    const changePercent = quote['10. change percent'];

    if (price !== undefined && changePercent !== undefined) {
        valueElement.textContent = parseFloat(price).toFixed(2);
        const changeValue = parseFloat(changePercent.replace('%', ''));
        changeElement.textContent = (changeValue > 0 ? '+' : '') + changeValue.toFixed(2) + '%';
        changeElement.classList.remove('ticker-item__change--up', 'ticker-item__change--down');
        if (changeValue > 0) changeElement.classList.add('ticker-item__change--up');
        else if (changeValue < 0) changeElement.classList.add('ticker-item__change--down');
        else changeElement.textContent = changeValue.toFixed(2) + '%'; // Değişim sıfırsa sadece değeri göster
        // console.log(`Ticker: '${symbol}' güncellendi.`);
    } else {
        valueElement.textContent = 'Veri Yok';
        changeElement.textContent = '';
    }
}

function fetchSymbolData(symbol) {
    const apiUrl = `https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol=${symbol}&apikey=${apiKey}`;
    fetch(apiUrl)
        .then(response => response.ok ? response.json() : response.text().then(text => { throw new Error(`API isteği başarısız (${response.status}): ${symbol}. Yanıt: ${text}`) }))
        .then(data => {
            if (data.Note) {
                console.warn(`Alpha Vantage API Notu (${symbol}): ${data.Note}`);
                if (data.Note.includes("API call frequency")) {
                    const listItem = document.querySelector(`.ticker-item[data-symbol="${symbol}"]`);
                    if (listItem) listItem.querySelector('.ticker-item__value').textContent = 'Limit';
                    return;
                }
            }
            updateTickerItem(symbol, data);
        })
        .catch(error => {
            console.error(`Ticker verisi (${symbol}) işlenirken hata:`, error);
            const listItem = document.querySelector(`.ticker-item[data-symbol="${symbol}"]`);
            if (listItem) {
                listItem.querySelector('.ticker-item__value').textContent = 'Hata';
                listItem.querySelector('.ticker-item__change').textContent = '';
            }
        });
}

function fetchAllTickers() {
    // console.log("Ticker verileri güncelleniyor...");
    symbolsToFetch.forEach((symbol, index) => {
        // Alpha Vantage ücretsiz API limitleri (dakikada 5, günde 500) nedeniyle
        // istekler arasına daha uzun bir gecikme koymak daha güvenli olabilir.
        // 5 sembol için 15 saniye aralık = 75 saniye, 5 dakikalık aralık içinde kalır.
        setTimeout(() => {
            fetchSymbolData(symbol);
        }, index * 15000); // 15 saniye ara
    });
}

// =============================================
// == HABER AKIŞI GÜNCELLEYİCİ (WordPress API) ==
// =============================================

// Basit göreceli zaman hesaplama fonksiyonu
function timeAgo(timestamp) {
    if (!timestamp || timestamp === 0) return '-';
    const now = new Date();
    // Timestamp saniye cinsinden geldiği varsayılıyor (PHP time())
    const past = new Date(timestamp * 1000);
    const diffInSeconds = Math.floor((now.getTime() - past.getTime()) / 1000);

    if (diffInSeconds < 0) return '-'; // Gelecekten tarihse
    if (diffInSeconds < 60) return `${diffInSeconds} sn`;

    const diffInMinutes = Math.floor(diffInSeconds / 60);
    if (diffInMinutes < 60) return `${diffInMinutes} dk`;

    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) return `${diffInHours} sa`;

    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays === 1) return `1 gün`;
    if (diffInDays <= 30) return `${diffInDays} gün`; // 30 güne kadar göster

    // 30 günden eskiyse tam tarihi göster (isteğe bağlı)
    // return past.toLocaleDateString('tr-TR'); // Örn: 12.04.2025
    return '-'; // Veya sadece tire
}


function fetchLatestNews() {
    if (!newsListElement) {
        console.warn("Haber listesi elementi (.latest-news-list) bulunamadı.");
        return;
    }

    // WordPress REST API endpoint'i (Sitenize göre ayarlayın)
    // Tam URL kullanmak genellikle daha güvenilirdir.
    // const newsApiUrl = 'https://borsadabibasina.com/wp-json/bbb-news/v1/latest?limit=6';
    const newsApiUrl = '/wp-json/bbb-news/v1/latest?limit=6'; // Eğer aynı domaindeyse bu da çalışabilir
    // Son 6 haberi alalım

    // console.log(`Haberler çekiliyor: ${newsApiUrl}`);

    fetch(newsApiUrl)
        .then(response => {
            // console.log(`Haber API yanıt durumu: ${response.status}`);
            if (!response.ok) {
                return response.text().then(text => {
                     throw new Error(`Haber API isteği başarısız (${response.status}): ${text || 'Boş yanıt'}`);
                 });
            }
            return response.json();
        })
        .then(newsItems => {
            // console.log("Alınan Haberler (JSON):", newsItems);

            if (newsItems && Array.isArray(newsItems) && newsItems.length > 0) {
                let newsHtml = '';
                newsItems.forEach(item => {
                    const title = item.title || 'Başlık Yok';
                    const link = item.link || '#';
                    const timestamp = item.timestamp || 0;

                    // Basit HTML temizleme (daha güçlü bir kütüphane daha iyi olabilir)
                    const safeTitle = title.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                    const relativeTime = timeAgo(timestamp);
                    const safeTime = relativeTime.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                    const safeLink = link.replace(/"/g, "&quot;").replace(/'/g, "&#39;");

                    newsHtml += `<li><span class="time">${safeTime}</span> <a href="${safeLink}" target="_blank" rel="noopener noreferrer">${safeTitle}</a></li>`;
                });
                newsListElement.innerHTML = newsHtml;
                // console.log("Haber akışı başarıyla güncellendi.");
            } else {
                // console.log("API'den haber verisi alınamadı veya gelen veri boş.");
                newsListElement.innerHTML = '<li>Güncel haber bulunamadı.</li>';
            }
        })
        .catch(error => {
            console.error('Haber akışı güncellenirken hata oluştu:', error);
            if (newsListElement) {
                newsListElement.innerHTML = `<li>Haberler yüklenirken bir hata oluştu.</li>`;
            }
        });
}

// ----- Başlangıç ve Zamanlayıcı -----
document.addEventListener('DOMContentLoaded', () => {
    console.log("Sayfa yüklendi, DOM hazır.");

    // === TEMA DEĞİŞTİRİCİ BAŞLANGIÇ ===
    const themeToggleButton = document.getElementById('theme-toggle-button');
    const bodyElement = document.body;

    // Kayıtlı temayı kontrol et ve uygula
    const applySavedTheme = () => {
        const currentTheme = localStorage.getItem('theme');
        if (currentTheme === 'dark') {
            bodyElement.classList.add('dark-mode');
            if (themeToggleButton) themeToggleButton.textContent = '☀️';
        } else {
            bodyElement.classList.remove('dark-mode'); // Varsayılan açık tema
            if (themeToggleButton) themeToggleButton.textContent = '🌙';
        }
        // console.log("Uygulanan tema:", currentTheme || 'light');
    };

    applySavedTheme(); // Sayfa yüklenince temayı uygula

    // Butona tıklama olayını dinle
    if (themeToggleButton) {
        themeToggleButton.addEventListener('click', () => {
            bodyElement.classList.toggle('dark-mode');
            let newTheme = 'light';
            if (bodyElement.classList.contains('dark-mode')) {
                newTheme = 'dark';
                themeToggleButton.textContent = '☀️';
            } else {
                themeToggleButton.textContent = '🌙';
            }
            localStorage.setItem('theme', newTheme);
            console.log("Tema değiştirildi:", newTheme);
        });
    } else {
        console.warn("Tema değiştirme butonu bulunamadı (#theme-toggle-button).");
    }
    // === TEMA DEĞİŞTİRİCİ SON ===


    // === API Veri Yükleme Başlangıç ===

    // API Anahtarı Kontrolü (Ticker için)
     if (!apiKey || apiKey.includes('YOUR_API') || apiKey.length < 10 || apiKey === '5W9YVWMQV330GTL1') { // Örnek anahtarı da kontrol edelim
        console.warn("Geçerli bir Alpha Vantage API Anahtarı girilmemiş veya örnek anahtar kullanılıyor! Lütfen ticker-updater.js dosyasında apiKey değişkenini güncelleyin.");
        const tickerList = document.querySelector('.ticker-list');
         if (tickerList) {
             // Kullanıcıya sadece bilgi verelim, sayfayı bozmayalım
             tickerList.querySelectorAll('.ticker-item__value').forEach(el => el.textContent = 'API?');
             tickerList.querySelectorAll('.ticker-item__change').forEach(el => el.textContent = '');
         }
    } else {
        console.log("Ticker verileri çekiliyor...");
        fetchAllTickers(); // İlk ticker yükleme
        setInterval(fetchAllTickers, tickerUpdateInterval); // Ticker'ları periyodik güncelle
    }

    // Haberleri Çek ve Güncelle
    if (newsListElement) {
        console.log("Haber akışı verileri çekiliyor...");
        fetchLatestNews(); // İlk haber yükleme
        setInterval(fetchLatestNews, newsUpdateInterval); // Haberleri periyodik güncelle
    } else {
        console.warn("Haber listesi elementi bulunamadığı için haberler çekilmeyecek.");
    }
    // === API Veri Yükleme Son ===

}); // DOMContentLoaded Sonu