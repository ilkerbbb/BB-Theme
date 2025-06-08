jQuery(document).ready(function($) {

    console.log('Portfolio Script DOMContentLoaded.'); // Hata ayıklama için

    // Chart.js ve portfolioData objesinin varlığını kontrol et
    if (typeof Chart === 'undefined') {
        console.error('HATA: Chart.js yüklenmemiş! functions.php dosyasını kontrol edin.');
        return; // Chart.js yoksa devam etme
    }
    if (typeof portfolioData === 'undefined') {
        console.error('HATA: portfolioData objesi tanımlanmamış! functions.php içindeki wp_localize_script kontrol edin.');
        // Grafik çizimini engellemek için boş varsayılanlar ata (opsiyonel)
        portfolioData = {
            pieChart: { labels: [], data: [] },
            cumulativeChart: { labels: [], portfolioData: [], xu100Data: [], sp500tlData: [] },
            monthlyChart: { labels: [], portfolioData: [] }
        };
        // return; // Veya burada durdurabiliriz
    } else {
        console.log('Bilgi: Chart.js ve portfolioData yüklendi.');
        console.log('Alınan Veri (portfolioData):', JSON.parse(JSON.stringify(portfolioData))); // Verinin kopyasını logla
    }

    // --- Gerekli HTML Elementlerini Seç ---
    const cumulativeCanvas = document.getElementById('cumulativePerformanceChart'); // Doğru ID
    const pieCanvas = document.getElementById('pieChart');
    const monthlyCanvas = document.getElementById('monthlyPerformanceChart'); // Doğru ID
    const toplamGetiriElement = document.getElementById('toplamGetiri');
    const benchmarkUzeriElement = document.getElementById('benchmarkUzeri');

    // --- 1. Kümülatif Performans Grafiği ---
    if (cumulativeCanvas) {
        try {
            const cumulativeCtx = cumulativeCanvas.getContext('2d');
            // Veriyi DİNAMİK olarak portfolioData'dan al
            const cumulativeLabels = portfolioData.cumulativeChart?.labels || [];
            const cumulativeBBBData = portfolioData.cumulativeChart?.portfolioData || [];
            const cumulativeXU100Data = portfolioData.cumulativeChart?.xu100Data || [];
            const cumulativeSP500TLData = portfolioData.cumulativeChart?.sp500tlData || [];

            console.log('Bilgi: Kümülatif Grafik için veriler alınıyor:', { labels: cumulativeLabels.length, bbb: cumulativeBBBData.length, xu100: cumulativeXU100Data.length, sp500tl: cumulativeSP500TLData.length });

            if (cumulativeLabels.length > 0) { // Sadece veri varsa çiz
                new Chart(cumulativeCtx, {
                    type: 'line',
                    data: {
                        labels: cumulativeLabels, // Dinamik etiketler
                        datasets: [
                          { label: "BBB (Kümülatif)", data: cumulativeBBBData, borderColor: "#f39c12", backgroundColor: "rgba(243, 156, 18, 0.1)", fill: true, tension: 0.3, pointRadius: 2, pointHoverRadius: 4 },
                          { label: "XU100 (Kümülatif)", data: cumulativeXU100Data, borderColor: "#e74c3c", backgroundColor: "rgba(231, 76, 60, 0.1)", fill: true, tension: 0.3, pointRadius: 2, pointHoverRadius: 4 },
                          { label: "SP500 TL (Kümülatif)", data: cumulativeSP500TLData, borderColor: "#3498db", backgroundColor: "rgba(52, 152, 219, 0.1)", fill: true, tension: 0.3, pointRadius: 2, pointHoverRadius: 4 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    // === DEĞİŞİKLİK BURADA ===
                                    label: context => {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            // Değerin sonuna '%' ekle
                                            label += context.parsed.y.toLocaleString('tr-TR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
                                        }
                                        return label;
                                    }
                                    // =========================
                                }
                            },
                            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 20 } }
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                // Y eksenine de % eklemek istersen aşağıdaki callback'i aktif et
                                // ticks: {
                                //     callback: function(value, index, values) {
                                //         return value.toLocaleString('tr-TR') + '%';
                                //     }
                                // }
                            },
                            x: { ticks: { maxRotation: 90, minRotation: 45 } }
                        },
                        hover: { mode: 'index', intersect: false }
                    }
                });
                console.log('Bilgi: Kümülatif Grafik başarıyla oluşturuldu.');

                // --- Genel Durum Değerlerini Güncelle (PHP'den gelen kümülatif % verisine göre) ---
                // Not: Bu kısım artık PHP'den gelen yüzdesel veriyi direkt kullanmalı.
                // Önceki 100'den fark alma mantığına gerek kalmadı.
                if (toplamGetiriElement && benchmarkUzeriElement && cumulativeBBBData.length > 0 && cumulativeXU100Data.length > 0) {
                    const lastBBB_Percent = cumulativeBBBData[cumulativeBBBData.length - 1]; // Son yüzdesel değer
                    const lastXU100_Percent = cumulativeXU100Data[cumulativeXU100Data.length - 1]; // Son yüzdesel değer
                    const difference = lastBBB_Percent - lastXU100_Percent;
                    const differencePrefix = difference >= 0 ? '+' : '';
                    const totalReturnPrefix = lastBBB_Percent >= 0 ? '+' : ''; // Toplam getiri için de işaret ekleyelim

                    toplamGetiriElement.innerText = totalReturnPrefix + lastBBB_Percent.toLocaleString('tr-TR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
                    benchmarkUzeriElement.innerText = differencePrefix + difference.toLocaleString('tr-TR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + "% (vs XU100)";
                    console.log('Bilgi: Genel Durum istatistikleri güncellendi (Yüzdesel).');
                } else {
                    console.warn('Uyarı: Genel Durum istatistikleri güncellenemedi - elementler veya kümülatif veri eksik.');
                }

            } else { // Veri yoksa canvas'a mesaj yaz
                 console.warn('Uyarı: Kümülatif Grafik için geçerli veri bulunamadı.');
                 const ctx = cumulativeCanvas.getContext('2d');
                 if (ctx) { ctx.textAlign = 'center'; ctx.fillStyle = getComputedStyle(document.body).getPropertyValue('--secondary-text-color') || '#555'; ctx.font = "14px sans-serif"; ctx.fillText('Kümülatif performans verisi bulunamadı.', cumulativeCanvas.width / 2, cumulativeCanvas.height / 2); }
            }
        } catch (error) {
            console.error('HATA: Kümülatif Grafik oluşturulurken hata:', error);
        }
    } else {
        console.warn('Uyarı: Kümülatif Grafik canvas elementi (ID: cumulativePerformanceChart) bulunamadı.');
    }

    // --- 2. Pasta Grafik (Portföy Dağılımı) ---
    if (pieCanvas) {
        try {
            const pieCtx = pieCanvas.getContext('2d');
            // Veriyi DİNAMİK olarak portfolioData'dan al
            const pieLabels = portfolioData.pieChart?.labels || [];
            const pieWeights = portfolioData.pieChart?.data || [];

            console.log('Bilgi: Pasta Grafik için veriler alınıyor:', { labels: pieLabels.length, weights: pieWeights.length });

            if (pieWeights.length > 0) {
                new Chart(pieCtx, {
                    type: 'pie',
                    data: {
                        labels: pieLabels, // Dinamik etiketler
                        datasets: [{
                            data: pieWeights, // Dinamik ağırlıklar
                            backgroundColor: [ "#ff9800", "#4caf50", "#2196f3", "#9c27b0", "#f44336", "#00bcd4", "#ffeb3b", "#795548", "#607d8b", "#cddc39", "#ff5722", "#8bc34a", "#03a9f4", "#e91e63", "#ffc107" ],
                            borderColor: 'rgba(255, 255, 255, 0.1)', borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false }, tooltip: { callbacks: { label: context => `${context.label}: ${context.formattedValue}%` } } } // Zaten % vardı
                    }
                });
                console.log('Bilgi: Pasta Grafik başarıyla oluşturuldu.');
            } else { // Veri yoksa canvas'a mesaj yaz
                console.warn('Uyarı: Pasta Grafik için geçerli veri bulunamadı.');
                const ctx = pieCanvas.getContext('2d');
                if (ctx) { ctx.textAlign = 'center'; ctx.fillStyle = getComputedStyle(document.body).getPropertyValue('--secondary-text-color') || '#555'; ctx.font = "14px sans-serif"; ctx.fillText('Portföy dağılım verisi bulunamadı.', pieCanvas.width / 2, pieCanvas.height / 2); }
            }
        } catch(error) {
            console.error('HATA: Pasta Grafik oluşturulurken hata:', error);
        }
    } else {
        console.warn('Uyarı: Pasta Grafik canvas elementi (ID: pieChart) bulunamadı.');
    }

    // --- 3. Bar Chart (Aylık Performans) ---
    if (monthlyCanvas) { // Doğru ID
        try {
            const barCtx = monthlyCanvas.getContext('2d');
            // Veriyi DİNAMİK olarak portfolioData'dan al
            const monthlyLabels = portfolioData.monthlyChart?.labels || [];
            const monthlyData = portfolioData.monthlyChart?.portfolioData || [];

            console.log('Bilgi: Aylık Grafik için veriler alınıyor:', { labels: monthlyLabels.length, data: monthlyData.length });

            if (monthlyLabels.length > 0) { // Veri varsa çiz
                new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: monthlyLabels, // Dinamik etiketler
                        datasets: [{
                            label: "Aylık Getiri (%)",
                            data: monthlyData, // Dinamik aylık portföy getirileri
                            backgroundColor: monthlyData.map(value => value < 0 ? "rgba(231, 76, 60, 0.7)" : "rgba(75, 192, 192, 0.7)"),
                            borderColor: monthlyData.map(value => value < 0 ? "#e74c3c" : "#4bc0c0"),
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false }, tooltip: { callbacks: { label: context => `${context.dataset.label}: ${context.formattedValue}%` } } }, // Zaten % vardı
                        scales: { y: { beginAtZero: true, ticks: { callback: value => value + '%' } }, x: { ticks: { maxRotation: 90, minRotation: 45 } } }
                    }
                });
                console.log('Bilgi: Aylık Bar Grafik başarıyla oluşturuldu.');
            } else { // Veri yoksa canvas'a mesaj yaz
                 console.warn('Uyarı: Aylık Grafik için geçerli veri bulunamadı.');
                 const ctx = monthlyCanvas.getContext('2d');
                 if (ctx) { ctx.textAlign = 'center'; ctx.fillStyle = getComputedStyle(document.body).getPropertyValue('--secondary-text-color') || '#555'; ctx.font = "14px sans-serif"; ctx.fillText('Aylık performans verisi bulunamadı.', monthlyCanvas.width / 2, monthlyCanvas.height / 2); }
            }
        } catch(error) {
            console.error('HATA: Aylık Bar Grafik oluşturulurken hata:', error);
        }
    } else {
        console.warn('Uyarı: Aylık Grafik canvas elementi (ID: monthlyPerformanceChart) bulunamadı.');
    }

}); // End DOMContentLoaded