/**
 * @file
 * Javascript for batch jobs.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.batch_jobs = {
    attach: function (context, settings) {
      $('div.batch').once('batch').each(function() {
        var batch = $('div.batch').attr('class');
        if (typeof batch !== 'undefined') {
          batch = batch.split(' ');
          var bid = batch[1];
          bid = bid.replace(/^batch-/, '');
          token = batch[2];
          var token = token.replace(/^batch-/, '');
          var progress = 0;
          $('#progress').progressbar({
            value: progress
          });
          $('div.batch-percent').html(progress + '%');
          $.get(drupalSettings.path.baseUrl + 'batch-jobs/' + bid +
            '/callback/' + token, null, updateProgressBar);
        }
      });

      $('table.batch-jobs input.button[value="Run"]').click(function (event) {
        var button = event.target.id.split('-');
        var bid = button[2];
        var token = $('input#' + event.target.id).attr('token');
        location.href = drupalSettings.path.baseUrl + 'batch-jobs/' + bid +
          '/run/' + token;
        return false;
      });

      $('table.batch-jobs input.button[value="Run finish tasks"]').click(function (event) {
        var button = event.target.id.split('-');
        var bid = button[2];
        var token = $('input#' + event.target.id).attr('token');
        location.href = drupalSettings.path.baseUrl + 'batch-jobs/' + bid +
          '/finish_tasks/' + token;
        return false;
      });

      $('table.batch-jobs input.button[value="Delete"]').click(function (event) {
        var button = event.target.id.split('-');
        var bid = button[2];
        var token = $('input#' + event.target.id).attr('token');
        location.href = drupalSettings.path.baseUrl + 'batch-jobs/' + bid +
          '/delete/' + token;
        return false;
      });

      $('table.batch-tasks input.button[value="Run"]').click(function (event) {
        var button = event.target.id.split('-');
        var tid = button[2];
        var token = $('input#' + event.target.id).attr('token');
        $(event.target).parent().html('Running');
        $.get(drupalSettings.path.baseUrl + 'batch-jobs/' + tid + '/task/' +
          token, null, updateTask);
        return false;
      });
    }
  };

  var updateProgressBar = function (response) {
    if (response.status) {
      var progress = Math.round(10000.0 * (response.completed /
        response.total)) / 100.0;
      $('#progress').progressbar({
        value: progress
      });
      $('div.batch-progress').html('<p>' + response.completed + ' of ' +
        response.total + '<br />' + progress + '%');
      if (response.started == response.total) {
        if (response.completed == response.total) {
          $('div.batch-complete').html('<p>Finish tasks started</p>');
          $.get(drupalSettings.path.baseUrl + 'batch-jobs/' + response.bid +
            '/finish/' + response.token, null, jobFinished);
        }
        var url = drupalSettings.path.baseUrl + 'admin/reports/batch-jobs';
        $('div.batch-jobs').html('<p><a href ="' + url +
          '">Batch jobs</a></p>');
      }
      else {
        $.get(drupalSettings.path.baseUrl + 'batch-jobs/' + response.bid +
          '/callback/' + response.token, null, updateProgressBar);
      }
    }
  };

  var jobFinished = function (response) {
    $('div.batch-complete').html('<p>Finish tasks completed</p>');
  };

  var updateTask = function (response) {
    var tr = $('tr[data-drupal-selector="edit-tasks-' + response.tid + '"]');
    var td = $(tr).find('td').first();
    $(td).next().next().html(response.start);
    $(td).next().next().next().html(response.end);
    $(td).next().next().next().next().html(response.status);
    $(td).next().next().next().next().next().html(response.message);
    $(td).next().next().next().next().next().next().html('');
  };
})(jQuery, Drupal, drupalSettings);
