

//// This script has use inside /wp-admin/network/site-new.php
jQuery(document).ready(function($) {
    $('#select-add-new-language-dropdown').on('change', function() {
        const selectedLangCode = $(this).val();
        $('#site-address').val(selectedLangCode);
    });
});

jQuery(document).ready(function($) {
    $('#add-site').on('click', function(e) {
        e.preventDefault();
        const languageCode = $('#select-add-new-language-dropdown').val();
        // Append the language code to the form before submitting
        $('<input>').attr({
            type: 'hidden',
            id: 'select_language_code',
            name: 'select_language_code',
            value: languageCode
        }).appendTo('form');

        $('form').submit();
    });
});


//// This script has use inside /wp-admin/network/site-info.php?id=xx
jQuery(document).ready(function($) {
    $('#submit').on('click', function() {
        const customLanguageCode = $('#link-select-language-dropdown').val();
        const blogId = $('input[name="blog_id"]').val();

        $('<input>').attr({
            type: 'hidden',
            name: 'link_select_language_code',
            value: customLanguageCode
        }).appendTo('form');

        $('<input>').attr({
            type: 'hidden',
            name: 'blog_id',
            value: blogId
        }).appendTo('form');

        $('form').submit();
    });
});
