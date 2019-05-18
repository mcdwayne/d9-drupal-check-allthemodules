(function ($) {
    Drupal.behaviors.ajaxCommentAjaxPagerBehavior = {
        attach: function(context, settings) {
            if(drupalSettings.comment_ajax_pager)       {
                for(i in drupalSettings.comment_ajax_pager){
                    $('div[data-ajax_comment_pager="'+i+'"] .pager a:not(.ajax-processed)').addClass('ajax-processed').each(function(){
                        $(this).attr('onclick', 'comment_ajax_pager(this, \''+i+'\');return false;');
                    });
                    $('div[data-ajax_comment_pager="'+i+'"] .comment_load_more_pager:not(.ajax-load_more)').addClass('ajax-load_more').each(function(){
                        $(this).find('.pager').hide();
                        if($(this).find('.pager__item--next a').length){
                            drupalSettings.comment_ajax_pager[i]['ajax_pager'].load_more = 1;
                            $(this).append('<a href="'+$(this).find('.pager__item--next a').attr('href')+'" onclick="'+$(this).find('.pager__item--next a').attr('onclick')+'">'+$(this).data('text')+'</a>');
                        }
                    });
                }
            }
        }
    };
    window.comment_ajax_pager = function(obj, id){
        var queryString = $(obj).attr('href').substring($(obj).attr('href').indexOf('?') + 1);
        Drupal.ajax({
            url: drupalSettings.comment_ajax_pager[id].ajax_url+'?'+queryString,
            submit: drupalSettings.comment_ajax_pager[id],
            element: obj,
            progress: {type: 'throbber'}
        }).execute();
        return false;
    }
})(jQuery);