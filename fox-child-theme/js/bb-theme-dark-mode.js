/**
 * Dark Mode Toggle Functionality (for BB Theme Templates)
 * Finds buttons with class 'theme-toggle-button', applies theme on load,
 * handles clicks to toggle theme and save preference to localStorage.
 * Also syncs the theme with the Polymarket embed widget.
 */
document.addEventListener('DOMContentLoaded', function() {

    const themeToggleButtons = document.querySelectorAll('.theme-toggle-button');
    const currentTheme = localStorage.getItem('theme') ? localStorage.getItem('theme') : null;
    const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');

    function applyTheme(theme) {
        const isDarkMode = theme === 'dark';
        document.body.classList.toggle('dark-mode', isDarkMode); // toggle kullanmak daha kısa
        themeToggleButtons.forEach(button => { button.textContent = isDarkMode ? '☀️' : '🌙'; });

        // --- Polymarket Widget Tema Güncelleme ---
        updatePolymarketTheme(theme); // applyTheme içinden çağır
        // --- Polymarket Widget Tema Güncelleme Sonu ---

        // console.log('Applied theme:', theme); // Debugging için
    }

    // --- YENİ: Polymarket Widget Tema Senkronizasyon Fonksiyonu ---
    const polymarketEmbed = document.querySelector('polymarket-market-embed'); // Widget elementini bul

    function updatePolymarketTheme(newTheme) { // Parametre olarak yeni temayı al
        if (!polymarketEmbed) {
            // console.warn('Polymarket embed not found.'); // Debug
            return; // Widget yoksa çık
        }

        const currentEmbedTheme = polymarketEmbed.getAttribute('theme');
        if (currentEmbedTheme !== newTheme) {
            console.log(`Updating Polymarket theme from ${currentEmbedTheme} to: ${newTheme}`); // Debug
            polymarketEmbed.setAttribute('theme', newTheme);
        }
    }
    // --- Polymarket Fonksiyon Sonu ---


    // --- Sayfa Yüklendiğinde Tema Uygulaması ---
    let initialTheme = 'light'; // Varsayılan
    if (currentTheme) {
        initialTheme = currentTheme; // Kayıtlı tercih öncelikli
    } else if (prefersDarkScheme.matches) {
        initialTheme = 'dark'; // Sistem tercihi ikinci öncelik
    }
    applyTheme(initialTheme); // Temayı ve Polymarket'i ayarla
    // console.log('Initial theme set to:', initialTheme); // Debug


    // --- Butonlara Tıklama Olayı Ekleme ---
    themeToggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            let newTheme = document.body.classList.contains('dark-mode') ? 'light' : 'dark';
            localStorage.setItem('theme', newTheme);
            applyTheme(newTheme); // Temayı ve Polymarket'i güncelle
        });
    });

    // --- Sistem Tercihi Değişikliğini Dinleme (İsteğe Bağlı) ---
    prefersDarkScheme.addEventListener('change', (e) => {
        if (!localStorage.getItem('theme')) { // Sadece kullanıcı manuel seçim yapmadıysa
            const newSystemTheme = e.matches ? 'dark' : 'light';
            applyTheme(newSystemTheme); // Temayı ve Polymarket'i güncelle
        }
    });

}); // End DOMContentLoaded