(function($){
    var inputChangeEventHandler = function () {
        var input = $(this);
        var hiddenInput = input.next('input[type="hidden"]');
        hiddenInput.val(input.autoNumeric('getNumericString'));
    };

    $.addAreaInitializerMethod(function (area){
        var inputs = area.find('input.auto-numeric-widget');

        inputs.each(function () {
            var input = $(this);
            var hiddenInput = $('<input />')
                .attr('type', 'hidden')
                .attr('name', input.attr('name'));

            var options = input.data('auto-numeric-options');
            input.removeAttr('name');
            input.autoNumeric(options);

            hiddenInput.val(input.autoNumeric('getNumericString'));
            hiddenInput.insertAfter(input);

            input.on('change', inputChangeEventHandler);
        });
    });

})(jQuery);
