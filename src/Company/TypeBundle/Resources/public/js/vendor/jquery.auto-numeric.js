(function ($) {
    $.fn.autoNumeric = function () {
        if (arguments.length === 0 || arguments.length === 1 && 'object' === typeof arguments[0]) {
            var options = 0 < arguments.length ? arguments[0] : {};

            return this.each(function () {
                var autoNumericInstance = new AutoNumeric(this, options);
                $(this).data('auto-numeric-instance', autoNumericInstance);
            });
        } else {
            var result = null;
            for (var i = 0; i < this.length; ++i) {
                var autoNumericInstance = $(this.get(i)).data('auto-numeric-instance');
                if ('undefined' !== typeof autoNumericInstance) {
                    var methodName = arguments[0];
                    result = autoNumericInstance[methodName].apply(autoNumericInstance, Array.prototype.slice.call(arguments, 1));
                }
            }

            return result;
        }
    };
})(jQuery);