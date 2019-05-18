<?php

namespace Drupal\rules_webform\Event;

/**
 * Event that is fired when webform is submitted.
 *
 * The event object will be created and the event will be dispatched in hook_webform_submission_insert().
 */
class WebformSubmitEvent extends RulesWebformEventBase {
  const EVENT_NAME = 'webform_submit';

}
