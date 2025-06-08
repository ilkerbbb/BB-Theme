jQuery(document).ready(function($) {

    // Yeni Öğe Ekleme İşlevi (Ticker ve Sidebar için Ortak)
    $('.dashboard-items-repeater').on('click', '.add-dashboard-item-row', function(e) {
        e.preventDefault();

        var repeater = $(this).closest('.dashboard-items-repeater');
        var tbody = repeater.find('.items-tbody');
        var template = repeater.find('.item-row-template').html(); // Template içeriğini al
        var rowCount = tbody.find('.item-row').length; // Mevcut satır sayısı

        // Template'deki __INDEX__ yer tutucusunu gerçek index ile değiştir
        var newRowHtml = template.replace(/__INDEX__/g, rowCount);

        // Yeni satırı tbody'ye ekle
        tbody.append(newRowHtml);
    });

    // Öğe Kaldırma İşlevi (Ticker ve Sidebar için Ortak)
    $('.dashboard-items-repeater').on('click', '.remove-dashboard-item-row', function(e) {
        e.preventDefault();

        // Emin misiniz diye sor (isteğe bağlı)
        // if (!confirm('Bu öğeyi kaldırmak istediğinizden emin misiniz?')) {
        //     return;
        // }

        // Tıklanan butona en yakın .item-row'u bul ve kaldır
        $(this).closest('.item-row').remove();

        // İsteğe bağlı: Kaldırdıktan sonra index'leri yeniden düzenlemek isterseniz
        // daha karmaşık bir kod gerekir, ancak genellikle form gönderiminde
        // PHP tarafı index'leri doğru işlediği için bu adıma gerek kalmaz.
    });

});