(function ($) {
    "use strict";

    /**
     * Replace a token via a Drupal AJAX form submission.
     *
     * @param token
     * @param callback
     * @param index
     */
    Drupal.tokenReplaceAjax = function (token, callback, index) {
        index = $.isNumeric(index) ? index : 0;
        var form = $('form').get(index) ? $('form').get(index) : $('form').get(0);
        var ajax = Drupal.ajax({
            // Use the first available element on the selected form. The element
            // is insignificant, it is simply used for the purpose of selecting
            // the form.
            element: $('input', form).get(0),
            submit: {
                _triggering_element_name: 'token_replace_ajax'
            },
            url: document.location.pathname + '?ajax_form=1&token_replace_ajax=' + token
        });
        if ($.isFunction(callback)) {
            ajax.commands.data = callback;
        }
        ajax.$form.ajaxSubmit(ajax.options);
    };

})(jQuery, drupalSettings);
