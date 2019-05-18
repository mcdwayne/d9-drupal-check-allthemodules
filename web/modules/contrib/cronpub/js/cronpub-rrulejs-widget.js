(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.icalEventFieldBehavior = {
    attach: function (context) {


      function noLibMessage() {
        $('input.ical-rrule', context).once('icalRrule').each(function (key, item) {
          var rruleField = $(item);
          var container = rruleField.parents('.rrule-js');
          var text = Drupal.t('This field is intended to transfer human readable rules in phrases. However, ' +
              'the requisite JS library is not installed. Follow the help instructions to install the library.');
          var message = $('<div>').attr({
            class: 'no-lib-message'
          }).text(text);
          container.append(message);
        });
      }

      try {
        if (typeof RRule === 'function') {
          // Check first if rrule.js is installed and then if nlp.js is also installed.
          $('input.ical-rrule', context).once('icalRrule').each(function (key, item) {
            var rruleField = $(item);
            var enabled = !$(item).is(':disabled');
            var rruleDesc = rruleField.next('.description');
            var size = rruleField.attr('size');
            var container = rruleField.parents('.rrule-js');
            var validMsg = $('<div>').attr({
              class: 'valid-messages'
            });
            var hideContainer = $('<div>').attr({
              class: 'hidden-js',
              style: 'display: none'
            }).append(rruleField).append(rruleDesc);
            var toggle = $('<a>').attr({
              href: '#',
              class: 'button'
            }).text(Drupal.t('Rule'));
            var humanField = $('<input type="text">').attr({
              size: size,
              class: 'form-text'
            });
            if (!enabled) {
              humanField.attr('disabled', 'disabled')
            }

            var humanExamples = $('<ul>').attr({
              class: 'ical-human-drafts',
              style: 'display:none;'
            });
            var humanExamplesHeader = $('<div><b><a href="#">' + Drupal.t('Expamples/drafts:') + '</a></b></div>');
            var currentYear = new Date().getFullYear();

            if (enabled) {
              var humanReadables = [
                  'Every weekday',
                  'Every 2 weeks on Tuesday',
                  'Every week on Monday, Wednesday',
                  'Every month on the 2nd last Friday for 7 times',
                  'Every 2 months until December 12, ' + currentYear
                ];

              // Select an example as a draft.
              $.each(humanReadables, function (i, text) {
                var draft = $('<span class="rrule-js-draft">')
                  .attr('title', Drupal.t('Select this example as draft.'))
                  .text(text)
                  .on('click touchend', function () {
                    humanField.focus().val(text).change();
                  });
                humanExamples.append($('<li>').append(draft));
              });

              // Toggeling th examples
              humanExamplesHeader.find('a').on('click touchend', function (e) {
                e.preventDefault();
                humanExamples.slideToggle(150);
              });
            } else {
              humanExamplesHeader = '';
              humanExamples = '';
            }


            // Attach new elements to field widget.
            container
              .append(hideContainer)
              .append(validMsg)
              .append(humanField)
              .append(toggle)
              .append(humanExamplesHeader)
              .append(humanExamples);

            // initial setup of field widget.
            var default_val = rruleField.val();
            var rrule = false;
            if (default_val) {
              try {
                // load default value from string.
                rrule = new RRule.fromString(rruleField.val());

                // assign the default value in human readable field.
                humanField.val(rrule.toText());
              }
              catch (err) {
                var message = Drupal.t('The saved value was not a valid RRule string.');
                validMsg.html('<div class="messages messages--warning">' + message + '</div>');
              }
              // Show/hide the rrule string.
              toggle.on('click touchend', function (e) {
                e.preventDefault();
                hideContainer.slideToggle(150);
              });
            }

            if (enabled) {

              // observer for changes in human field.
              humanField.on('keyup change', function () {
                if ($(this).is(':focus')) {
                  var text = $(this).val();
                  try {
                    rrule = new RRule.fromText(text);
                    var msgRruleJs = rrule.toText();
                    var state = msgRruleJs.match(/RRule error/i)
                        ? 'warning'
                        : 'status';
                    validMsg.html('<div class="messages messages--' + state + '">' + msgRruleJs + '</div>');
                    rruleField.val(rrule.toString());
                  }
                  catch (err) {
                    var message = Drupal.t('The inserted string is not valid.');
                    validMsg.html('<div class="messages messages--warning">' + message + '</div>');
                  }
                }
              });
            }
          });
        }
        else {
          noLibMessage();
        }
      }
      catch (e) {
        noLibMessage();
      }
    }
  };

}(jQuery, Drupal));
