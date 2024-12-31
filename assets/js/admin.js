// Debug check for script loading
console.log('Smart Alt Text: Script file loaded');

jQuery(document).ready(function($) {
    console.log('Smart Alt Text: jQuery ready');
    console.log('Smart Alt Text: Initial settings:', smart_alt_text_obj);

    // Handle alt text input changes with debounce
    let saveTimeout;
    $(document).on('input', '.smart-alt-text-input', function() {
        const $input = $(this);
        const imageId = $input.data('image-id');
        const altText = $input.val();

        console.log('Smart Alt Text: Input changed - ', {
            imageId,
            altText,
            element: $input[0]
        });

        // Clear any pending save
        clearTimeout(saveTimeout);

        // Set a new timeout to save after typing stops
        saveTimeout = setTimeout(() => {
            console.log('Smart Alt Text: Saving alt text - ', {
                imageId,
                altText
            });
            saveAltText($input, imageId, altText);
        }, 1000); // Wait 1 second after typing stops before saving
    });

    // Handle analyze button clicks
    $(document).on('click', '.analyze-button', function(e) {
        e.preventDefault();
        const $button = $(this);
        const imageId = $button.data('image-id');
        const imageUrl = $button.data('image-url');

        console.log('Smart Alt Text: Analyze button clicked', {
            imageId,
            imageUrl,
            settings: smart_alt_text_obj,
            apiKeyStatus: smart_alt_text_obj.has_api_key ? 'Present' : 'Missing'
        });

        if (!imageId || !imageUrl) {
            console.error('Smart Alt Text: Missing image data', {
                imageId,
                imageUrl
            });
            alert('Error: Missing image data');
            return;
        }

        if (!smart_alt_text_obj.has_api_key) {
            console.error('Smart Alt Text: Missing API key', {
                settings: smart_alt_text_obj
            });
            alert(smart_alt_text_obj.i18n.no_api_key);
            return;
        }

        analyzeImage($button, imageId, imageUrl);
    });

    function saveAltText($input, imageId, altText) {
        console.log('Smart Alt Text: Starting save request', {
            imageId,
            altText,
            ajaxUrl: smart_alt_text_obj.ajax_url
        });

        // Show saving indicator
        $input.css('opacity', '0.6');

        $.ajax({
            url: smart_alt_text_obj.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'save_alt_text',
                nonce: smart_alt_text_obj.nonce,
                image_id: imageId,
                alt_text: altText
            },
            success: function(response) {
                console.log('Smart Alt Text: Save response', response);
                
                if (response.success) {
                    // Briefly show success state
                    $input.css('border-color', '#46b450');
                    setTimeout(() => {
                        $input.css('border-color', '');
                    }, 1000);
                } else {
                    // Show error state
                    console.error('Smart Alt Text: Save failed', response);
                    $input.css('border-color', '#dc3232');
                    alert(response.data.message || 'Error saving alt text');
                    setTimeout(() => {
                        $input.css('border-color', '');
                    }, 1000);
                }
            },
            error: function(xhr, status, error) {
                console.error('Smart Alt Text: Save error', {xhr, status, error});
                $input.css('border-color', '#dc3232');
                alert('Error saving alt text: ' + error);
                setTimeout(() => {
                    $input.css('border-color', '');
                }, 1000);
            },
            complete: function() {
                $input.css('opacity', '1');
            }
        });
    }

    function analyzeImage($button, imageId, imageUrl) {
        console.log('Smart Alt Text: Starting analysis', {
            imageId,
            imageUrl,
            ajaxUrl: smart_alt_text_obj.ajax_url,
            settings: smart_alt_text_obj
        });

        const $input = $button.closest('tr').find('.smart-alt-text-input');
        const originalText = $button.text();

        $button.prop('disabled', true)
               .text(smart_alt_text_obj.i18n.analyzing)
               .addClass('updating-message');

        $.ajax({
            url: smart_alt_text_obj.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'analyze_image',
                nonce: smart_alt_text_obj.nonce,
                image_id: imageId,
                image_url: imageUrl
            },
            success: function(response) {
                console.log('Smart Alt Text: Analysis response', response);
                
                if (response.success) {
                    $input.val(response.data.alt_text);
                    $button.removeClass('updating-message')
                           .addClass('updated')
                           .text(smart_alt_text_obj.i18n.analyzed);
                    
                    // Also save the alt text
                    saveAltText($input, imageId, response.data.alt_text);
                } else {
                    // Show error state
                    console.error('Smart Alt Text: Analysis failed', response);
                    $button.removeClass('updating-message')
                           .addClass('error')
                           .text(smart_alt_text_obj.i18n.error);
                    alert(response.data.message || 'Error analyzing image');
                }
            },
            error: function(xhr, status, error) {
                console.error('Smart Alt Text: Analysis error', {xhr, status, error});
                $button.removeClass('updating-message')
                       .addClass('error')
                       .text(smart_alt_text_obj.i18n.error);
                alert('Error analyzing image: ' + error);
            },
            complete: function() {
                setTimeout(() => {
                    $button.prop('disabled', false)
                           .removeClass('updating-message updated error')
                           .text(originalText);
                }, 2000);
            }
        });
    }
}); 