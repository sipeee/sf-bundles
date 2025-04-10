(function($, global){
    var getDownloadFileNameByUrl = function(url) {
        var urlParts = url.replace(/\?[^\?]*$/).split('/');

        while (0 < urlParts.length) {
            var urlPart = urlParts.pop();
            if (0 < urlPart.length) {
                return urlPart;
            }
       }

       return 'document';
    };

    var isUrlTargeted = function (downloadUrl) {
        return 0 < downloadUrl.match(/[\?&]download=[^&]+/).length;
    };

    var downloadTargettedUrl = function (downloadUrl) {
        downloadUrl = downloadUrl.replace(/([\?&])download=[^&]+((&|$))/, '$1download=1$2');

        var a = $('<a></a>')
            .attr('href', downloadUrl)
            .attr('target', 'blank');
        $('body:first').append(a);
        a.get(0).click();
        a.remove();
    };

    var downloadSimpleUrl = function (downloadUrl) {
        var req = new XMLHttpRequest();
        req.open("GET", downloadUrl, true);
        req.responseType = "blob";
        req.onload = function () {
            //Convert the Byte Data to BLOB object.
            var blob = new Blob([req.response], { type: "application/octet-stream" });

            //Check the Browser type and download the File.
            var isIE = false || !!document.documentMode;
            var downloadFileName = getDownloadFileNameByUrl(downloadUrl);
            if (isIE) {
                global.navigator.msSaveBlob(blob, downloadFileName);
            } else {
                var url = window.URL || window.webkitURL;
                var downloadLink = url.createObjectURL(blob);
                var a = $('<a></a>')
                    .attr('href', downloadLink)
                    .attr('download', downloadFileName);
                $('body:first').append(a);
                a.get(0).click();
                a.remove();
            }
        };
        req.send();
    };

    var downloadUrl = function (downloadUrl) {
        if (isUrlTargeted(downloadUrl)) {
            downloadTargettedUrl(downloadUrl);
        } else {
            downloadSimpleUrl(downloadUrl);
        }
    };

    var applyFancyboxOnPage = function () {
        var galleries = {};
        var contents = [];

        $('.js-fancybox').each(function () {
            var item = $(this);
            if ('undefined' === typeof item.attr('href')) {
                item.off('click');

                return;
            }

            var galleryKey = ('undefined' !== (typeof item.attr('data-gallery')).toLowerCase() && '' !== item.attr('data-gallery'))
                ? item.attr('data-gallery')
                : '';

            if (galleryKey === '') {
                contents.push(this);

                return true;
            }

            if ('undefined' === (typeof galleries[galleryKey]).toLowerCase()) {
                galleries[galleryKey] = [];
            }

            item.attr('data-fancybox', galleryKey);

            galleries[galleryKey].push(this);
        });

        var downloadWindow = $('.document-download-window-container');
        $('.js-fancybox[data-type="inline"]').each(function () {
            var item = $(this);
            if (item.attr('data-src') !== '.document-download-window-container') {
                return true;
            }

            item.attr('data-type', 'html');
            item.attr('data-src', downloadWindow.prop('outerHTML'));
        });

        $('.fancybox-html-content').each(function () {
            var link = $(this);
            link.off('click').on('click', function () {
                $.fancybox.open([{
                    type: 'html',
                    src: $(this).attr('data-content'),
                    opts: {
                        afterLoad: function (e) {
                            e.current.$content.initializeArea();
                        }
                    }
                }]);
            });
        });

        var defaultOptions = {
            'iframe': {
                'preload': false
            },
            'image': {
                'preload': false
            },
            'onInit': function(instance) {
                instance.$refs.toolbar.prepend(
                    $('<button class="fancybox-button fancybox-button--download" title="Download"></button>').append(
                        $('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 211.293 211.293"><g><path d="M176.777,54.027H133.43c-4.143,0-7.5,3.357-7.5,7.5c0,4.142,3.357,7.5,7.5,7.5h35.848v127.266H42.016V69.027h35.844 c4.143,0,7.5-3.357,7.5-7.5c0-4.143-3.357-7.5-7.5-7.5H34.516c-4.142,0-7.5,3.357-7.5,7.5v142.266c0,4.143,3.358,7.5,7.5,7.5 h142.262c4.143,0,7.5-3.357,7.5-7.5V61.527C184.277,57.385,180.92,54.027,176.777,54.027z"/><path d="M82.058,101.468c-2.929-2.929-7.678-2.93-10.606-0.001c-2.93,2.93-2.93,7.678-0.001,10.607l28.889,28.89 c1.407,1.407,3.314,2.197,5.304,2.197c1.989,0,3.896-0.79,5.303-2.196l28.891-28.89c2.93-2.929,2.93-7.678,0.001-10.606 c-2.929-2.929-7.678-2.93-10.606-0.001l-16.088,16.087V7.5c0-4.143-3.357-7.5-7.5-7.5s-7.5,3.357-7.5,7.5v110.054L82.058,101.468z"/></g></svg>')
                    ).on('click', function () {
                        downloadUrl(instance.current.src);
                    })
                );
            },
            'afterLoad': function (instance, current) {
                if (!current.$content.is('.document-download-window-container')) {
                    return;
                }

                var downloadUrl = current.opts.$orig.attr('href');
                current.$content.find('.download-button').attr('href', downloadUrl);
            }
        };

        $.each(galleries, function (key, gallery) {
            $(gallery).fancybox(defaultOptions);
        });
        $.each(contents, function (key, content) {
            $(content).fancybox(defaultOptions);
        });
    };
    var loadCss = function (url, callback) {
        return $.ajax( {
            dataType: "text",
            cache: true,
            url: url,
            success: function (data) {
                $('<style></style>')
                    .appendTo('head:first')
                    .attr({
                        type: 'text/css',
                    })
                    .html(data);

                callback(data);
            }
        } );
    };
    var loadScript = function( url, callback) {
        return jQuery.ajax( {
            dataType: "script",
            cache: true,
            url: url,
            success: callback
        } );
    };
    var loadFancybox = function(callback) {
        var body = $('body:first');
        loadCss( body.attr('data-fancybox-css-url'), function() {
            loadScript( body.attr('data-fancybox-js-url'), callback);
        });
    };

    $.addAreaInitializerMethod(function (area){
        var fancyboxLinks = $('.js-fancybox, [data-fancybox], .fancybox-html-content');

        if (fancyboxLinks.length <= 0) {
            return;
        }

        $('[data-fancybox]').each(function () {
            var item = $(this);
            item.addClass('js-fancybox');
            item.attr('data-gallery', item.attr('data-fancybox'));
            item.removeAttr('data-fancybox');
        });

        if ('undefined' === typeof $.fancybox) {
            loadFancybox(function () {
                applyFancyboxOnPage();
            });
        } else {
            applyFancyboxOnPage();
        }
    });

})(jQuery, window);

