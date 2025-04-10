(function ($) {

    $.fn.companyTextAutocomplete = function(parameterOptions){
        parameterOptions = parameterOptions || {};

        this.each(function () {

            var input = $(this);
            var options = $.extend({}, input.data('autocomplete-options'), parameterOptions);

            input.autocomplete({
                minLength: options['minimum_input_length'],
                delay: options['quiet_millis'],
                source: function(request, responseCallback) {
                    var params = {
                        'q': input.val(), // search term
                        'page': 1,
                        'item_per_page': options['items_per_page']
                    };
                    var extraParams = options['params'] || {};

                    if('function' === (typeof(extraParams)).toLowerCase()){
                        extraParams = extraParams(params);
                    }

                    params = $.extend({}, extraParams, params);

                    $.getJSON(options['url'], params , function(data){
                        var result = [];
                        $.each(data, function(key, record){
                            result.push({
                                id: record.id,
                                label: record.text,
                            });
                        });

                        responseCallback(result);
                    } );
                }
            });
        });
    };

    $.addAreaInitializerMethod(function (area) {
        area.find('.ui-text-autocomplete-widget').companyTextAutocomplete();
    });

})(jQuery);
