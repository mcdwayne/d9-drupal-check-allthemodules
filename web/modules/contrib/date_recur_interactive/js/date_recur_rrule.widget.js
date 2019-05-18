/*
 * A JQuery UI Widget to create a RRule compatible inputs for use inside a form
 * Requires: rrule.js (http://jkbr.github.io/rrule/)
 *          underscore.js
 * Original author: Josh Levinger, 2013.
 * Original source: https://github.com/rootio/rootio_web/blob/master/rootio/static/js/plugins/rrule.recurringinput.js
 * Original license: AGPL3.
 * Relicensed by Josh Levinger to GPL v2+.
 * Modified and adapted to Drupal 8 by Frando, 2016.
 */

(function ($, Drupal, Modernizr, RRule) {

  var widget_count = 0;

  RRule.FREQUENCY_ADVERBS = [
    Drupal.t('yearly', {}, {context: 'Date recur: Freq'}),
    Drupal.t('monthly', {}, {context: 'Date recur: Freq'}),
    Drupal.t('weekly', {}, {context: 'Date recur: Freq'}),
    Drupal.t('daily', {}, {context: 'Date recur: Freq'}),
    Drupal.t('hourly', {}, {context: 'Date recur: Freq'}),
    Drupal.t('minutely', {}, {context: 'Date recur: Freq'}),
    Drupal.t('secondly', {}, {context: 'Date recur: Freq'})
  ];
  // add helpful constants to RRule
  RRule.FREQUENCY_NAMES = [
    Drupal.t('year'),
    Drupal.t('month'),
    Drupal.t('week'),
    Drupal.t('day'),
    Drupal.t('hour'),
    Drupal.t('minute'),
    Drupal.t('second')
  ];
  RRule.FREQUENCY_NAMES_PLURAL = [
    Drupal.t('years'),
    Drupal.t('months'),
    Drupal.t('weeks'),
    Drupal.t('days'),
    Drupal.t('hours'),
    Drupal.t('minutes'),
    Drupal.t('seconds')
  ];
  RRule.DAYCODES = ['MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'];
  RRule.DAYNAMES = [
    Drupal.t('Monday'),
    Drupal.t('Tuesday'),
    Drupal.t('Wednesday'),
    Drupal.t('Thursday'),
    Drupal.t('Friday'),
    Drupal.t('Saturday'),
    Drupal.t('Sunday')
  ];

  RRule.MONTHS = [
    Drupal.t('Jan'),
    Drupal.t('Feb'),
    Drupal.t('Mar'),
    Drupal.t('Apr'),
    Drupal.t('May'),
    Drupal.t('Jun'),
    Drupal.t('Jul'),
    Drupal.t('Aug'),
    Drupal.t('Sep'),
    Drupal.t('Oct'),
    Drupal.t('Nov'),
    Drupal.t('Dec')
  ];

  RRule.SETPOS = {
    '1': Drupal.t('first', {}, {context: 'Date recur: Freq'}),
    '2': Drupal.t('second', {}, {context: 'Date recur: Freq'}),
    '3': Drupal.t('third', {}, {context: 'Date recur: Freq'}),
    '4': Drupal.t('forth', {}, {context: 'Date recur: Freq'}),
    '5': Drupal.t('fifth', {}, {context: 'Date recur: Freq'}),
    '-1': Drupal.t('last', {}, {context: 'Date recur: Freq'}),
  };

  // @todo: localize. @see: locale.datepicker.js.

  // note, month num for these values should be one-based, not zero-based
  $.widget("rrule.recurringinput", {
    // default options
    options: {
      rrule: '',
      dtstart: null
    },

    _create: function () {
      this._exinc = {};
      this._exinc.exclude = [];
      this._exinc.include = [];

      //set up inputs
      var tmpl = '';
      //frequency
      tmpl += '<div class="container-inline">';
      tmpl += '<label class="controls">'
        + Drupal.t('Repeat', {}, {context: 'Date recur'})
        + ' ';
      tmpl += '<select name="freq">';
      _.each(RRule.FREQUENCIES, function (element, index) {
        var f = RRule[element];
        tmpl += '<option value=' + f + ' data-freq-base="' + element.toLowerCase() + '">' + RRule.FREQUENCY_ADVERBS[index] + '</option>';
      });
      tmpl += '</select>';
      tmpl += '</label>';


      tmpl += '<label class="controls"> ';
      tmpl += Drupal.t('every', {}, {context: 'Date recur'})
      tmpl += ' <input type="number" value="1" min="1" max="100" name="interval"/>';
      tmpl += '&nbsp;<span id="frequency_name"></span>';
      tmpl += '</label>';
      tmpl += '</div>';

      // repeat options, frequency specific
      // data-freq should be lowercase value from FREQUENCY_NAMES

      //bymonth: weekdays
      tmpl += '<div class="repeat-options controls container-inline" data-freq="monthly">';
      tmpl += '<label for="byweekday-pos">';
      tmpl += Drupal.t('On the', {}, {context: 'Date recur: weekday in month'})
      tmpl += '</label>';
      tmpl += ' <div class="byweekday-pos-container"><span class="byweekday-pos-text"></span>';
      tmpl += ' <div class="byweekday-pos-input">';
      _.each(RRule.SETPOS, function (element, index) {
        tmpl += '<label><input type="checkbox" name="byweekday-pos" value=' + index + '> ' + element + '</label>';
      });
      tmpl += '</div>';
      tmpl += '</div>';
      _.each(RRule.DAYCODES, function (element, index) {
        var d = RRule[element];
        tmpl += '<label class="inline">';
        tmpl += '<input type="checkbox" name="byweekday" value="' + d.weekday + '" />';
        tmpl += RRule.DAYNAMES[index] + '</label>';
      });
      tmpl += '</div>';

      //bymonth: months
      tmpl += '<div class="repeat-options controls container-inline" data-freq="monthly">';
      tmpl += Drupal.t('Only in', {}, {context: 'Date recur: month'})
      tmpl += ' </label>';
      _.each(RRule.MONTHS, function (element, index) {
        tmpl += '<label class="inline">';
        tmpl += '<input type="checkbox" name="bymonth" value="' + (index + 1) + '" />';
        tmpl += element + '</label>';
      });
      tmpl += '</div>';

      //byweekday
      tmpl += '<div class="repeat-options controls container-inline" data-freq="weekly">';
      tmpl += '<label for="byweekday">';
      tmpl += Drupal.t('On', {}, {context: 'Date recur: weekday'})
      tmpl += ' </label>';
      _.each(RRule.DAYCODES, function (element, index) {
        var d = RRule[element];
        tmpl += '<label class="inline">';
        tmpl += '<input type="checkbox" name="byweekday" value="' + d.weekday + '" />';
        tmpl += RRule.DAYNAMES[index] + '</label>';
      });
      tmpl += '</div>';

      //byhour
      tmpl += '<label class="repeat-options" data-freq="hourly">';
      tmpl += Drupal.t('Only at', {}, {context: 'Date recur: time'})
      tmpl += ' <input name="byhour" /> <span>o\'clock</span></label>';

      //byminute
      tmpl += '<label class="repeat-options" data-freq="minutely">';
      tmpl += Drupal.t('Only at', {}, {context: 'Date recur: time'})
      tmpl += ' <input name="byminute" />  <span>minutes<span></label>';

      //bysecond
      tmpl += '<label class="repeat-options" data-freq="secondly">';
      tmpl += Drupal.t('Only at', {}, {context: 'Date recur: time'})
      tmpl += ' <input name="bysecond" /> <span>seconds</span></label>';

      // end repeat options


      // end on
      this.end_input_name = 'end-' + widget_count;
      tmpl += '<div class="end-options controls">';
      tmpl += '<label for="' + this.end_input_name + '">';
      tmpl += Drupal.t('End', {}, {context: 'Date recur'})
      tmpl += ' </label>';

      tmpl += '<label class="inline">';
      tmpl += '<input type="radio" name="' + this.end_input_name + '" value="0" checked="checked" class="end-radio"/> ';
      tmpl += Drupal.t('Never', {}, {context: 'Date recur'})
      tmpl += '</label>';
      tmpl += '<label class="inline">';
      tmpl += '<input type="radio" name="' + this.end_input_name + '" value="1" class="end-radio" /> ';
      tmpl += Drupal.t('After !count occurrences', {'!count': '<input type="number" max="1000" min="1" value="" name="count"/> '}, {context: 'Date recur'})
      tmpl += '</label>';
      tmpl += '<label class="inline">';
      tmpl += '<input type="radio" name="' + this.end_input_name + '" value="2" class="end-radio"> ';
      tmpl += Drupal.t('On date !date', {'!date': '<input type="date" name="until"/>'}, {context: 'Date recur'})
      tmpl += '</label>';

      tmpl += '</div>';

      // exclude/include
      tmpl += '<div class="exlude-include-options controls">';
      tmpl += '<label for="exclude-include">';
      tmpl += Drupal.t('Exclude/include dates', {}, {context: 'Date recur'});
      tmpl += ' </label>';
      tmpl += '<label class="inline">';
      tmpl += '<select name="exinc-type">';
      tmpl += '<option value="exclude">' + Drupal.t('Exclude') + '</option>';
      tmpl += '<option value="include">' + Drupal.t('Include') + '</option>';
      tmpl += '</select>';
      tmpl += '<input type="date" name="exinc-date" />';
      tmpl += '<input type="button" name="exinc-add" value="Add" />';
      tmpl += '</label>';
      tmpl += '<div class="exinc-dates-container"></div>'
      tmpl += '</div>';

      // summary
      tmpl += '<label for="output">';
      tmpl += Drupal.t('Summary', {}, {context: 'Date recur'})
      tmpl += ': <em class="text-output"></em></label>'; // human readable
      tmpl += '<label>' + Drupal.t('RRule') + ':<code class="rrule-output"></code></label>'; // ugly rrule

      //TODO: show next few instances to help user debug

      //render template
      this.element.append(tmpl);

      // attach datepicker if needed.
      if (Modernizr.inputtypes.date !== true) {
        this.element.find("input[type=date]").once('datePicker').each(function() {
          var datepickerSettings = {
            dateFormat: 'yy-mm-dd'
          };
          $(this).datepicker(datepickerSettings);
        })
      }


      //save input references to widget for later use
      this.frequency_select = this.element.find('select[name="freq"]');
      this.interval_input = this.element.find('input[name="interval"]');
      this.end_input = this.element.find('input[type="radio"][name="' + this.end_input_name + '"]');
      this.byweekday_pos_input = this.element.find('.byweekday-pos-input');

      //bind event handlers
      this._on(this.element.find('select, input'), {
        change: this._refresh
      });
      this._on(this.element.find('input[name=exinc-add]'), {
        "click": this._addExcludeInclude
      });

      // Handle byweekday_pos popup.
      this.byweekday_pos_input.hide();
      var widget = this;
      this.element.find('.byweekday-pos-text').click(function(e) {
        if ($(this).hasClass('select-shown')) {
          $(this).removeClass('select-shown');
          widget.byweekday_pos_input.hide();
        }
        else {
          $(this).addClass('select-shown');
          widget.byweekday_pos_input.show();
        }
        e.stopPropagation();
      });
      this.byweekday_pos_input.click(function(e) {
        e.stopPropagation();
      });
      $(document).click(function(e) {
        if (widget.byweekday_pos_input.is(':visible')) {
          widget.byweekday_pos_input.hide();
          widget.element.find('.byweekday-pos-text')
        }
      });

      //set sensible defaults
      this.frequency_select.val(2);
      this.interval_input.val(1);

      // setup default.
      var rrule;
      var rruleOpts = {};
      if (this.options.dtstart) {
        rruleOpts.dtstart = this.options.dtstart;
      }
      if (this.options.rrule.length) {
        rrule = RRule.rrulestr(this.options.rrule, {forceset: true});
        rruleOpts = rrule._rrule[0].options;
      }
      else {
        rruleOpts.freq = RRule.WEEKLY;
        if (this.options.dtstart) {
          rruleOpts.byweekday = [6, 0, 1, 2, 3, 4, 5][this.options.dtstart.getDay()];
        }
      }
      if (typeof rrule == 'undefined') {
        rrule = new RRule.RRuleSet();
        rrule.rrule(new RRule(rruleOpts));
      }
      try {
        this._applyRRule(rrule);
      } catch (_error) {
        e = _error;
        $(".text-output", this.element).append($('<pre class="error"/>').text('=> ' + String(e || null)));
        return;
      }

      widget_count++;

      //refresh
      this._refresh();
    },

    _applyRRule: function (rrule) {
      var opts = rrule._rrule[0].options;
      var freq = RRule.FREQUENCY_NAMES[opts.freq].toLowerCase();

      // split byweekday.
      var byweekdayPos = [];
      if (opts.byweekday == null) {
        opts.byweekday = [];
      }
      if (opts.bynweekday instanceof Array) {
        _.each(opts.bynweekday, function (el) {
          opts.byweekday.push(el[0]);
          byweekdayPos.push(el[1]);
        });
      }
      byweekdayPos.sort(function (a, b) {
        if (a === -1) {
          return 1;
        }
        return a - b;
      });
      opts['byweekday-pos'] = byweekdayPos;


      var $sel = $('[data-freq!=' + freq + ']', this.element);
      var k;
      for (k in opts) {
        var v = opts[k];

        // Try to set the value.
        if (v instanceof Array) {
          $('input[name=' + k + '][type=checkbox]', $sel).val(v);
          if ($('select[name=' + k + ']', $sel).length) {
            $.each(v, function(i, e) {
              $('select[name=' + k + '] option[value=' + e + ']', $sel).prop("selected", true);
            })
          }
        }
        else {
          if ($('input[name=' + k + '][type!=checkbox]', $sel).val(v).length) {
          }
          else if ($('select[name=' + k + ']', $sel).val(v).length) {
          }
        }
      }
      if (opts.count) {
        $('input[name=count]', this.element).val(opts.count);
        $('input[name="' + this.end_input_name + '"][value=1]', this.element).prop('checked', true);
      }
      if (opts.until) {
        $('input[name=until]', this.element)[0].valueAsDate = opts.until;
        $('input[name="' + this.end_input_name + '"][value=2]', this.element).prop('checked', true);
      }

      for (key in rrule._rdate) {
        this._exinc.include.push(rrule._rdate[key]);
      }
      for (key in rrule._exdate) {
        this._exinc.exclude.push(rrule._exdate[key]);
      }
    },

    _createWeekdayPosString: function($boxes) {
      var names = [];
      $boxes.each(function() {
        if ($(this).prop('checked')) {
          names.push(RRule.SETPOS[$(this).attr('value')]);
        }
      });
      if (names.length) {
        return names.join(', ');
      }
      else {
        return Drupal.t('(select a week)');
      }
    },

    _addExcludeInclude: function() {
      var date = this.element.find('input[name=exinc-date]').val();
      if (date) {
        var type = this.element.find('select[name=exinc-type]').val();
        this._exinc[type].push(date);
        this._refresh();
      }
    },

    // called on create and when changing options
    _refresh: function () {
      var that = this;
      //determine selected frequency
      var frequency = this.frequency_select.find("option:selected");
      var frequency_text = frequency.attr('data-freq-base');
      // fill in frequency-name span
      this.element.find('#frequency_name').text(RRule.FREQUENCY_NAMES[frequency.val()]);
      // and pluralize
      if (this.interval_input.val() > 1) {
        this.element.find('#frequency_name').text(RRule.FREQUENCY_NAMES_PLURAL[frequency.val()]);
      }

      this.element.find('.byweekday-pos-text').text(this._createWeekdayPosString(this.element.find('input[name=byweekday-pos]')));

      // display appropriate repeat options
      var repeatOptions = this.element.find('.repeat-options');
      repeatOptions.hide();

      if (frequency !== "") {
        //show options for the selected frequency
        repeatOptions.filter('[data-freq=' + frequency_text + ']').show();

        //and clear descendent fields for the others
        var nonSelectedOptions = repeatOptions.filter('[data-freq!=' + frequency_text + ']');
        nonSelectedOptions.find('input[type=checkbox]:checked').removeAttr('checked');
        nonSelectedOptions.find('input[type!=checkbox]').val('');
        nonSelectedOptions.find('select').val('');
      }

      //reset end
      switch (this.end_input.filter(':checked').val()) {
        case "0":
          //never, clear count and until
          this.end_input.siblings('input[name=count]').val('');
          this.end_input.siblings('input[name=until]').val('');
          break;
        case "1":
          //after, clear until
          this.end_input.siblings('input[name=until]').val('');
          break;
        case "2":
          //date, clear count
          this.end_input.siblings('input[name=count]').val('');
          break;
      }

      // set up exclude/include
      var exincTpl = {};
      var exincStr = '';
      var type, dateKey;
      for (type in this._exinc) {
        exincTpl[type] = [];
        for (dateKey in this._exinc[type]) {
          var date = this._exinc[type][dateKey];
          exincTpl[type].push(
            '<span class="excinc-date-val" data-exinc-date="' + date + '" data-exinc-type="' + type + '">'
            + this._formatDate(this._parseExincDate(date))
            + ' <span class="exinc-date-remove">&#x2718;</span>'
            + '</span>'
          );
        }
      }
      if (exincTpl.exclude.length) {
        exincStr += '<div class="container-inline"><label>' + Drupal.t('Exclude:') + '</label> ' + exincTpl.exclude.join(', ') + '</div>';
      }
      if (exincTpl.include.length) {
        exincStr += '<div class="container-inline"><label>' + Drupal.t('Include:') + '</label> ' + exincTpl.include.join(', ') + '</div>';
      }
      this.element.find('.exinc-dates-container').html(exincStr);
      this.element.find('.exinc-date-remove').click(function() {
        var parent = $(this).parent()[0];
        var type = $(parent).data('exinc-type');
        var date = $(parent).data('exinc-date');
        that._exinc[type] = _.without(that._exinc[type], _.findWhere(that._exinc[type], date));
        that._refresh();
      });

      //determine rrule
      var rrule = this._getRRule();

      if (rrule) {
        $('.rrule-output', this.element).text(rrule.valueOf().join("\n"));
        $('.text-output', this.element).text(this._getHumanReadable(rrule));
        this.element.trigger('rrule-update');
      }
    },

    _getHumanReadable: function(rrule) {
      var that = this;
      var text = '';
      if (rrule._rrule[0]) {
        text = text + rrule._rrule[0].toText();
      }
      if (rrule._rdate.length) {
        text = Drupal.t('!text, and also on: !dates', {
          '!text': text,
          '!dates': _.map(rrule._rdate, function(el) {
            return that._formatDate(el);
          }).join(', ')
        });
      }
      if (rrule._exdate.length) {
        text = Drupal.t('!text, but not on: !dates', {
          '!text': text,
          '!dates': _.map(rrule._exdate, function(el) {
            return that._formatDate(el);
          }).join(', ')
        });
      }
      return text;
    },

    _getFormValues: function ($form) {
      //modified from rrule/tests/demo/demo.js
      var paramObj;
      paramObj = {};

      $.each($form.serializeArray(), function (_, kv) {
        if (paramObj.hasOwnProperty(kv.name)) {
          paramObj[kv.name] = $.makeArray(paramObj[kv.name]);
          return paramObj[kv.name].push(kv.value);
        } else {
          return paramObj[kv.name] = kv.value;
        }
      });
      return paramObj;
    },

    _getRRule: function () {
      //modified from rrule/tests/demo/demo.js
      //ignore 'end', because it's part of the ui but not the spec
      var values = this._getFormValues($(this.element).find('select, input[class!="end-radio"]'));
      var options = {};

      if (_.has(values, 'byweekday-pos') && _.has(values, 'byweekday')) {
        var weekdayPos = values['byweekday-pos'];
      }
      delete values['byweekday-pos'];
      delete values['exinc-type'];
      delete values['exinc-date'];

      var getDay = function (i) {
        var days = [RRule.MO, RRule.TU, RRule.WE, RRule.TH, RRule.FR, RRule.SA, RRule.SU];
        if (typeof weekdayPos !== 'undefined') {
          if (weekdayPos instanceof Array) {
            return _.map(weekdayPos, function (pos) {
              return days[i].nth(pos)
            });
          }else {
            return days[i].nth(weekdayPos);
          }
        }
        return [days[i]];
      };

      var k, v;
      for (k in values) {
        v = values[k];
        if (!v) {
          continue;
        }
        if (_.contains(["dtstart", "until"], k)) {
          v = this._parseDate(v);
        } else if (k === 'byweekday') {
          if (v instanceof Array) {
            v = _.flatten(_.map(v, getDay), true);
          } else {
            v = getDay(v);
          }
        } else if (/^by/.test(k)) {
          if (!(v instanceof Array)) {
            v = _.compact(v.split(/[,\s]+/));
          }
          v = _.map(v, function (n) {
            return parseInt(n, 10);
          });
        } else {
          v = parseInt(v, 10);
        }
        if (k === 'wkst') {
          v = getDay(v);
        }
        if (k === 'interval' && v === 1) {
          continue;
        }
        options[k] = v;
      }

      // get exclude/include dates.
      var dates = {include: [], exclude: []};
      var that = this;
      this.element.find('[data-exinc-date]').each(function() {
        var type = $(this).data('exinc-type');
        dates[type].push(that._parseExincDate($(this).data('exinc-date')));
      });

      var rule, type, key;
      try {
        rule = new RRule.RRuleSet();
        rule.rrule(new RRule(options));
        for (type in dates) {
          for (key in dates[type]) {
            if (type == 'include') {
              rule.rdate(dates[type][key]);
            }
            if (type == 'exclude') {
              rule.exdate(dates[type][key]);
            }
          }
        }
      } catch (_error) {
        var e = _error;
        $(".text-output", this.element).append($('<pre class="error"/>').text('=> ' + String(e || null)));
        return;
      }
      return rule;
    },

    // _setOptions is called with a hash of all options that are changing
    // always refresh when changing options
    _setOptions: function () {
      this._superApply(arguments);
      this._refresh();
    },

    _parseDate: function(dateval) {
      var d = new Date(Date.parse(dateval));
      return new Date(d.getTime() + (d.getTimezoneOffset() * 60 * 1000));
    },

    _parseExincDate: function(dateval) {
      var d = new Date(Date.parse(dateval));
      return d;
    },

    _formatDate: function(dateobj) {
      return Drupal.t('!month/!day/!year', {
        '!day': this._pad(dateobj.getDate()),
        '!month': this._pad(dateobj.getMonth() + 1),
        '!year': this._pad(dateobj.getFullYear())
      }, {context: 'Date recur'});
    },

    _pad: function(n) {
      return (n < 10) ? ("0" + n) : n;
    },

    destroy: function () {
      // remove references
      this.frequency_select.remove();
      this.interval_input.remove();

      // unbind events

      // clear templated html
      this.element.html("");

      $.Widget.prototype.destroy.apply(this);
    }
  });
}(jQuery, Drupal, Modernizr, RRule));
