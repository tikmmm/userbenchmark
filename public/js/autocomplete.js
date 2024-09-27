$(document).ready(function() {
    $('#search').on('input', function() {
        var query = $(this).val();
        var suggestionsBox = $('#autocomplete-suggestions');

        if (query.length < 2) {
            suggestionsBox.empty().hide();
            return;
        }

        $.ajax({
            url: autocompleteUrl,
            data: { query: query },
            success: function(data) {
                suggestionsBox.empty().show();
                if (data.length > 0) {
                    data.forEach(function(model) {
                        suggestionsBox.append('<div class="autocomplete-suggestion">' + model + '</div>');
                    });
                } else {
                    suggestionsBox.append('<div class="autocomplete-suggestion">No result</div>');
                }
            }
        });
    });

    $(document).on('click', '.autocomplete-suggestion', function() {
    $('#search').val($(this).text());
    $('#autocomplete-suggestions').empty().hide();
});

    $(document).click(function(event) {
    if (!$(event.target).closest('#search').length) {
    $('#autocomplete-suggestions').empty().hide();
}
});
});

