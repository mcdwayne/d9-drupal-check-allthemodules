/**
 * @file
 * Test output viewer behaviors.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.testOutputViewer = {
    attach: function (context, settings) {

      // Elements.
      var $dashboard = $('#tov-dashboard');
      var $title = $('#tov-title');
      var $created = $('#tov-created');
      var $firstButton = $('#tov-first');
      var $previousButton = $('#tov-previous');
      var $nextButton = $('#tov-next');
      var $lastButton = $('#tov-last');
      var $current = $('#tov-current');
      var $fileButton = $('#tov-file');
      var $description = $('#tov-description');
      var $reloadButton = $('#tov-reload');
      var $enlargeButton = $('#tov-enlarge');
      var $shrinkButton = $('#tov-shrink');
      var $iframe = $('#tov-iframe');

      var outputBaseUrl = settings.path.baseUrl + 'test-output/file/';

      var interval;

      var defaultState = {
        id: '',
        class: null,
        created: null,
        results: [],
        current: null,
        total: 0,
        file: null,
        fullscreen: false
      };
      var state = {};
      $.extend(state, defaultState);

      function fetchData() {
        var url = Drupal.url('test-output/data');
        $.getJSON(url, function (data) {
          if (data.error) {
            clearInterval(interval);
            alert('ERROR: ' + data.error);
            return;
          }

          if (!data.id) {
            reset();
            $title.html('No test results were found');
            return;
          }
          if (state.id !== data.id || state.total !== data.results.length) {
            state.created = data.created;
            state.id = data.id;
            state.results = data.results;
            state.class = data.class;
            state.total = state.results.length;
            state.current = settings.testOutputViewer.defaultResult === 'first' ? 0 : state.total - 1;
            render();
          }
        });
      }

      function render() {
        $current.html((state.current + 1) + '/' + state.total);
        var newestOutput = state.results[state.current];
        $title.html('#' + state.id + ' ' + state.class);
        $created.html(state.created);
        $description.html(newestOutput.description);

        if ($iframe.attr('src') !== outputBaseUrl + newestOutput.src) {
          $iframe.addClass('tov-loading');
          $iframe.attr('src', outputBaseUrl + newestOutput.src);
        }

        var isFirst = state.current === 0;
        var isLast = state.current + 1 === state.total;
        $firstButton.prop('disabled', isFirst);
        $previousButton.prop('disabled', isFirst);
        $nextButton.prop('disabled', isLast);
        $lastButton.prop('disabled', isLast);
        $fileButton.prop('disabled', state.total === 0);
      }

      function reset() {
        $.extend(state, defaultState);
        $iframe.attr('src', 'about:blank');
        $title.html('');
        $created.html('');
        $description.html('');
        $firstButton.prop('disabled', true);
        $previousButton.prop('disabled', true);
        $nextButton.prop('disabled', true);
        $lastButton.prop('disabled', true);
        $fileButton.prop('disabled', true);
        $current.html('0/0');
      }

      function moveToFirst() {
        state.current = 0;
        render();
      }

      function moveToPrevious() {
        if (state.current > 0) {
          state.current--;
          render()
        }
      }

      function moveToNext() {
        if (state.current + 1 < state.total) {
          state.current++;
          render()
        }
      }

      function moveToLast() {
        state.current = state.total - 1;
        render();
      }

      function enlarge() {
        $dashboard.addClass('tov-fullscreen');
        $enlargeButton.hide();
        $shrinkButton.show();
        state.fullscreen = true;
      }

      function shrink() {
        $dashboard.removeClass('tov-fullscreen');
        $enlargeButton.show();
        $shrinkButton.hide();
        state.fullscreen = false;
      }

      // Click events.
      $firstButton.click(moveToFirst);
      $previousButton.click(moveToPrevious);
      $nextButton.click(moveToNext);
      $lastButton.click(moveToLast);
      $enlargeButton.click(enlarge);
      $shrinkButton.click(shrink);
      $fileButton.click(function () {
        var currentOutput = state.results[state.current].src;
        window.open(settings.path.baseUrl + settings.testOutputViewer.outputPath + '/' + currentOutput);
      });
      $reloadButton.click(function () {
        reset();
        fetchData();
      });

      // Keydown events.
      $('body').keydown(function(event) {
        switch (event.key) {
          case 'ArrowLeft':
            event.ctrlKey ? moveToFirst() : moveToPrevious();
            break;

          case 'ArrowRight':
            event.ctrlKey ? moveToLast() : moveToNext();
            break;

          case 'F11':
            state.fullscreen ? shrink() : enlarge();
            break;

          case 'Escape':
            state.fullscreen && shrink();
            break;

          default:
            return;
        }
        event.preventDefault();
      });

      // Load events.
      $iframe.on('load', (function () {
        $iframe.removeClass('tov-loading');
        // Set iframe height.
        var iframe = $iframe.get(0);
        iframe.style.height = (iframe.contentDocument.body.scrollHeight + 50) + 'px';
      }));

      // Run viewer.
      fetchData();
      if (settings.testOutputViewer.autoUpdate) {
        interval = setInterval(fetchData, settings.testOutputViewer.autoUpdateTimeout * 1000);
      }

    }
  };

} (jQuery, Drupal));
