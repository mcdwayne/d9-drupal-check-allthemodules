/**
 * @file
 * Templates for tooltips.
 */

(function ($, Drupal, drupalSettings) {

  Drupal.theme.imageTooltipIconForm = function(icon) {
    var html = '';
    html += '<div class="tip ui-draggable" id="tooltip-icon-' + icon.nid + '" data-nid="' + icon.nid + '" data-title="' + icon.title + '" data-src="' + icon.src + '" data-delta="' + icon.delta + '" style="position: relative; left: ' + icon.left + 'px; top: ' + icon.top + 'px;">';
    html += '<img class="tooltip-icon" src="' + icon.src + '" title="' + icon.title + '">';
    html += '</div>';
    return html;
  };

  Drupal.theme.imageTooltipIconView = function(icon) {
    var html = '';
    html += '<div class="tip" data-nid="' + icon.nid + '" style="position: relative; left: ' + icon.left + 'px; top: ' + icon.top + 'px;">';
    html += '<a href="/tooltip/' + icon.nid + '/nojs" class="use-ajax" data-dialog-type="modal" title="' + icon.title + '">';
    html += '<img class="tooltip-icon" id="tooltip-icon-' + icon.nid + '" src="' + icon.src + '">';
    html += '</a>';
    html += '</div>';
    return html;
  };

  Drupal.theme.tooltipConfigLink = function(nid) {
    var html = '';
    html += '<div class="tooltip-config-link">';
    html += '<a href="#tooltip-icon-' + nid + '" class="tooltip-configure-link" data-nid="' + nid + '">';
    html += Drupal.t('Change position for this tooltip');
    html += '</a>';
    html += '</div>';
    return html;
  };

  Drupal.theme.tooltipWarning = function() {
    var html = '';
    html += '<div class="tooltips-empty-warning">';
    html += '<span class="closebtn">&times;</span>';
    html += '<strong>' + Drupal.t('Warning!') + '</strong>&nbsp;';
    html += Drupal.t('Please upload base image before adding tooltips.');
    html += '</div>';
    return html;
  };

})(jQuery, Drupal, drupalSettings);
