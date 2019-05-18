/**
 * @file
 * Fast Autocomplete jQuery plugin.
 */

(function ($, Drupal) {
  var facJsonRequest = null;

  /**
   * Apply the fastAutocomplete plugin to given input:text elements.
   */
  $.fn.fastAutocomplete = function (options) {
    // Default settings.
    var settings = $.extend({
      id: null,
      jsonFilesPath: undefined,
      keyMinLength: 1,
      keyMaxLength: 5,
      breakpoint: 0,
      enabled: false,
      resizeTimer: null,
      emptyResult: '',
      allResultsLink: true,
      allResultsLinkThreshold: -1,
      highlightEnabled: false,
      resultLocation: false
    }, options);

    // Check if a jsonFilesPath is set before doing anything.
    if (settings.jsonFilesPath !== undefined) {
      // Only apply the plugin on input fields of type text.
      this.filter('input').each(function (index) {
        var that = this;

        $(this).attr({
          'autocomplete':'off',
          'autocorrect': 'off',
          'autocapitalize': 'none',
          'spellcheck': 'false',
          'aria-autocomplete':'list',
          'aria-owns': 'fac-result-' + settings.id + '-' + index
        });

        // Create the hidden result div.
        var facResult = $('<div>').attr({
          'class': 'fac-result hidden',
          'id': 'fac-result-' + settings.id + '-' + index
        });

        toggleResponsiveBehavior(facResult, settings);
        $(window).resize(function (e) {
          clearTimeout(settings.resizeTimer);
          settings.resizeTimer = setTimeout(toggleResponsiveBehavior(facResult, settings), 250);
        });

        // Add a hidden div to announce changes to screen readers.
        var announce = $('<div>').attr({
          'class': 'announce visually-hidden',
          'role': 'status',
          'aria-live': 'polite'
        });
        announce.appendTo(facResult);

        // Create result list.
        var resultList = $('<ul>').attr({
          'class': 'result-list hidden'
        }).appendTo(facResult);

        if (settings.allResultsLink) {
          // Add the see all link to the result div.
          var seeAllLink = $('<li>').attr({
            'class': 'see-all-link'
          }).html('<div><a href="#">' + Drupal.t('See all results') + '</a></div></li>');
          // The mousedown and click events are to prevent the default link behavior.
          seeAllLink.find('> div > a').mousedown(function (e) {
            e.preventDefault();
          }).click(function (e) {
            e.preventDefault();
          });
          seeAllLink.css('cursor', 'pointer').mousedown(function (e) {
            switch (e.which) {
              // Left mouse click.
              case 1:
                $(that).closest('form').submit();
                break;
            }
            e.preventDefault();
          }).hover(function (e) {
            facResult.find('> ul.result-list > li.selected').removeClass('selected');
            $(this).addClass('selected');
          }).addClass('hidden');
          seeAllLink.appendTo(resultList);
        }

        if (settings.emptyResult) {
          // Add the empty result text.
          var emptyResults = $('<div>').attr({
            'class': 'empty-result'
          });
          emptyResults.html(settings.emptyResult);
          emptyResults.find('a').each(function () {
            $(this).on('mousedown', function (e) {
              e.preventDefault();
              switch (e.which) {
                // Left mouse click.
                case 1:
                  window.location = $(this).attr('href');
                  break;
              }
            });
          });
          emptyResults.appendTo(facResult);
        }

        if (settings.resultLocation) {
          facResult.appendTo(settings.resultLocation);
        }
        else {
          var form = $(this).closest('form');
          form.addClass('form-fac-result');
          facResult.appendTo(form);
        }
        // When a character is entered perform the necessary ajax call. Don't
        // respond to any special keys.
        $(this).on('keyup', function (e) {
          if (settings.enabled) {
            if (!e) {
              e = window.event;
            }
            switch (e.keyCode) {
              case 9:
              case 16:
              case 17:
              case 18:
              case 20:
              case 33:
              case 34:
              case 35:
              case 36:
              case 37:
              case 38:
              case 39:
              case 40:
              case 13:
              case 27:
                return;

              default:
                populateResults(this, facResult, settings);
                return;
            }
            e.preventDefault();
          }
        });

        $(this).on('paste', function (e) {
          $(that).trigger('keyup');
        });

        // Handle special keys (up, down, esc).
        $(this).on('keydown', function (e) {
          if (settings.enabled) {
            if (!e) {
              e = window.event;
            }

            switch (e.keyCode) {
              // Down arrow.
              case 40:
                selectDown(facResult);
                e.preventDefault();
                break;

              // Up arrow.
              case 38:
                selectUp(facResult);
                e.preventDefault();
                break;

              // Enter.
              case 13:
                var selected = facResult.find('li.selected:not(.see-all-link)');
                if (selected.length) {
                  selected.find('a:not(.contextual-links)')[0].click();
                  e.preventDefault();
                }
                else {
                  return;
                }
                break;

              // Esc.
              case 27:
                if (facJsonRequest !== null) {
                  facJsonRequest.abort();
                }
                facResult.addClass('hidden');
                e.preventDefault();
                break;

              default:
                return;
            }
          }
        });

        // Hide the result div when the input element loses focus.
        $(this).on('blur', function (e) {
          if (settings.enabled) {
            if (facJsonRequest !== null) {
              facJsonRequest.abort();
            }
            facResult.addClass('hidden');
          }
        });

        // When the input element gains focus, show the result.
        $(this).on('focus', function (e) {
          if (settings.enabled) {
            facResult.removeClass('hidden');
          }
        });
      });
    }

    // Return the original object to make the plugin chainable.
    return this;
  };

  // Enable or disable the Fast Autocomplete behavior based on a breakpoint.
  function toggleResponsiveBehavior(facResult, settings) {
    var browserWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
    if (browserWidth >= settings.breakpoint) {
      settings.enabled = true;
    }
    else {
      settings.enabled = false;
      if (facResult !== undefined) {
        facResult.addClass('hidden');
      }
    }
  }

  // Populates the results div with suggestions from an AJAX response.
  function populateResults(element, facResult, settings) {
    var value = $(element).val();
    value = value.trim();

    // Remove any slashes from the value, since that will cause 404s,
    // because the slash will create extra path parameters that we do
    // not want.
    value = value.replace(/\s*\/\s*/g, '_');
    value = value.replace(/\s+/g, '_');

    // Make sure the value is lowercase for case-insensitive search.
    value.toLowerCase();

    var resultList = facResult.find('> ul.result-list');
    var emptyResult = facResult.find('> div.empty-result');
    var announce = facResult.find('> div.announce');
    var seeAllLink = undefined;
    if (settings.allResultsLink) {
      seeAllLink = resultList.find('> li.see-all-link');
    }

    // Trim the result to the maximum length.
    if (value.length > settings.keyMaxLength) {
      value = value.substring(0, settings.keyMaxLength);
    }

    // Only perform the ajax call if the key has a minimum length. Append a
    // timestamp to the url to prevent the browser caching the json response.
    if (value.length >= settings.keyMinLength) {
      $(element).trigger('fac:requestStart');
      facJsonRequest = $.ajax({
        url: settings.jsonFilesPath + value + '.json?nocache=' + (new Date()).getTime(),
        dataType: 'json',
        type: 'GET',
        processData: false,
        beforeSend : function () {
          if (facJsonRequest !== null) {
            facJsonRequest.abort();
          }
        },
        success: function (data, status, xhr) {
          if (data.items.length) {
            announce.text(Drupal.formatPlural(data.items.length, '@count result found.', '@count results found.') + ' ' + Drupal.t('Use the up/down keys to navigate the results or press <ENTER> to show all results.'));
            if (seeAllLink) {
              if (data.items.length >= settings.allResultsLinkThreshold) {
                seeAllLink.find('> div > a').html(Drupal.t('See all results for "%key"', {'%key': $(element).val()}));
                seeAllLink.removeClass('hidden');
              }
              else {
                seeAllLink.addClass('hidden');
              }
            }
            resultList.find('> li.result').remove();
            emptyResult.addClass('hidden');
            $.each(data.items, function (key, dataValue) {
              var item = $('<li class="result">' + dataValue + '</li>');
              Drupal.attachBehaviors(item[0]);
              item.css('cursor', 'pointer').mousedown(function (e) {
                e.preventDefault();
                switch (e.which) {
                  // Left mouse click.
                  case 1:
                    var clickedItem = item.find('a:not(.contextual-links a)');
                    clickedItem.click();
                    break;

                }
              }).hover(function (e) {
                resultList.find('> li.selected').removeClass('selected');
                $(this).addClass('selected');
              });
              if (seeAllLink !== undefined) {
                item.insertBefore(seeAllLink);
              }
              else {
                item.appendTo(resultList);
              }
            });
            resultList.removeClass('hidden');
            facResult.removeClass('hidden');
            if (settings.highlightingEnabled) {
              resultList.find('> li.result').unmark().mark($(element).val().split(' '));
            }
          }
          else {
            announce.text('No results found.');
            resultList.addClass('hidden');
            resultList.find('li.result').remove();
            if (seeAllLink) {
              seeAllLink.addClass('hidden');
            }
            facResult.addClass('hidden');
            emptyResult.removeClass('hidden');
          }
          $(element).trigger('fac:requestEnd');
        },
        fail: function () {
          $(element).trigger('fac:requestEnd');
        }
      });
    }
    else {
      if (settings.highlightingEnabled) {
        resultList.find('> li.result').unmark().mark($(element).val().split(' '));
      }
      // If the key is empty, clear the result div and show the empty result content.
      if (value.length < 1) {
        announce.text('');
        resultList.addClass('hidden');
        resultList.find('> li.result').remove();
        if (seeAllLink) {
          seeAllLink.addClass('hidden');
        }
        facResult.addClass('hidden');
        emptyResult.removeClass('hidden');
      }
    }
  }

  // Select the next suggestion.
  function selectDown(facResult) {
    var selector = '> div.empty-result ul';
    if (facResult.find('> ul.result-list > li').length) {
      selector = '> ul.result-list';
    }
    var selected = facResult.find(selector + ' > li.selected');
    if (selected.length) {
      selected.removeClass('selected');
      selected.next('li').addClass('selected');
    }
    else {
      facResult.find(selector + ' > li:first').addClass('selected');
    }
  }

  // Select the previous suggestion.
  function selectUp(facResult) {
    var selector = '> div.empty-result ul';
    if (facResult.find('> ul.result-list > li').length) {
      selector = '> ul.result-list';
    }
    var selected = facResult.find(selector + ' > li.selected');
    if (selected.length) {
      selected.removeClass('selected');
      selected.prev('li').addClass('selected');
    }
    else {
      facResult.find(selector + ' > li:last').addClass('selected');
    }
  }

}(jQuery, Drupal));
