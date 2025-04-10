(function ($) {
    var passwordShowHideToggleEvent = function() {
        var self = $(this);
        var passwordField = self.parents('.show-hide-password-widget:first').find('input:first');
        var toggleIcon = self.find('i.show-hide-toggle-icon');

        if (toggleIcon.hasClass('fa-eye-slash')) {
            toggleIcon.removeClass('fa-eye-slash')
            toggleIcon.addClass('fa-eye')
            passwordField.attr('type', 'text');
        } else {
            toggleIcon.removeClass('fa-eye')
            toggleIcon.addClass('fa-eye-slash')
            passwordField.attr('type', 'password');
        }
    };

    $.addAreaInitializerMethod(function (area) {
        area.find('.show-hide-password-widget .show-hide-password-toggle').on('click', passwordShowHideToggleEvent);
    });
})(jQuery);
