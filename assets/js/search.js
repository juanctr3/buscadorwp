jQuery(document).ready(function($) {
    var searchInput = $('#asp-search-input');
    var resultsContainer = $('#asp-results-container');
    var typingTimer;
    var doneTypingInterval = 500; // 0.5 segundos de espera

    searchInput.on('keyup', function() {
        clearTimeout(typingTimer);
        var term = $(this).val();
        
        if (term.length < 3) {
            resultsContainer.hide().html('');
            return;
        }

        typingTimer = setTimeout(function() {
            fetchResults(term);
        }, doneTypingInterval);
    });

    function fetchResults(term) {
        $.ajax({
            url: aspData.ajaxurl,
            type: 'post',
            data: {
                action: 'asp_fetch_results',
                term: term,
                security: aspData.nonce
            },
            success: function(response) {
                if(response.success && response.data.length > 0) {
                    var html = '<ul>';
                    $.each(response.data, function(index, item) {
                        var img = item.image ? '<img src="' + item.image + '" class="asp-thumb">' : '';
                        html += '<li><a href="' + item.link + '">' + img + '<span>' + item.title + '</span></a></li>';
                    });
                    html += '</ul>';
                    resultsContainer.html(html).show();
                } else {
                    resultsContainer.html('<div class="asp-no-results">No se encontraron servicios.</div>').show();
                }
            }
        });
    }

    // Cerrar si clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.asp-search-wrapper').length) {
            resultsContainer.hide();
        }
    });
});
