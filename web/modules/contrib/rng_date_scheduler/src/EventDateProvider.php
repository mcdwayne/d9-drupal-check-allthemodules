<?php

/**
 * @file
 * Contains \Drupal\rng_date_scheduler\EventDateProvider.
 */

namespace Drupal\rng_date_scheduler;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\rng\EventManagerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Event date provider.
 */
class EventDateProvider implements EventDateProviderInterface {

  use ContainerAwareTrait;

  /**
   * The related request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a new EntityIsEventCheck object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *  The request stack.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(RequestStack $request_stack, EventManagerInterface $event_manager) {
    $this->requestStack = $request_stack;
    $this->eventManager = $event_manager;
  }

  /**
   * @inheritdoc
   */
  function getDates(EntityInterface $event) {
    $dates = [];
    $field_access = $this->getFieldAccess($event->getEntityTypeId(), $event->bundle(), TRUE);
    foreach ($field_access as $field_name => $field) {
      /** @var \Drupal\datetime\Plugin\Field\FieldType\DateTimeFieldItemList $field_item_list */
      $field_item_list = $event->{$field_name};

      if (isset($field_item_list->date)) {
        /** @var DrupalDateTime $value */
        $value = $field_item_list->date;

        $date = new EventDateAccess();
        $date
          ->setFieldName($field_name)
          ->setDate($value)
          ->setAccessBefore($field['access']['before'])
          ->setAccessAfter($field['access']['after']);

        if ($field_item_list->getSetting('datetime_type') == 'date') {
          $during = $field['access']['during'];

          $date2 = clone $date;

          $value->setTime(0, 0, 0);
          $dates[] = $date->setDate($value)
            ->setAccessAfter($during);

          $value2 = clone $value;
          $value2->modify('+1 day');
          $dates[] = $date2->setDate($value2)
            ->setAccessBefore($during);
        }
        else {
          $dates[] = $date;
        }
      }
    }

    // Sort dates ascending.
    usort($dates, function($a, $b) {
      /** @var \Drupal\rng_date_scheduler\EventDateAccess $a */
      /** @var \Drupal\rng_date_scheduler\EventDateAccess $b */
      if ($a->getDate() == $b->getDate()) {
        return 0;
      }
      else {
        return $a->getDate() < $b->getDate() ? -1 : 1;
      }
    });

    return $dates;
  }

  /**
   * @inheritdoc
   */
  function getRegistrationCreateAccess(EntityInterface $event) {
    $dates = $this->getDates($event);
    if (!count($dates)) {
      $default_access = $this->getDefaultAccess($event->getEntityTypeId(), $event->bundle());
      return AccessResult::forbiddenIf($default_access === FALSE)
        ->addCacheableDependency($event);
    }

    $now = DrupalDateTime::createFromTimestamp($this->requestStack->getCurrentRequest()->server->get('REQUEST_TIME'));

    $previous_after = NULL;
    foreach ($dates as $date) {
      $before = $date->canAccessBefore();
      $after = $date->canAccessAfter();

      if ($now < $date->getDate()) {
        // If this is not the last date slot, add max age until the date.
        $time_until = $date->getDate()->format('U') - $now->format('U');
        return AccessResult::forbiddenIf(in_array(FALSE, [$previous_after, $before], TRUE))
          ->addCacheableDependency($event)
          ->setCacheMaxAge($time_until);
      }

      $previous_after = $after;
    }

    return AccessResult::forbiddenIf($previous_after === FALSE)
      ->addCacheableDependency($event);
  }

  /**
   * @inheritdoc
   */
  function getDefaultAccess($entity_type_id, $bundle) {
    $event_type = $this->eventManager->eventType($entity_type_id, $bundle);
    return $event_type->getThirdPartySetting('rng_date_scheduler', 'default_access', NULL);
  }

  /**
   * @inheritdoc
   */
  function getFieldAccess($entity_type_id, $bundle, $status = TRUE) {
    $event_type = $this->eventManager->eventType($entity_type_id, $bundle);

    $field_access = [];
    foreach ($event_type->getThirdPartySetting('rng_date_scheduler', 'fields', []) as $field) {
      if (isset($field['field_name']) && isset($field['status'])) {
        if (!isset($status) || $field['status'] == $status) {
          $field_name = $field['field_name'];
          $field_access[$field_name] = $field;
        }
      }
    }

    return $field_access;
  }

}
