<?php

/*
 * Template for Edit page tab in Instapage plugin dashboard.
 */
?>
<h2 class="ui-subtitle" data-bind="text: id() ? '<?php echo InstapageCmsPluginConnector::lang('Edit page'); ?>' : '<?php echo InstapageCmsPluginConnector::lang('Add page'); ?>' "></h2>
<form class="c-section l-space-tertiary" data-bind="visible: subAccounts().length">
  <div class="ui-sub-section">
    <div class="l-grid l-space-top-primary">
      <div class="l-grid__cell l-grid__cell--1/3">
        <label><?php echo InstapageCmsPluginConnector::lang('Sub-account'); ?></label>
        <select class="c-form-text-item__field is-not-empty" data-bind="options: subAccounts, optionsText: 'name', optionsValue: 'id', value: choosenSubAccount"></select>
      </div>
      <div class="l-grid__cell l-grid__cell--1/3">
        <label><?php echo InstapageCmsPluginConnector::lang('Landing Page'); ?></label>
        <select class="c-form-text-item__field is-not-empty" data-bind="options: landingPages, optionsText: 'title', optionsValue:'id', value: choosenLandingPage"></select>
      </div>
      <div class="l-grid__cell l-grid__cell--1/3" data-bind="css: {'has-danger': !isTypeValid()}">
        <label><?php echo InstapageCmsPluginConnector::lang('Page Type'); ?></label>
        <select class="c-form-text-item__field is-not-empty" data-bind="options: pageTypes, optionsText: 'title', optionsValue:'name', value: choosenLandingPageType"></select>
      </div>
    </div>
  </div>
  <div class="los-pollos c-form-text-item" data-bind="visible: choosenLandingPageType() === 'page', css: {'has-danger': !isSlugValid()}">
    <span class="c-form-text-item__info"><?php echo InstapageCmsPluginConnector::getHomeURL() . '/'; ?></span>
    <div>
      <input class="c-form-text-item__field" data-bind="value: slug, event: {change: validateSlug}, css: {'is-not-empty': slug()}" type="textbox" />
      <label class="c-form-text-item__label"><?php echo InstapageCmsPluginConnector::lang('URL for this page'); ?></label>
      <div class="c-form-text-item__bar"></div>
      <span class="c-form-text-item__info slug" data-bind="visible: !isSlugValid()">
        <span><?php echo InstapageCmsPluginConnector::lang('Slug is invalid.'); ?></span>
        <i class="material-icons c-form-text-item__info-icon"><?php echo InstapageCmsPluginConnector::lang('error'); ?></i>
      </span>
    </div>
  </div>

  <div class="ui-sub-section" style="margin-top: 20px;">
    <button class="fx-ripple-effect c-button c-button--regular c-button--action" data-bind="click: publishPage" type="button" ><?php echo InstapageCmsPluginConnector::lang('Publish'); ?></button>
    <button class="fx-ripple-effect c-button c-button--regular" data-bind="click: masterModel.toolbarModel.loadListPages"><span class="c-button__text"><?php echo InstapageCmsPluginConnector::lang('Back'); ?></span></button>
  </div>
</form>
