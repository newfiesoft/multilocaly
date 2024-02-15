

//// Autocomplete functionality
jQuery(document).ready(function($) {
    $("#mlomw_linked_post_search").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: myPluginAjax.ajax_url, // Use the localized 'ajax_url'
                method: "POST",
                dataType: "json",
                data: {
                    action: "mlomw_back_fetch_names_and_connect",
                    term: request.term,
                    post_type: $("#post_type").val(),
                    nonce: myPluginAjax.mlomwAutocompleteNonce // Use the localized nonce
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            $("#mlomw_linked_post_id").val(ui.item.value);
        }
    });
});


//// Bind to the pre-submit event instead of click to avoid preventing the main action
jQuery(document).ready(function($) {
    let ajaxSubmitted = false;

    $('form#post').on('submit', function(e) {
        // Prevent double AJAX submission
        if (ajaxSubmitted) return;

        const dataHolder = $("#mlomw_data_attributes");
        if (dataHolder.length && !dataHolder.data('processed')) {
            e.preventDefault(); // Prevent default form submission

            // Disable the publish button to prevent multiple submissions
            $('#publish').prop('disabled', true).val('Processing...');

            const ajaxData = {
                action: "mlomw_back_update_linked_content",
                langId: dataHolder.data("langid"),
                siteId: dataHolder.data("siteid"),
                mainPostId: $("#mlomw_linked_post_id").val(),
                currentPostId: dataHolder.data("currentpostid"),
                postType: dataHolder.data("posttype"),
                nonce: dataHolder.data("nonce"),
            };

            $.ajax({
                url: dataHolder.data("ajaxurl"),
                type: "POST",
                data: ajaxData,
                success: function(response) {
                    if (response.success) {
                        // Mark AJAX as submitted to prevent re-triggering
                        ajaxSubmitted = true;
                        dataHolder.data('processed', true);

                        $('#publish').prop('disabled', false).click();
                    } else {
                        // Handle error
                        console.error("Failed to update content link.");
                        $('#publish').prop('disabled', false).val('Publish');
                    }
                },
                error: function() {
                    console.error("AJAX error occurred.");
                    $('#publish').prop('disabled', false).val('Publish');
                }
            });
        }
    });
});
