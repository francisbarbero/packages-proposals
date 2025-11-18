// public/js/app.js
(function($) {
    'use strict';

    $(document).ready(function() {
        // Simple tab handler for all .sfpp-tabs instances
        $('.sfpp-tabs').each(function() {
            var $tabs = $(this);

            $tabs.on('click', '.sfpp-tabs-nav a[data-tab-target]', function(e) {
                e.preventDefault();

                var $link  = $(this);
                var target = $link.data('tab-target');

                // Activate nav item
                $tabs.find('.sfpp-tabs-nav-item').removeClass('is-active');
                $link.parent().addClass('is-active');

                // Show correct panel
                $tabs.find('.sfpp-tab-panel').removeClass('is-active');
                $tabs.find('.sfpp-tab-panel[data-tab-id="' + target + '"]').addClass('is-active');
            });
        });
    });

})(jQuery);
