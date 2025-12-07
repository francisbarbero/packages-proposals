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

        // Text snippet insertion handler
        $(document).on('click', '.sfpp-populate-insert', function(e) {
            e.preventDefault();

            var $item = $(this);
            var content = $item.data('content');
            var targetId = $item.data('target');

            if (!content || !targetId) {
                return;
            }

            var $target = $('#' + targetId);
            if (!$target.length || !$target.is('textarea')) {
                return;
            }

            // Insert text at cursor position
            insertTextAtCursor($target[0], content);

            // Visual feedback
            $item.addClass('sfpp-populate-inserted');
            setTimeout(function() {
                $item.removeClass('sfpp-populate-inserted');
            }, 300);
        });

        /**
         * Insert text at cursor position in a textarea
         * @param {HTMLTextAreaElement} textarea
         * @param {string} text
         */
        function insertTextAtCursor(textarea, text) {
            var startPos = textarea.selectionStart;
            var endPos = textarea.selectionEnd;
            var scrollTop = textarea.scrollTop;

            // Insert text
            var before = textarea.value.substring(0, startPos);
            var after = textarea.value.substring(endPos, textarea.value.length);
            textarea.value = before + text + after;

            // Restore cursor position after inserted text
            var newPos = startPos + text.length;
            textarea.selectionStart = newPos;
            textarea.selectionEnd = newPos;

            // Restore scroll position
            textarea.scrollTop = scrollTop;

            // Trigger change event for any listeners
            $(textarea).trigger('change');

            // Focus the textarea
            textarea.focus();
        }
    });

})(jQuery);
