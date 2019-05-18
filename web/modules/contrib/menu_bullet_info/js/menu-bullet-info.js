(function($){
    if($(".bullet-info").data("bullet")>0) $( ".bullet-info > a" ).append( "<span class='bullet-info-data'>" + $(".bullet-info").data("bullet") + "</span>" );
})(jQuery);