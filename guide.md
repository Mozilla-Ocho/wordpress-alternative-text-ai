# Smart Alt Text Plugin Development Guide

Command to run in root to pack into zip:
rm -f smart-alt-text.zip && mkdir -p smart-alt-text && cp -r assets includes templates *.php *.md smart-alt-text/ && zip -r smart-alt-text.zip smart-alt-text && rm -rf smart-alt-text/

## Current Status

### Implemented Features
- ✅ Plugin structure and basic setup
- ✅ Admin menu integration with Settings, Images, and Usage Stats pages
- ✅ X.AI Image Understanding API integration (replaced OpenAI GPT-4 Vision)
- ✅ Settings page with API key management and generation options
- ✅ Image management interface with bulk actions and pagination
- ✅ Manual alt text editing with auto-save
- ✅ Image analysis using X.AI Vision
- ✅ Prefix/suffix text options for generated alt text
- ✅ Usage tracking for images in posts/pages
- ✅ Automatic metadata refresh after alt text updates

### Recent Updates
1. Migrated to X.AI API:
   - Updated API endpoint to use chat completions
   - Implemented proper message structure for image analysis
   - Added detailed error logging for API responses
   - Using grok-2-vision-1212 model for image understanding

2. Fixed Alt Text Persistence:
   - Implemented auto-save functionality with debounce
   - Added metadata refresh after saves
   - Fixed issues with alt text not showing in Media Library
   - Added proper success/error states for saves

3. Enhanced Debugging:
   - Added comprehensive console logging
   - Improved error messages and handling
   - Added debug information section to UI
   - Implemented detailed API response logging

### Current Functionality

1. Manual Alt Text Entry:
   ```javascript
   // Console logs show the flow:
   "Smart Alt Text: Input changed" // Triggers on each keystroke
   "Smart Alt Text: Saving alt text" // After 1 second of no typing
   "Smart Alt Text: Starting save request" // AJAX call starts
   "Smart Alt Text: Save response" // Server response received
   ```

2. Image Analysis:
   ```javascript
   // Flow when clicking Analyze button:
   "Smart Alt Text: Analyze button clicked" // Initial click
   "Smart Alt Text: Starting analysis" // AJAX request to server
   "Smart Alt Text: Analysis response" // X.AI API response
   "Smart Alt Text: Save response" // Alt text saved to database
   ```

3. X.AI Integration:
   ```php
   // Request format:
   [
       'model' => 'grok-2-vision-1212',
       'messages' => [
           [
               'role' => 'user',
               'content' => [
                   ['type' => 'image_url', 'image_url' => ['url' => $url, 'detail' => 'high']],
                   ['type' => 'text', 'text' => 'Generate alt text...']
               ]
           ]
       ]
   ]
   ```

### Technical Learnings

1. Alt Text Persistence:
   - WordPress uses `_wp_attachment_image_alt` meta key
   - Need to call `clean_post_cache()` after updates
   - Changes are immediately visible after cache refresh
   - Auto-save prevents data loss and improves UX

2. X.AI API Integration:
   - Uses chat completions endpoint for vision
   - Requires proper message structure
   - Supports both URL and base64 image inputs
   - Returns response in chat message format

3. JavaScript Best Practices:
   - Use debounce for input changes (1 second delay)
   - Implement proper loading states
   - Show visual feedback for actions
   - Log all important events to console

### Next Steps
1. Enhancements:
   - [ ] Add batch processing capability
   - [ ] Implement progress indicators
   - [ ] Add image preview on hover
   - [ ] Enhance error message display

2. Optimizations:
   - [ ] Cache API responses
   - [ ] Implement rate limiting
   - [ ] Add request queuing
   - [ ] Optimize database queries

3. UI Improvements:
   - [ ] Add undo/redo capability
   - [ ] Implement keyboard shortcuts
   - [ ] Add bulk selection tools
   - [ ] Enhance mobile responsiveness

### Debug Information
Current debug output shows:
- Script loading status
- jQuery availability
- API key presence
- AJAX URL configuration
- All user interactions
- Server responses
- Error states

### Console Log Patterns
```javascript
// Input Changes
"Smart Alt Text: Input changed - {imageId, altText, element}"

// Save Operations
"Smart Alt Text: Saving alt text - {imageId, altText}"
"Smart Alt Text: Starting save request {imageId, altText, ajaxUrl}"
"Smart Alt Text: Save response {success, data}"

// Analysis Operations
"Smart Alt Text: Analyze button clicked {imageId, imageUrl, apiKey}"
"Smart Alt Text: Starting analysis {imageId, imageUrl, ajaxUrl}"
"Smart Alt Text: Analysis response {success, data}"
```

### Known Issues & Challenges
1. AJAX Communication:
   - Initial issues with AJAX requests not firing
   - Challenges with proper error handling
   - Need to ensure proper nonce verification
   - Required better debugging for troubleshooting

2. Data Persistence:
   - Manual alt text changes not saving initially
   - Need to handle failed save attempts gracefully
   - Required proper database update confirmation

3. API Integration:
   - Proper handling of API key encryption/decryption
   - Ensuring secure API key storage
   - Handling API rate limits and errors
   - Formatting image URLs correctly for API

### Development Tips
1. Debugging:
   ```javascript
   // Add console logs for tracking
   console.log('Smart Alt Text: Action triggered', {
       data: relevantData
   });
   ```

2. Error Handling:
   ```php
   try {
       // Your code here
   } catch (\Exception $e) {
       error_log('Smart Alt Text: Error - ' . $e->getMessage());
       wp_send_json_error('User-friendly error message');
   }
   ```

3. AJAX Requests:
   ```javascript
   $.ajax({
       url: smart_alt_text_obj.ajax_url,
       type: 'POST',
       data: {
           action: 'your_action',
           nonce: smart_alt_text_obj.nonce,
           // Additional data
       },
       success: function(response) {
           // Handle success
       },
       error: function(xhr, status, error) {
           // Handle error
       }
   });
   ```

### Testing Checklist
- [ ] Verify script loading
- [ ] Test manual alt text saving
- [ ] Validate analyze button functionality
- [ ] Check error handling
- [ ] Verify API integration
- [ ] Test bulk actions
- [ ] Validate security measures
- [ ] Check user feedback

## Resources
- [WordPress AJAX Documentation](https://developer.wordpress.org/plugins/javascript/ajax/)
- [OpenAI API Documentation](https://platform.openai.com/docs/api-reference)
- [WordPress Plugin Security](https://developer.wordpress.org/plugins/security/)