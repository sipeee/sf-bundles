(function ($) {

    $.addAreaInitializerMethod(function (area) {

        area.find('.company-collection-add .btn').on('click', function () {
            var button = $(this);
            var container = button.parents('.company-collection-widget:first');

            var index = container.data('last-index') + 1;

            var prototype = container.attr('data-prototype');
            prototype = prototype = prototype.replace(/\_\_name\_\_/g, index);
            var newItem = $(prototype);

            container.find('.company-collection-body').append(newItem);

            newItem.initializeArea();

            container.data('last-index', index);

            return false;
        });

        area.find('.company-collection-delete').on('click', function () {
            var deleteButton = $(this);
            var currentRow = deleteButton.parents('.company-collection-row:first');

            var removeCallback = function() {
                deleteButton.trigger('delete');

                currentRow.remove();
            };

            if( 'undefined' !== typeof deleteButton.data('confirmation') ) {
                var confirmation = deleteButton.data('confirmation');
                confirmation.type = confirmation.type || BootstrapDialog.TYPE_WARNING;
                confirmation.title = confirmation.title || 'Warning!';
                confirmation.message = confirmation.message || 'Are you sure to remove that item?';
                BootstrapDialog.confirm({
                    type: confirmation.type,
                    title: confirmation.title,
                    message: confirmation.message,
                    callback: function (agree) {
                        if (agree) {
                            removeCallback();
                        }
                    }
                });
            }else{
                removeCallback();
            }

            return false;
        });

    });

})(jQuery);