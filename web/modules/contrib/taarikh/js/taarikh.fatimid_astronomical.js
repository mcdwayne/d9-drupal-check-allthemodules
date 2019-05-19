(function($) {

  "use strict";

  function FatimidAstronomicalCalendar(language) {
    $.calendars.calendars.islamic.call(this, language);
  }

  $.extend(
    FatimidAstronomicalCalendar.prototype,
    $.calendars.calendars.islamic.prototype,
    {
      jdEpoch: 1948083.5,
      // Localisations.
      regionalOptions: {
        "": {
          // The calendar name.
          name: Drupal.t("Hijri"),
          epochs: ["BH", "AH"],
          monthNames: [Drupal.t("Muharram"), Drupal.t("Safar"), Drupal.t("Rabi' al-awwal"), Drupal.t("Rabi' al-aakhar"),
            Drupal.t("Jumada al-awwal"), Drupal.t("Jumada al-aakhar"), Drupal.t("Rajab"), Drupal.t("Sha'aban"),
            Drupal.t("Ramadan"), Drupal.t("Shawwal"), Drupal.t("Zilqad"), Drupal.t("Zilhaj")],
          monthNamesShort: [Drupal.t("Muh"), Drupal.t("Saf"), Drupal.t("Rab1"), Drupal.t("Rab2"), Drupal.t("Jum1"), Drupal.t("Jum2"),
            Drupal.t("Raj"), Drupal.t("Sha'"), Drupal.t("Ram"), Drupal.t("Shaw"), Drupal.t("DhuQ"), Drupal.t("DhuH")],
          dayNames: [Drupal.t("Yawm al-ahad"), Drupal.t("Yawm al-ithnayn"), Drupal.t("Yawm ath-thulaathaa'"),
            Drupal.t("Yawm al-arbi'aa'"), Drupal.t("Yawm al-khamis"), Drupal.t("Yawm al-jum'a"), Drupal.t("Yawm as-sabt")],
          dayNamesShort: [Drupal.t("Sun"), Drupal.t("Mon"), Drupal.t("Tue"), Drupal.t("Wed"), Drupal.t("Thu"), Drupal.t("Fru"), Drupal.t("Sat")],
          dayNamesMin: [Drupal.t("Su"), Drupal.t("Mo"), Drupal.t("Tu"), Drupal.t("We"), Drupal.t("Th"), Drupal.t("Fr"), Drupal.t("Sa")],
          // See format options on BaseCalendar.formatDate.
          dateFormat: "yyyy/mm/dd",
          // The first day of the week, Sun = 0, Mon = 1, ...
          firstDay: 0,
          // True if right-to-left language, false if left-to-right.
          isRTL: false
        }
      },

      leapYear: function(year) {
        var date = this._validate(year, this.minMonth, this.minDay, $.calendars.local.invalidYear);
        return (date.year() * 11 + 11) % 30 < 11;
      },

      toJD: function(year, month, day) {
        var date = this._validate(year, month, day, $.calendars.local.invalidDate);
        year = date.year();
        month = date.month();
        day = date.day();
        year = (year <= 0 ? year + 1 : year);
        var jd = day + Math.ceil(29.5 * (month - 1)) + (year) * 354 +
          Math.floor(((11 * year)) / 30) + this.jdEpoch;
        return jd;
      },

      fromJD: function(jd) {
        var iyear = 10631. / 30.;
        // Results in years 2, 5, 8, 10, 13, 16, 19, 21, 24, 27 & 29 as leap years.
        var shift3 = 0.01 / 30.;

        var z = jd - this.jdEpoch;
        var cyc = Math.floor(z / 10631.);
        z = z - 10631 * cyc;
        var j = Math.floor((z - shift3) / iyear);
        var year = 30 * cyc + j;
        z = z - Math.floor(j * iyear + shift3);
        var month = Math.floor((z + 28.5001) / 29.5);
        if (month == 13) {
          month = 12;
        }
        var day = z - Math.floor(29.5001 * month - 29);

        return this.newDate(year, month, day);
      }
    });

  $.calendars.calendars.fatimid_astronomical = FatimidAstronomicalCalendar;
})(jQuery);
