jQuery(document).ready(function($) {
    function updateNavigation(instance, page, maxPages) {
        var container = $('#pna-container-' + instance);
        var useArrows = $('#polaris-posts-container-' + instance).data('use-arrows') === 'true';
        var currentPage = parseInt(page);
        
        if (useArrows) {
            var prevArrow = container.find('.pna-arrow-nav.prev');
            var nextArrow = container.find('.pna-arrow-nav.next');
            
            // Update arrow states
            prevArrow.toggleClass('disabled', currentPage <= 1);
            nextArrow.toggleClass('disabled', currentPage >= maxPages);
        } else {
            // Update numeric pagination
            var pagination = $('.pna-pagination[data-instance="' + instance + '"]');
            pagination.find('.pna-page-link').removeClass('active');
            pagination.find('[data-page="' + currentPage + '"]').addClass('active');
        }
    }

    $(document).on('click', '.pna-page-link, .pna-arrow-nav:not(.disabled)', function(e) {
        e.preventDefault();
        var $this = $(this);
        var instance = $this.data('instance');
        var container = $('#polaris-posts-container-' + instance);
        var maxPages = parseInt(container.data('max-pages'));
        var currentPage = parseInt(container.data('current-page'));
        
        // Calculate the target page
        var targetPage;
        if ($this.hasClass('pna-arrow-nav')) {
            targetPage = $this.hasClass('prev') ? currentPage - 1 : currentPage + 1;
        } else {
            targetPage = parseInt($this.data('page'));
        }

        // Validate target page
        if (targetPage < 1 || targetPage > maxPages) {
            return;
        }

        $.ajax({
            url: polaris_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'polaris_load_paginated_news',
                page: targetPage,
                instance: instance,
                posts_per_page: container.data('posts-per-page'),
                category: container.data('category'),
                exclude: container.data('exclude')
            },
            beforeSend: function() {
                container.fadeTo('fast', 0.5);
            },
            success: function(response) {
                container.html(response).fadeTo('fast', 1);
                
                // Update the current page data attribute
                container.data('current-page', targetPage);
                
                // Update navigation
                updateNavigation(instance, targetPage, maxPages);
            },
            complete: function() {
                // Force a final update after everything is complete
                setTimeout(function() {
                    updateNavigation(instance, targetPage, maxPages);
                }, 50);
            }
        });
    });
});