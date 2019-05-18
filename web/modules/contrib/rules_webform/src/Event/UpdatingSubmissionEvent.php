<?php

namespace Drupal\rules_webform\Event;

/**
 * Event that is fired when webform submission is updating.
 *
 * The event object will be created and the event will be dispatched in hook_webform_submission_presave().
 */
class UpdatingSubmissionEvent extends RulesWebformEventBase {
  const EVENT_NAME = 'updating_submission';

}
