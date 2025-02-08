// Debug check for script loading
console.log('Solo AI Alt Text: Script file loaded');

jQuery(document).ready(function($) {
    // Check if our object exists
    if (typeof solo_ai_alt_text_obj === 'undefined') {
        console.error('Solo AI Alt Text: Required settings object is not defined');
        return;
    }

    // Debug API key validation
    $.ajax({
        url: solo_ai_alt_text_obj.ajax_url,
        type: 'POST',
        data: {
            action: 'get_api_key_debug_info',
            nonce: solo_ai_alt_text_obj.nonce
        },
        success: function(response) {
            if (response.success && response.data) {
                console.log('Solo AI Alt Text: API Key Validation Debug Info:', response.data);
            }
        }
    });

    console.log('Solo AI Alt Text: jQuery ready');
    console.log('Solo AI Alt Text: Initial settings:', solo_ai_alt_text_obj);

    // Debug: Log all analyze buttons found
    console.log('Solo AI Alt Text: Found analyze buttons:', $('.analyze-button').length);
    console.log('Solo AI Alt Text: Found alt text inputs:', $('.solo-ai-alt-text-input').length);

    // Handle alt text input changes with debounce
    let saveTimeout;
    $(document).on('input', '.solo-ai-alt-text-input', function() {
        const $input = $(this);
        const imageId = $input.data('image-id');
        const altText = $input.val();

        console.log('Solo AI Alt Text: Input changed - ', {
            imageId,
            altText,
            element: $input[0],
            hasImageId: typeof imageId !== 'undefined',
            inputClass: $input.attr('class'),
            dataAttributes: $input.data()
        });

        // Clear any pending save
        clearTimeout(saveTimeout);

        // Set a new timeout to save after typing stops
        saveTimeout = setTimeout(() => {
            console.log('Solo AI Alt Text: Saving alt text - ', {
                imageId,
                altText,
                ajaxUrl: solo_ai_alt_text_obj?.ajax_url,
                hasNonce: !!solo_ai_alt_text_obj?.nonce
            });
            saveAltText($input, imageId, altText);
        }, 1000); // Wait 1 second after typing stops before saving
    });

    // Handle analyze button clicks
    $(document).on('click', '.analyze-button', function(e) {
        e.preventDefault();
        console.log('Solo AI Alt Text: Analyze button clicked - raw event', e);

        const $button = $(this);
        console.log('Solo AI Alt Text: Button element:', $button[0]);
        console.log('Solo AI Alt Text: Button data attributes:', $button.data());

        const imageId = $button.data('image-id');
        const imageUrl = $button.data('image-url');

        console.log('Solo AI Alt Text: Analyze button clicked', {
            imageId,
            imageUrl,
            buttonElement: $button[0],
            hasImageId: typeof imageId !== 'undefined',
            hasImageUrl: typeof imageUrl !== 'undefined',
            settings: solo_ai_alt_text_obj,
            apiKeyStatus: solo_ai_alt_text_obj?.has_api_key ? 'Present' : 'Missing',
            ajaxUrl: solo_ai_alt_text_obj?.ajax_url,
            hasNonce: !!solo_ai_alt_text_obj?.nonce
        });

        if (!imageId || !imageUrl) {
            console.error('Solo AI Alt Text: Missing image data', {
                imageId,
                imageUrl,
                buttonData: $button.data()
            });
            alert('Error: Missing image data');
            return;
        }

        if (!solo_ai_alt_text_obj?.has_api_key) {
            console.error('Solo AI Alt Text: Missing API key', {
                settings: solo_ai_alt_text_obj
            });
            alert(solo_ai_alt_text_obj?.i18n?.no_api_key || 'Please configure your API key');
            return;
        }

        analyzeImage($button, imageId, imageUrl);
    });

    function saveAltText($input, imageId, altText) {
        console.log('Solo AI Alt Text: Starting save request', {
            imageId,
            altText,
            ajaxUrl: solo_ai_alt_text_obj?.ajax_url,
            hasNonce: !!solo_ai_alt_text_obj?.nonce,
            inputElement: $input[0]
        });

        // Show saving indicator
        $input.css('opacity', '0.6');

        $.ajax({
            url: solo_ai_alt_text_obj.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'save_alt_text',
                nonce: solo_ai_alt_text_obj.nonce,
                image_id: imageId,
                alt_text: altText
            },
            success: function(response) {
                console.log('Solo AI Alt Text: Save response', {
                    response,
                    success: response?.success,
                    message: response?.data?.message
                });
                
                if (response.success) {
                    // Briefly show success state
                    $input.css('border-color', '#46b450');
                    setTimeout(() => {
                        $input.css('border-color', '');
                    }, 1000);
                } else {
                    // Show error state
                    console.error('Solo AI Alt Text: Save failed', response);
                    $input.css('border-color', '#dc3232');
                    alert(response.data?.message || 'Error saving alt text');
                    setTimeout(() => {
                        $input.css('border-color', '');
                    }, 1000);
                }
            },
            error: function(xhr, status, error) {
                console.error('Solo AI Alt Text: Save error', {
                    xhr,
                    status,
                    error,
                    responseText: xhr.responseText
                });
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
        console.log('Solo AI Alt Text: Starting analysis', {
            imageId,
            imageUrl,
            ajaxUrl: solo_ai_alt_text_obj.ajax_url,
            settings: solo_ai_alt_text_obj,
            buttonElement: $button[0]
        });

        const $input = $button.closest('tr').find('.solo-ai-alt-text-input, input[type="text"]');
        console.log('Solo AI Alt Text: Found input element:', $input[0]);
        
        const originalText = $button.text();

        $button.prop('disabled', true)
               .text(solo_ai_alt_text_obj?.i18n?.analyzing || 'Analyzing...')
               .addClass('updating-message');

        $.ajax({
            url: solo_ai_alt_text_obj.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'analyze_image',
                nonce: solo_ai_alt_text_obj.nonce,
                image_id: imageId,
                image_url: imageUrl
            },
            success: function(response) {
                console.group('Solo AI Alt Text: Analysis Response');
                console.log('Response:', response);
                
                if (response.success) {
                    if (response.data.debug_info) {
                        console.group('Debug Steps:');
                        response.data.debug_info.forEach(step => {
                            if (step.includes('[ERROR]')) {
                                console.error(step);
                            } else {
                                console.log(step);
                            }
                        });
                        console.groupEnd();
                    }
                    
                    // Update the input field with the new alt text
                    if ($input.length) {
                        $input.val(response.data.alt_text);
                        console.log('Solo AI Alt Text: Updated input field with new alt text');
                    } else {
                        console.error('Solo AI Alt Text: Could not find input field to update');
                    }
                    
                    $button.removeClass('updating-message')
                           .addClass('updated')
                           .text(solo_ai_alt_text_obj?.i18n?.analyzed || 'Done!');
                    
                    // Save the alt text
                    if ($input.length) {
                        saveAltText($input, imageId, response.data.alt_text);
                    }
                } else {
                    console.error('Analysis failed:', response.data?.message);
                    if (response.data?.debug_info) {
                        console.group('Error Debug Info:');
                        response.data.debug_info.forEach(step => console.error(step));
                        console.groupEnd();
                    }
                    
                    $button.removeClass('updating-message')
                           .addClass('error')
                           .text(solo_ai_alt_text_obj?.i18n?.error || 'Error');
                    alert(response.data?.message || 'Error analyzing image');
                }
                console.groupEnd();
            },
            error: function(xhr, status, error) {
                console.group('Solo AI Alt Text: AJAX Error');
                console.error('Status:', status);
                console.error('Error:', error);
                
                let errorMessage = 'Error analyzing image. Please try again.';
                let debugInfo = [];

                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.data?.message) {
                        errorMessage = response.data.message;
                    }
                    if (response.data?.debug_info) {
                        debugInfo = response.data.debug_info;
                    }
                } catch (e) {
                    console.error('Failed to parse response:', xhr.responseText);
                    // Try to extract error from HTML response
                    const htmlMatch = xhr.responseText.match(/<p>(.*?)<\/p>/);
                    if (htmlMatch) {
                        errorMessage = htmlMatch[1].replace(/<[^>]+>/g, '');
                    }
                }

                if (debugInfo.length > 0) {
                    console.group('Debug Information:');
                    debugInfo.forEach(step => {
                        if (step.includes('[ERROR]')) {
                            console.error(step);
                        } else {
                            console.log(step);
                        }
                    });
                    console.groupEnd();
                }

                console.groupEnd();
                
                $button.removeClass('updating-message')
                       .addClass('error')
                       .text(solo_ai_alt_text_obj?.i18n?.error || 'Error');
                alert(errorMessage);
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

    // Handle bulk action
    $('#doaction, #doaction2').on('click', function() {
        const action = $(this).prev('select').val();
        if (action === 'analyze') {
            const selectedImages = $('input[name="images[]"]:checked');
            if (selectedImages.length === 0) {
                alert('Please select at least one image to analyze.');
                return;
            }

            // Process images sequentially
            let processed = 0;
            const total = selectedImages.length;

            function processNext() {
                if (processed >= total) {
                    return;
                }

                const $checkbox = $(selectedImages[processed]);
                const imageId = $checkbox.val();
                const imageUrl = $checkbox.data('image-url');
                const $button = $(`button[data-image-id="${imageId}"]`);

                // Trigger the analyze button click
                $button.trigger('click');

                // Wait for the analysis to complete before processing the next image
                const checkInterval = setInterval(function() {
                    if (!$button.prop('disabled')) {
                        clearInterval(checkInterval);
                        processed++;
                        processNext();
                    }
                }, 500);
            }

            processNext();
        }
    });

    // Function to log debug info
    function logDebugInfo(response) {
        console.group('Smart Alt Text - Debug Information');
        if (response.debug_info && response.debug_info.steps) {
            response.debug_info.steps.forEach(function(step) {
                if (step.includes('[ERROR]')) {
                    console.error(step);
                } else {
                    console.log(step);
                }
            });
        }
        console.groupEnd();
    }

    // Modify your existing AJAX success/error handlers
    function handleAjaxSuccess(response) {
        if (response.success) {
            console.log('Smart Alt Text: Success Response', response);
            logDebugInfo(response.data);
            // ... rest of your success handling code ...
        } else {
            console.error('Smart Alt Text: Error Response', response);
            logDebugInfo(response.data);
            // ... rest of your error handling code ...
        }
    }

    function handleAjaxError(xhr, status, error) {
        console.error('Smart Alt Text: AJAX Error', {
            status: status,
            error: error,
            response: xhr.responseText
        });
        // ... rest of your error handling code ...
    }
}); 