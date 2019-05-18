(function ($, Drupal, drupalSettings) {
  drupalSettings.MM.summaryFuncs['edit-publishing'] = function (context) {
    var concat_date = function(path) {
      var out = [];
      $(path, context).each(function() {
        var v;
        if (v = $(this).val()) {
          out.push(v);
        }
      });
      return out.join(' ');
    };
    var summary = [];

    var check = $('input[name="status[value]"]', context);
    if (check.length) {
      summary.push(check.is(':checked') ? Drupal.t('published') : Drupal.t('not published'));
    }

    var pub = concat_date('input[name=publish_on],input[name="publish_on[date]"],input[name="publish_on[time]"]');
    if (pub) {
      summary.push(Drupal.t("publish on") + ": " + pub);
    }

    pub = concat_date('input[name=unpublish_on],input[name="unpublish_on[date]"],input[name="unpublish_on[time]"]');
    if (pub) {
      summary.push(Drupal.t("unpublish on") + ": " + pub);
    }

    check = $('input[name=set_change_date]', context);
    if (check.is(':checked')) {
      summary.push(Drupal.t('use "Publish on" date'));
    }

    return summary.join('<br />');
  };
})(jQuery, Drupal, drupalSettings);