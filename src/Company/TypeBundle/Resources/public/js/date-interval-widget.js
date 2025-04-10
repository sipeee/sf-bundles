(function ($) {
    $.fn.dateIntervalWidget = function(){
        this.each(function () {
            var container = $(this);
            var typeInput = container.find('[name$="[type]"]');
            var dateFromInput = container.find('[name$="[dateFrom]"]');
            var dateToInput = container.find('[name$="[dateTo]"]');
            var dateFromDisplayInput = container.find('.date-from-input');
            var dateToDisplayInput = container.find('.date-to-input');

            if(
                typeInput.length <= 0 ||
                dateFromInput.length <= 0 ||
                dateToInput.length <= 0 ||
                dateFromDisplayInput.length <= 0 ||
                dateToDisplayInput.length <= 0
            ){
                return;
            }

            var predefinedValues = typeInput.data('predefined-values');

            dateFromDisplayInput.datetimepicker({
                'format': "YYYY-MM-DD",
                'showClear': ('undefined' === typeof dateFromInput.attr('required')),
            });
            dateToDisplayInput.datetimepicker({
                'format': "YYYY-MM-DD",
                'showClear': ('undefined' === typeof dateToInput.attr('required')),
            });
            var initializeDisplayLabel = function() {
                container.find('a.dropdown-option').each(function () {
                    var key = ('undefined' !== typeof $(this).attr('data-predefined-value'))
                        ? $(this).attr('data-predefined-value')
                        : ''
                    ;

                    var dates = ('' !== key)
                        ? predefinedValues[key]
                        : {dateFrom: '', dateTo: ''}
                    ;

                    if ( dates['dateFrom'] == dateFromInput.val() && dates['dateTo'] == dateToInput.val() ) {
                        found = true;

                        typeInput.val(key);
                        container.find('.react-datepicker-buttonDisplayText').text($(this).text());
                        setValueTitle(
                            ('undefined' !== typeof $(this).attr('data-display-label'))
                                ? $(this).attr('data-display-label')
                                : $(this).text()
                        );

                        return false;
                    }
                });

                if (!found && dateFromInput.val() && dateToInput.val()) {
                    container.find('.react-datepicker-buttonDisplayText').text(dateFromInput.val() + ' - ' + dateToInput.val());
                }
            };

            var setValueTitle = function(title){
                var element = container.find('.react-datepicker-buttonDisplayText');

                if('INPUT' === element.get(0).tagName.toUpperCase()){
                    element.val(title.trim());
                }else{
                    element.text(title);
                }
            };
            var setDetailedValueTitle = function(fromValue, toValue){
                if(!fromValue && !toValue){
                    setValueTitle('');

                    return;
                }

                fromValue = ('' !== fromValue)
                    ? fromValue
                    : '...'
                ;
                toValue = ('' !== toValue)
                    ? toValue
                    : '...'
                ;

                setValueTitle( fromValue + ' - ' + toValue);
            };
            container.find('a.dropdown-option').off('click').on('click', function () {
                var key = ('undefined' !== typeof $(this).attr('data-predefined-value'))
                    ? $(this).attr('data-predefined-value')
                    : ''
                ;

                var dates = ('' !== key)
                    ? predefinedValues[key]
                    : {dateFrom: '', dateTo: ''}
                ;

                typeInput.val(key);
                dateFromInput.val(dates['dateFrom']);
                dateFromDisplayInput.val(dates['dateFrom']);
                dateToInput.val(dates['dateTo']);
                dateToDisplayInput.val(dates['dateTo']);

                setValueTitle(
                    ('undefined' !== typeof $(this).attr('data-display-label'))
                        ? $(this).attr('data-display-label')
                        : $(this).text()
                );
            });

            var widgetIsOpen = false;
            container.find('.react-datepicker-dropdown button.dropdown-toggle, .react-datepicker-dropdown input.dropdown-input').off('click').on('click', function () {
                widgetIsOpen = true;
            });
            container.find('.thin-input-group, .paypal-react-datepicker-submit-li').off('click').on('click', function (e) {
                if(widgetIsOpen){
                    e.stopImmediatePropagation();
                    e.stopPropagation();

                    return false;
                }
            });
            container.find('.paypal-react-datepicker-submit').off('click').on('click', function (e) {
                widgetIsOpen = false;

                dateFromInput.val(dateFromDisplayInput.val());
                dateToInput.val(dateToDisplayInput.val());

                found = false;
                var button = $(this);
                initializeDisplayLabel();

                if(!found){
                    typeInput.val(button.attr('data-predefined-value'));
                    container.find('.react-datepicker-buttonDisplayText').text(dateFromInput.val() + ' - ' + dateToInput.val());
                    setDetailedValueTitle(dateFromInput.val(), dateToInput.val());
                }
            });

            var found = false;
            container.find('a.dropdown-option').each(function () {
                var key = ('undefined' !== typeof $(this).attr('data-predefined-value'))
                    ? $(this).attr('data-predefined-value')
                    : ''
                ;

                if( typeInput.val() === key ){
                    setValueTitle(
                        ('undefined' !== typeof $(this).attr('data-display-label'))
                            ? $(this).attr('data-display-label')
                            : $(this).text()
                    );
                    found = true;

                    return false;
                };
            });

            var button = container.find('.paypal-react-datepicker-submit');
            if(!found && button.attr('data-predefined-value') === typeInput.val()){
                setDetailedValueTitle(dateFromInput.val(), dateToInput.val());
            }

            initializeDisplayLabel();
        });
    };

    $.addAreaInitializerMethod(function (area) {
        area.find('.date-interval-widget').dateIntervalWidget();
    });
})(jQuery, document);