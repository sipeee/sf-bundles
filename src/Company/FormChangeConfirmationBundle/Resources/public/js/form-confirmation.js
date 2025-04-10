(function ($, global) {

    var findFieldInListByName = function (name, list) {
        for (var i = 0; i < list.length; ++i) {
            if (list[i].name === name) {
                return i;
            }
        }

        return null;
    };

    var serializeForm = function (form) {
        form = $(form);
        var result = [];
        form.find(':input[name]').each(function () {
            var input = $(this);
            result.push({
                'name' : input.attr('name'),
                'value' : input.val(),
            });
        });

        return result;
    };

    var isFormChanged = function (form) {
        form = $(form);
        if (!form.is('form[data-form-values]')) {
            return false;
        }

        var originalFormValues = JSON.parse(form.attr('data-form-values'));
        var currentFormValues = serializeForm(form);

        while (0 < originalFormValues.length) {
            var originalFormValue = originalFormValues[0];
            var position = findFieldInListByName(originalFormValue.name, currentFormValues);
            if (null === position) {
                return true;
            }

            var originalValue = originalFormValue.value;
            var currentValue = currentFormValues[position].value;
            if ('object' === typeof originalValue) {
                originalValue = originalValue.sort().join('|');
            }
            if ('object' === typeof currentValue) {
                currentValue = currentValue.sort().join('|');
            }

            if (originalValue !== currentValue) {
                return true;
            }

            originalFormValues.splice(0, 1);
            currentFormValues.splice(position, 1);
        }

        return 0 < currentFormValues.length;
    };

    $.fn.isFormChanged = function () {
        var forms = this;

        for (var i = 0; i < forms.length; ++i) {
            if (isFormChanged(forms.get(i))) {
                return true;
            }
        }

        return false;
    };

    $.addAreaInitializerMethod(function (area) {
        var forms = area.find('form[data-form-values]');
        forms.submit(function () {
            $(this).removeAttr('data-form-values');
        });
    });

    $(function () {
        $(global).on('beforeunload', function (e) {
            if($('form[data-form-values]').isFormChanged()) {
                return 'Az ürlapot módosította. Ezek a módosítások elvesznek, ha elhagyja az oldalt. Biztos benne?';
            } else {
                return '';
            }
        });
    });

})(jQuery, window);