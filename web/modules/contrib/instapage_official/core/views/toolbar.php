<?php

/*
 * Template for toolbar ofInstapage plugin.
 */
?>
<div id="instapage-toolbar" class="l-wrapper instapage-cms-plugin">
  <div>
    <div class="c-tabs c-tabs--full-width c-tabs--with-shadow">
      <ul class="c-tabs__list">
        <li class="c-tab listing-view is-active">
          <a class="c-tab__text" data-bind="click: loadListPages"><?php echo InstapageCmsPluginConnector::lang('List pages'); ?></a>
        </li>
        <li class="c-tab edit-view">
          <a class="c-tab__text" data-bind="click: loadEditPage"><?php echo InstapageCmsPluginConnector::lang('Add page'); ?></a>
        </li>
        <li class="c-tab settings-view">
          <a class="c-tab__text" data-bind="click: loadSettings"><?php echo InstapageCmsPluginConnector::lang('Settings'); ?></a>
        </li>
      </ul>
      <div class="c-tabs__slider"></div>
    </div>
  </div>
</div>
