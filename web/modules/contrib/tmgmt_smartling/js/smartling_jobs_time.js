(function ($) {

    Drupal.behaviors.smartlingJobsUtcToLocalTime = {
        attach: function (context, settings) {
          var utcTimeString = $('input[name="settings[add_to_job_tab][container][job_info][utc_due_date_hidden]"]', context).val();
          var $date = $('input[name="settings[add_to_job_tab][container][job_info][due_date][date]"]', context);
          var $time = $('input[name="settings[add_to_job_tab][container][job_info][due_date][time]"]', context);

          if (utcTimeString) {
            var utcTime = moment.utc(utcTimeString).toDate();
            var localTime = moment(utcTime);

            $date.val(localTime.format('YYYY-MM-DD'));
            $time.val(localTime.format('HH:mm'));
          }
          else {
            $date.val('');
            $time.val('');
          }

          $('input[name="settings[smartling_users_time_zone]"]', context).val(moment.tz.guess());
        }
    };

})(jQuery);
