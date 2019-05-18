<?php

/*
 * Template for messages displayed by Instapage plugin.
 */
?>
<div class="l-grid">
  <div id="messages-container" class="instapage-cms-plugin l-grid__cell l-grid__cell--1/2" data-bind="foreach: messages">
    <div class="message-wrapper c-modal c-modal--popover">
      <div class="message c-modal__body" data-bind="html: text, css: type"></div>
      <div class="c-button c-button--clean c-modal__close c-button--large" data-bind="click: $root.removeMessage">
        <i class="delete-message material-icons c-button__icon"><?php echo InstapageCmsPluginConnector::lang('close'); ?></i>
      </div>
    </div>
  </div>
</div>
