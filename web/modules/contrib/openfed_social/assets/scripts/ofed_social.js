(function ($, Drupal) {
    Drupal.behaviors.ofedSocial = {
        attach: function attach(context, settings) {

            $('.block--ofed-social a.ofed_social_share_link', context).on('click', function() {
                if ($(this).hasClass('ofed_social_share_link_email')){
                    // Do nothing.
                } else if ($(this).hasClass('ofed_social_share_link_print')){
                    window.print();
                } else {
                    window.open(this.href,'', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=600');
                    return false;
                }
            });
        }
    }
})(jQuery, Drupal);
