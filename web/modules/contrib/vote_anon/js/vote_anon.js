/**
 * Disable vote links
 */
(function ($) {
  jQuery.fn.disableVoteLinks = function(target) {
    if(Number(parseInt(target))) {
      jQuery('div.voting-link-' + target + ' a').attr('href','javascript:void(0)');
      jQuery('div.voting-link-'+ target +' a').removeClass('use-ajax').addClass('disable-ajax disabled');
      jQuery('div.voting-link-' + target).addClass('disabled');
    }
    else {
      jQuery('div.voting-link a').attr('href','javascript:void(0)');
      jQuery('div.voting-link a').removeClass('use-ajax').addClass('disable-ajax disabled');
      jQuery('div.voting-link').addClass('disabled');
    }
  };
}(jQuery));
