(function ($){

    var createNewItem = function (widget) {
        var index = widget.data('last-index') + 1;

        var htmlContent = widget
            .attr('data-prototype')
            .replace(/\_\_name\_\_/g, index)
        ;
        var content = $(htmlContent);
        widget.find('.company-collection-body:first').append(content);
        content.initializeArea();

        widget.data('last-index', index);

        return content;
    };

    var paintImageToCard = function(pictureItem, file) {
        var imageKeeper = pictureItem.find('.image-keeper');
        if (null === file) {
            imageKeeper.removeAttr('href');
            imageKeeper.removeAttr('style');
            pictureItem.removeClass('selected');
            pictureItem.find('.file-remove-widget').prop('checked', true);

            pictureItem.initializeArea();

            return;
        }

        pictureItem.addClass('selected');
        pictureItem.find('.file-remove-widget').prop('checked', false);
        var galleryContainer = pictureItem.parents('.picture-gallery-image-container:first');
        if (0 < galleryContainer.length) {
            imageKeeper.attr('data-gallery', galleryContainer.attr('id'));
        }

        imageKeeper.attr('href', URL.createObjectURL(file));
        imageKeeper.removeAttr('data-src');
        if (/^image\//.test(file.type)) {
            imageKeeper.css({
                'backgroundImage': 'url(' + URL.createObjectURL(file) + ')'
            });
            imageKeeper.attr('data-type', 'image');
        } else {
            if ('application/pdf' === file.type) {
                imageKeeper.attr('data-type', 'iframe');
            } else {
                imageKeeper.attr('data-type', 'inline');
                imageKeeper.attr('data-src', '.document-download-window-container');
            }
            pictureItem.addClass('icon');
            var fileIcons = pictureItem.data('file-icons');
            var urlBase = pictureItem.attr('data-icon-dir').replace(/\?.*$/, '');
            var icon = ('string' === (typeof (fileIcons[file.type])).toLowerCase())
                ? fileIcons[file.type]
                : 'unknown'
            ;

            imageKeeper.css({
                'backgroundImage': 'url(' + urlBase + '/' + icon + '.png)'
            });
        }

        pictureItem.initializeArea();
    };
    var isFileValid = function(pictureItem, file) {
        var types = pictureItem.data('accepted-mime-types');

        return 0 <= $.inArray(file.type, types);
    };

    var handleImageUploadWidget = function(widget) {
        widget.find('input[type="file"]').on('change', function(){
            var file = this.files[0];

            if (null !== file && !isFileValid(widget, file)) {
                this.files[0] = null;
            }

            paintImageToCard(widget, file);
        });

        widget.find('.title input[type="text"]').on('change', function(){
            var textInput = $(this);
            var imageKeeper = widget.find('.image-keeper');

            if ('' !== textInput.val()) {
                imageKeeper.attr('data-caption', textInput.val());
            } else {
                imageKeeper.removeAttr('data-caption');
            }

            widget.initializeArea();
        });
    };

    var initDeleteButtonOfWidget = function(deleteImageButton) {
        var standalone = deleteImageButton.parents('.picture-file-upload-widget:first').data('standalone');
        if (standalone) {
            deleteImageButton.on('click', function () {
                var button = $(this);
                var widget = button.parents('.picture-file-upload-widget:first');
                paintImageToCard(widget, null);
            });
        } else {
            deleteImageButton.on('click', function () {
                var button = $(this);
                $('body > div.tooltip').remove();
                button.parents('.picture-file-upload-widget:first').parent().remove();
            });
        }
    };

    var initImageUploadWidget = function(widgets){
        widgets.each(function () {
            handleImageUploadWidget($(this));
        });
    };

    var initMultiPictureUploadWidget = function(multiUploadButtons) {
        multiUploadButtons.on('change', function () {
            var input = $(this);
            var multiFileContainer = input.parents('[data-prototype]:first');
            var galleryContainer = input.parents('.picture-gallery-widget').find('.picture-gallery-image-container');

            $.each(this.files, function(index, file){
                var cardItem = createNewItem(galleryContainer);

                var pictureItem = (cardItem.hasClass('picture-file-upload-widget:first'))
                    ? cardItem
                    : cardItem.find('.picture-file-upload-widget:first')
                ;

                if (isFileValid(pictureItem, file)) {
                    paintImageToCard(pictureItem, file);

                    cardItem.initializeArea();
                }else{
                    cardItem.remove();
                }
            });

            createNewItem(multiFileContainer);
        });

    };

    var initMultiPictureUploadButton = function (buttons) {
        buttons.each(function (){
            var uploadButton = $(this);
            if (uploadButton.hasClass('initialized')) {
                return true;
            }

            var offsetInput = uploadButton.find(':input:hidden:first');
            var lastIndex = uploadButton.parents('.picture-gallery-widget').find('.picture-gallery-image-container').data('last-index');
            offsetInput.val(lastIndex);

            uploadButton.addClass('initialized');
        });
    };

    var initMultiFileLastOffset = function (hiddenInputs) {
        hiddenInputs.each(function (){
            var hiddenInput = $(this);

            if ('undefined' === (typeof hiddenInput.attr('rel')).toLowerCase()) {
                return true;
            }

            hiddenInput.val(hiddenInput.attr('rel'));
            hiddenInput.removeAttr('rel');
        });
    };

    $.addAreaInitializerMethod(function (area){
        initImageUploadWidget(area.find('.picture-file-upload-widget'));

        initDeleteButtonOfWidget(area.find('.delete-image-button'));

        initMultiPictureUploadWidget(area.find('.multi-picture-upload-widget'));

        initMultiPictureUploadButton(area.find('.picture-gallery-file-upload-button'));

        initMultiFileLastOffset(area.find('.multi-file-last-offset'));
    });
})(jQuery);