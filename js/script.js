jQuery(document).ready(function($) {
    const modal = $('#abdg-modal');
    const closeBtn = $('.abdg-close');
    const generateBtn = $('#generate-description');
    const useDescriptionBtn = $('#use-description');
    const businessDescriptionField = $('#business_description');
    const aiResult = $('#ai-result');
    
    // Handle generate button click
    generateBtn.on('click', function() {
        const businessName = $('#business_name').val();
        const businessType = $('#business_type').val();
        const briefDescription = $('#brief_description').val();
        
        if (!businessName || !businessType) {
            alert('Silakan isi nama dan jenis bisnis terlebih dahulu');
            return;
        }
        
        generateBtn.prop('disabled', true);
        generateBtn.text('Generating...');
        
        $.ajax({
            url: abdg_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'abdg_generate_description',
                nonce: abdg_ajax.nonce,
                business_name: businessName,
                business_type: businessType,
                brief_description: briefDescription
            },
            success: function(response) {
                if (response.success) {
                    aiResult.html(response.data);
                    modal.show();
                } else {
                    alert(response.data || 'Terjadi kesalahan saat generate deskripsi');
                }
            },
            error: function() {
                alert('Terjadi kesalahan pada server');
            },
            complete: function() {
                generateBtn.prop('disabled', false);
                generateBtn.text('Generate dengan AI');
            }
        });
    });
    
    // Handle use description button click
    useDescriptionBtn.on('click', function() {
        businessDescriptionField.val(aiResult.text());
        modal.hide();
    });
    
    // Handle modal close
    closeBtn.on('click', function() {
        modal.hide();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(event) {
        if (event.target == modal[0]) {
            modal.hide();
        }
    });
});