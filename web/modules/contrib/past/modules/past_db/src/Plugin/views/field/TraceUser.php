<?php

namespace Drupal\past_db\Plugin\views\field;

use Drupal\past_db\Entity\PastEvent;
use Drupal\user\Entity\User;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to show user and add trace link. Also called Actor.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("past_db_trace_user")
 */
class TraceUser extends FieldPluginBase {

  /**
   * Preload the user so that render hits the static cache.
   */
  public function preRender(&$values) {
    parent::preRender($values);

    $uids = [];
    foreach ($values as $value) {
      $uid = $this->getValue($value);
      if (is_numeric($uid) && $uid > 0) {
        $uids[] = $uid;
      }
    }
    if ($uids) {
      User::loadMultiple($uids);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $event_id = $this->getValue($values);
    /** @var PastEvent $event */
    $event = \Drupal::entityTypeManager()->getStorage('past_event')->load($event_id);
    if (isset($event)) {
      return $event->getActorDropbutton(20, \Drupal::request()->getPathInfo());
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    return $values->event_id;
  }

}
