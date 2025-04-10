(function ($){
    var initializerMethods = [];

    $.addAreaInitializerMethod = function(callback){
        initializerMethods.push(callback);
    };

    $.fn.initializeArea = function(){
        var area = $(this);
        $.each(initializerMethods, function (index, method){
            method(area);
        });
    };

    $(function (){
        $('body:first').initializeArea();
    });
})(jQuery);