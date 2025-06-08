jQuery(document).ready(function($) {

    // --- Varlıklar Repeater ---
    var assetsTbody = $('#assets-tbody');
    var assetTemplateHtml = $('#asset-row-template').html();

    // Başlangıçta hiç varlık yoksa ilk boş satırı ekle
    if (assetsTbody.children('tr.asset-row').length === 0) {
        if (assetTemplateHtml) {
             var firstRowHtml = assetTemplateHtml.replace(/__INDEX__/g, 0);
             assetsTbody.append(firstRowHtml);
        } else { console.error('Asset row template not found!'); }
    }

    // Yeni varlık satırı ekleme
    $('#add-asset-row').on('click', function() {
        if (!assetTemplateHtml) { console.error('Asset row template not found!'); return; }
        var newIndex = assetsTbody.children('tr.asset-row').length;
        var newRowHtml = assetTemplateHtml.replace(/__INDEX__/g, newIndex);
        assetsTbody.append(newRowHtml);
    });

    // Varlık satırı kaldırma
    assetsTbody.on('click', '.remove-asset-row', function() {
        if (assetsTbody.children('tr.asset-row').length > 1) {
            $(this).closest('tr.asset-row').remove();
        } else {
             $(this).closest('tr.asset-row').find('input, select').val('');
             alert('Son varlık satırı silinemez, içini temizleyebilirsiniz.');
        }
    });


    // --- Performans Verileri Repeater ---
    var performanceTbody = $('#performance-tbody');
    var performanceTemplateHtml = $('#performance-row-template').html(); // Performans için de template kullan

     // Başlangıçta hiç performans verisi yoksa ilk boş satırı ekle
     if (performanceTbody.children('tr.performance-row').length === 0) {
         if (performanceTemplateHtml) {
             var firstPerfRowHtml = performanceTemplateHtml.replace(/__INDEX__/g, 0);
             performanceTbody.append(firstPerfRowHtml);
         } else { console.error('Performance row template not found!'); }
     }

    // Yeni performans satırı ekleme (Şablon kullanarak)
    $('#add-performance-row').on('click', function() {
         if (!performanceTemplateHtml) { console.error('Performance row template not found!'); return; }
        var newIndex = performanceTbody.children('tr.performance-row').length;
        var newRowHtml = performanceTemplateHtml.replace(/__INDEX__/g, newIndex);
        performanceTbody.append(newRowHtml);
    });

    // Performans satırı kaldırma
    performanceTbody.on('click', '.remove-performance-row', function() {
        if (performanceTbody.children('tr.performance-row').length > 1) {
            $(this).closest('tr.performance-row').remove();
        } else {
            $(this).closest('tr.performance-row').find('input').val('');
            alert('Son performans satırı silinemez, içini temizleyebilirsiniz.');
        }
    });

});