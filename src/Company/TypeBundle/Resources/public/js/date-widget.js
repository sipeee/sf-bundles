(function ($) {
    $.addAreaInitializerMethod(function (area) {
        var language = $('html:first').attr('lang') || 'en';
        language = language.substr(language.length - 2, 2).toLowerCase();

        moment.locale(language);
        moment.updateLocale(language, {
            week: { dow: 1 }
        });

        area.find('.js-datepicker').datetimepicker({
            format: "YYYY-MM-DD",
            locale: language,
        });
    });

    // $.addAreaInitializerMethod(function (area) {
    //     area.find('.js-datetimepicker').datepicker($.extend($.datepicker.regional['hu'], {
    //         dateFormat: 'yy-mm-dd hh:mm',
    //         showOtherMonths: true,
    //         selectOtherMonths: true,
    //         changeMonth: true,
    //         changeYear: true
    //     }));
    // });
})(jQuery);

