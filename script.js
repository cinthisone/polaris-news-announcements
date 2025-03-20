jQuery(document).ready(function($) {
    $(document).on('click', '.pna-page-link', function(e) {
        e.preventDefault();

        var page = $(this).data('page');
        var container = $('#polaris-posts-container');
        var pagination = $('.pna-pagination');

        $.ajax({
            url: polaris_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'polaris_load_paginated_news',
                page: page,
                post_type: container.data('post-type'),
                category: container.data('category'),
                posts_per_page: container.data('posts-per-page'),
            },
            beforeSend: function() {
                container.fadeTo('fast', 0.5);
            },
            success: function(response) {
                container.html(response).fadeTo('fast', 1);
                pagination.find('.pna-page-link').removeClass('active');
                pagination.find('[data-page="' + page + '"]').addClass('active');
            }
        });
    });
});