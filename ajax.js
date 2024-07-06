jQuery(document).ready(function($) {
    // jQuery AJAX call to fetch data on page load
    $.ajax({
        url: ajax_object.ajaxurl, // WordPress AJAX URL
        type: 'POST',
        data: {
            action: 'custom_api_sync_ajax' // AJAX action name
        },
        success: function(response) {
            // Handle the response data
            console.log(response);
        },
        error: function(xhr, status, error) {
            // Handle errors
            console.error(xhr.responseText);
        }
    });
});
