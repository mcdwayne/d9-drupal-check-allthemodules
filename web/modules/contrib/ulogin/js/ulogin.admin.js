(function ($, Drupal, settings) {
    "use strict";
    Drupal.behaviors.ulogin_vtabs_SettingsSummary = {
        attach: function (context, settings) {
            // Make sure this behavior is processed only if drupalSetSummary is defined.
            if (typeof $.fn.drupalSetSummary == 'undefined') {
                return;
            }

            $('#edit-fset-display, #edit-fset-account', context).each(function (index, Element) {
                $(this).drupalSetSummary(function (context) {
                    var vals = [];

                    $('label', context).each(function (index, Element) {
                        var label_for = $(this).attr('for');
                        if ($('#' + label_for).is(':checked')) {
                            vals.push($.trim($(this).text()));
                        }
                    });

                    return vals.join(', ');
                });
            });

            $('#edit-fset-providers', context).drupalSetSummary(function (context) {
                var vals = [];

                $('input[name^="ulogin_providers"]', context).each(function (index, Element) {
                    if ($(this).is(':checked')) {
                        vals.push($.trim($(this).closest('td').next().text()));
                    }
                });

                return vals.join(', ');
            });

            $('#edit-fset-fields', context).drupalSetSummary(function (context) {
                var vals = [];

                $('input[name^="ulogin_fields"]', context).each(function (index, Element) {
                    if ($(this).is(':checked')) {
                        vals.push($.trim($(this).closest('td').next().text()));
                    }
                });

                return vals.join(', ');
            });

            $('fieldset#edit-fset-other', context).drupalSetSummary(function (context) {
                var vals = [];

                var redirect = $('input#edit-ulogin-destination', context).attr('value');
                var label = '<span style="font-weight:bold;">' + $.trim($('label[for="edit-ulogin-destination"]', context).text()) + '</span>';
                if (redirect) {
                    vals.push(label + ': ' + redirect);
                }
                else {
                    vals.push(label + ': ' + 'return to the same page');
                }

                label = '<span style="font-weight:bold;">' + $.trim($('label[for="edit-ulogin-forms"]', context).text()) + '</span>';
                var list = [];
                $('div#edit-ulogin-forms', context).find('label').each(function (index, Element) {
                    var label_for = $(this).attr('for');
                    if ($('#' + label_for).is(':checked')) {
                        list.push($.trim($(this).text()));
                    }
                });
                vals.push(label + ': ' + list.join(', '));

                label = '<span style="font-weight:bold;">' + $.trim($('label[for="edit-ulogin-duplicate-emails"]', context).text()) + '</span>';
                list = [];
                $('div#edit-ulogin-duplicate-emails', context).find('label').each(function (index, Element) {
                    var label_for = $(this).attr('for');
                    if ($('#' + label_for).is(':checked')) {
                        list.push($.trim($(this).text()));
                    }
                });
                vals.push(label + ': ' + list.join(', '));

                return vals.join('<br />');
            });
        }
    };

})(jQuery, Drupal, drupalSettings);
