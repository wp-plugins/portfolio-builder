(function($){

    $(document).ready(function() {

        $('.dlspb-no-js').hide();
        $('.dlspb-js-only').show();

        $('.dlspb a[href="#"]').click(function() {
            return false;
        });

        $('.dlspb a[href=""]').click(function() {
            return false;
        });

        $('#dlspb-tabs').tabs();

        $(".dlspb-screenshots A[rel=dlspb_screenshots]").fancybox({
            'transitionIn' : 'none',
            'transitionOut' : 'none',
            'titleShow' : false
        });

        // UL to SELECT
        $('ul.dlspb-faux-select').each(function() {
            var list = $(this),
                select = $(document.createElement('select')).insertBefore($(this).hide());
            $('>li', this).each(function() {
                var ahref = $(this).children('a'),
                    target = ahref.attr('target'),
                    option = $(document.createElement('option'))
                        .appendTo(select)
                        .val(ahref.attr('href'))
                        .html(ahref.html())
                        .click(function() {
                            if (option.val().length == 0) return;
                            if (target === '_blank') {
                                window.open(ahref.attr('href'));
                            } else {
                                window.location.href = ahref.attr('href');
                            }
                        });
                if (ahref.attr('class') === 'dlspb-selected') option.attr('selected', 'selected');
            });
        });

        // Go to filter on change
        $('.dlspb-filter SELECT').on('change', function() {
            var form = $(this).parent('.dlspb-filter-field').parent('FORM');
            form.attr('action', $(this).val());
            form.submit();
        });

    });

})(jQuery);