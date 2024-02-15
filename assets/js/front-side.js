

//// JavaScript for handling Displays just the available languages name inside the dropbox
function initializeLanguageDropdown() {
    const dropdown = document.querySelector(".mlomw-dropdown-flags");
    const options = document.querySelector(".mlomw-dropdown-options");

    // Toggle dropdown options display on click.
    if (dropdown) {
        dropdown.addEventListener("click", function() {
            options.style.display = options.style.display === "block" ? "none" : "block";
        });
    }

    // Redirect to the selected language page on click.
    if (options) {
        options.querySelectorAll(".mlomw-dropdown-results").forEach(function(option) {
            option.addEventListener("click", function() {
                window.location.href = this.getAttribute("data-value");
            });
        });
    }
}

// Wait for the DOM to be fully loaded before initializing the dropdown.
document.addEventListener("DOMContentLoaded", initializeLanguageDropdown);


//// JavaScript for handling displays just the available languages flag inside the dropbox
jQuery(document).ready(function($) {
    $(".mlomw-inline-results a").on("click", function(event) {
        event.preventDefault();
        window.location.href = $(this).attr("href");
    });
});


//// Catch information
function handleSiteChange(select) {

    const url = select.value;
    const mainId = select.options[select.selectedIndex].getAttribute("data-pp-id");

    // Check if URL contains a query string, adjust accordingly
    window.location.href = url + (url.includes('?') ? '&' : '?') + 'p=' + mainId;
}
