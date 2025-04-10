(function ($) {

    $.fn.companyEntityAutocomplete = function(parameterOptions){
        parameterOptions = parameterOptions || {};

        this.each(function () {
            var that = $(this);
            var input = $(this).prev();
            var values;

            try {
                values = JSON.parse(input.val());
            } catch (e) {
                values = null;
            }

            if(values === null){
                values = [];
            }

            var options = $.extend({}, $(this).data('autocomplete-options'), parameterOptions);
            var multiple = $(this).attr('multiple');
            multiple = (typeof multiple !== 'undefined') && multiple;

            var language = $('html:first').attr('lang') || 'en';
            language = language.substr(language.length - 2, 2).toLowerCase();

            if (multiple) {
                $.each(values, function(_, record){
                    that.append(
                        $('<option></option>')
                            .attr('value', record.id)
                            .attr('selected', 'selected')
                            .text(record.text)
                    );
                });
            }

            that.select2({
                data: values,
                ajax: {
                    url: options['url'],
                    multiple: multiple,
                    dataType: 'json',
                    delay: options['quiet_millis'],
                    data: function (params) {
                        params = $.extend({
                            term: '',
                            page: 1
                        }, params);

                        var extraParams = options['params'] || {};

                        if('function' === (typeof(extraParams)).toLowerCase()){
                            extraParams = extraParams(params);
                        }

                        return $.extend(extraParams, {
                            q: params.term, // search term
                            page: params.page,
                            item_per_page: options['items_per_page']
                        });
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;

                        if(options['is_creation_allowed'] && 1 === params.page && 0 === data.length){
                            data.push( { id: 'create', text: params.term } );
                        }

                        return {
                            results: data,
                            pagination: {
                                more: options['items_per_page'] <= data.length
                            }
                        };
                    },
                    cache: options['cache']
                },
                placeholder: options['placeholder'],
                allowClear: options['allow_clear'],
                width: options['width'],
                // escapeMarkup: function (markup) { return markup; },
                minimumInputLength: options['minimum_input_length'],
                containerCssClass: options['container_css_class'] + ' ' +  ((that.attr('class') || '').replace('select2-entity-autocomplete-widget', '')),
                dropdownCssClass: options['dropdown_css_class'],
                language: language
            });

            that.on('change', function(){
                var data = that.select2('data');

                input.val(JSON.stringify(data));
            });

            if (multiple) {
                that.select2('data', values);
            }
        });
    };

    $.addAreaInitializerMethod(function (area) {
        area.find('.select2-entity-autocomplete-widget').companyEntityAutocomplete();
    });

})(jQuery);
