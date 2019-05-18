/**
 * @file
 * JavaScript for annotations module.
 */

(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.annotations = {
        attach: function attach(context) {
            var fnDatas = {};
            var FootnotesObj = $('<ul></ul>');
            FootnotesObj.addClass("footnotes");
            if ($('.annotations-footnote').length) {
                $('.annotations-footnote').each(function (index, obj) {
                    var id = $(this).attr('id');
                    fnDatas['fn-' + id] = {
                        'id': id,
                    };
                    $(this).addClass('footnote-' + id);
                });
            }
            $.ajax({
                url: Drupal.url('annotations-footnotes'),
                type: 'POST',
                data: { 'fnDatas': fnDatas},
                dataType: 'json',
                success: function (data) {
                    console.log(data);
                    var fnCount = 0;
                    $.each(data, function (key, value) {
                        fnCount++;
                        var FootnotesObjItem = $('<li id="footnote-item-' + value.id + '"></li>');
                        var FootnotesObjItemLabel = $('<span></span>');
                        FootnotesObjItemLabel.addClass("footnote-label");
                        FootnotesObjItemLabel.attr('id', 'footnote-ref-' + value.id);
                        FootnotesObjItemLabel.text(fnCount + '. ');
                        FootnotesObjItem.append(FootnotesObjItemLabel);
                        FootnotesObjItem.append(value.description);
                        FootnotesObj.append(FootnotesObjItem);

                        $('.footnote-' + value.id).html('<sup><a href="#footnote-ref-' + value.id + '">' + fnCount + '</a></sup>');

                    });
                    $('.block-annotations-footnotes .content').html(FootnotesObj);

                }
            });
        }
    };

})(jQuery, Drupal, drupalSettings);