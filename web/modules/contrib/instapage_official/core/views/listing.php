<?php

/*
 * Template for Listing page tab in Instapage plugin dashboard.
 */
?>
<div class="c-form-text-item  c-form-text-item--no-label">
  <input type="text" class="c-form-text-item__field c-form-text-item__field--with-icon" placeholder="<?php echo InstapageCmsPluginConnector::lang('Search query'); ?>" data-bind="value: query, valueUpdate: 'keyup'">
  <div class="c-form-text-item__bar"></div>
  <button class="c-button c-button--clean c-button--large c-form-text-item__button">
    <i class="material-icons c-button__icon"><?php echo InstapageCmsPluginConnector::lang('search'); ?></i>
  </button>
  <span class="c-form-text-item__info">
    <span><?php echo InstapageCmsPluginConnector::lang('Search for page title or URL'); ?></span>
  </span>
</div>
<h2 class="ui-subtitle"><?php echo InstapageCmsPluginConnector::lang('Page listing'); ?></h2>
<div data-bind='simpleGrid: gridViewModel'> </div>

<script type="text/html" id="page-row-template">
  <tr>
    <td class="preview-image c-table__cell" data-bind="html: (typeof $data.screenshot !== 'undefined' && $data.screenshot !== null) ? '<div class=\'cropper\'><img src=\'' + $data.screenshot + '\' /></div>' : ''"></td>
    <td class="c-table__cell">
      <div class="page-title" data-bind="text: $data.title, click: masterModel.toolbarModel.loadEditPage"></div>
      <div class="page-url" data-bind="html: 'URL: <a href=\'<?php echo InstapageCmsPluginConnector::getHomeUrl();?>/' + $data.slug + '\' target=\'_blank\'/><?php echo InstapageCmsPluginConnector::getHomeUrl();?>/' + $data.slug + '</a>'"></div>
      <div class="page-actions">
        <div class="before-delete" data-bind="visible: !toDelete()">
          <button class="fx-ripple-effect c-button c-button--flat c-button--small" data-bind="click:masterModel.toolbarModel.loadEditPage"><?php echo InstapageCmsPluginConnector::lang('Edit'); ?></button>
          <button class="fx-ripple-effect c-button c-button--flat c-button--danger c-button--small" data-bind="click:masterModel.pagedGridModel.askForDeleteConfirmation"><?php echo InstapageCmsPluginConnector::lang('Delete'); ?></button>
        </div>
        <div class="after-delete" data-bind="visible: toDelete()">
          <button class="fx-ripple-effect c-button c-button--flat c-button--small" data-bind="click:masterModel.pagedGridModel.cancelDelete"><?php echo InstapageCmsPluginConnector::lang('Cancel'); ?></button>
          <button class="fx-ripple-effect c-button c-button--flat c-button--danger c-button--small" data-bind="click:masterModel.pagedGridModel.deletePage"><?php echo InstapageCmsPluginConnector::lang('Confirm delete'); ?></button>
        </div>
      </div>
    </td>
    <td data-bind="text: $data.type" class="c-table__cell c-table__cell--left"></td>
    <td data-bind="visible: !statsLoaded()" class="c-table__cell c-table__cell--left">
      <div class="l-group__item">
        <span class="c-loader"></span>
      </div>
    </td>
    <td data-bind="visible: statsLoaded() && typeof stats_cache() !== 'undefined' , template: {name: 'page-row-stats-template', foreach: stats_cache}" class="c-table__cell"></td>
    <td data-bind="visible: statsLoaded() && typeof stats_cache() === 'undefined'" class="c-table__cell">
      <?php echo InstapageCmsPluginConnector::lang('Stats are unavailable at the moment.'); ?>
    </td>
    <td data-bind="visible: !statsLoaded()" class="c-table__cell">
      <div class="l-group__item">
        <span class="c-loader"></span>
      </div>
    </td>
    <td data-bind="visible: !statsLoaded()" class="c-table__cell">
      <div class="l-group__item">
        <span class="c-loader"></span>
      </div>
    </td>
    <td data-bind="visible: !statsLoaded()" class="c-table__cell">
      <div class="l-group__item">
        <span class="c-loader"></span>
      </div>
    </td>
    <td data-bind="visible: statsLoaded(), text: totalStats() ? totalStats().visits : 0" class="c-table__cell"></td>
    <td data-bind="visible: statsLoaded(), text: totalStats() ? totalStats().conversions : 0" class="c-table__cell"></td>
    <td data-bind="visible: statsLoaded(), text: totalStats() ? totalStats().conversionRate + '%' : '0%'" class="c-table__cell"></td>
  </tr>
</script>

<script type="text/html" id="page-row-stats-template">
  <div class="variation">
    <div>
      <span data-bind="text: variation" class="variation-name c-badge c-badge--action c-badge--has-text"></span>
    </div>
    <div data-bind="text: visits + ' visits with ' + conversions + ' conversions'" class="variation-stats"></div>
    <div>
      <span data-bind="text: conversions_rate + '%'" class="variation-conversion-rate c-badge c-badge--success c-badge--has-text"></span>
    </div>
  </div>
</script>
