<?php

namespace Drupal\entity_pilot\Batch;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\entity_pilot\CustomsInterface;
use Drupal\entity_pilot\ArrivalInterface;
use Drupal\entity_pilot\Entity\Arrival;
use Drupal\entity_pilot\FlightInterface;

/**
 * A customs officer does the bidding of the customs service in a batch API.
 */
class CustomsOfficer {

  /**
   * Customs service.
   *
   * @var \Drupal\entity_pilot\CustomsInterface
   */
  protected $customs;

  /**
   * Constructs a new CustomsOfficer object.
   *
   * @param \Drupal\entity_pilot\CustomsInterface $customs
   *   The customs service.
   */
  public function __construct(CustomsInterface $customs) {
    $this->customs = $customs;
  }

  /**
   * Factory method.
   *
   * @return \Drupal\entity_pilot\Batch\CustomsOfficer
   *   A new instance.
   */
  protected static function factory() {
    $container = \Drupal::getContainer();
    return new static($container->get('entity_pilot.customs'));
  }

  /**
   * Batch API callback to land a single passenger.
   *
   * @param \Drupal\entity_pilot\ArrivalInterface $arrival
   *   The arrival from which the passenger is being landed.
   * @param string $passenger_id
   *   The passenger ID to approve.
   * @param array $context
   *   Context of the batch operation.
   */
  public static function landPassenger(ArrivalInterface $arrival, $passenger_id, array &$context) {
    $officer = static::factory();
    $officer->screen($arrival);
    $entity = $officer->approve($arrival, $passenger_id);
    $context['results'][$passenger_id] = $entity->label();
    if ($entity) {
      $context['sandbox']['message'] = SafeMarkup::format('Passenger: %label landed', ['%label' => $entity->label()]);
    }
    if (!isset($context['results']['arrival'])) {
      $context['results']['arrival'] = $arrival;
    }
  }

  /**
   * Batch API finished callback.
   *
   * @param bool $success
   *   TRUE if succeeded.
   * @param array $results
   *   Batch processing results.
   */
  public static function landingFinished($success, array $results) {
    /* @var \Drupal\entity_pilot\ArrivalInterface $arrival */
    $translator = \Drupal::translation();
    $logger = \Drupal::logger('entity_pilot');
    if ($success && isset($results['arrival']) && $arrival = $results['arrival']) {
      // Fetch a fresh version of the arrival, as we call save.
      \Drupal::entityTypeManager()->getStorage('ep_arrival')->resetCache([$arrival->id()]);
      $arrival = Arrival::load($arrival->id());
      $account = $arrival->getAccount();
      $watchdog_args = ['@type' => $arrival->bundle(), '%info' => $arrival->label()];
      $t_args = ['@type' => $account->label(), '%info' => $arrival->label()];
      $logger->info('@type: updated %info.', $watchdog_args);
      drupal_set_message($translator->translate('Arrival for @type account named %info has been updated.', $t_args));
      $arrival->setStatus(FlightInterface::STATUS_LANDED)->save();
      \Drupal::service('entity_pilot.customs')->clearCache($arrival);
      $items = [];
      /* @var \Drupal\Core\Entity\ContentEntityInterface $item */
      // Results also includes the arrival entity, remove that.
      unset($results['arrival']);
      $successful = array_filter($results);
      foreach ($successful as $item) {
        $items[] = $item;
      }
      $message = $translator->formatPlural(count($successful), 'Imported 1 item:', 'Imported @count items:');
      $list = [
        'message' => ['#markup' => $message],
        'list' => [
          '#theme' => 'item_list',
          '#items' => $items,
        ],
      ];
      drupal_set_message(\Drupal::service('renderer')->render($list));
      if (count($results) !== count($successful)) {
        drupal_set_message($translator->formatPlural(count($results) - count($successful), '1 item could not be imported.', '@count items could not be imported.'), 'warning');
      }
    }
    else {
      drupal_set_message($translator->translate('An error occurred whilst landing the flight, check logs for more detail'), 'error');
      $logger->error('An error occurred whilst landing the flight, check logs for more detail');
    }
  }

  /**
   * Lands a single passenger.
   *
   * @param \Drupal\entity_pilot\ArrivalInterface $arrival
   *   The arrival from which the passenger is being landed.
   * @param string $passenger_id
   *   Passenger to approve.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|false
   *   The saved entity (passenger) or FALSE in case of an error.
   */
  public function approve(ArrivalInterface $arrival, $passenger_id) {
    $entity = $this->customs->approvePassenger($passenger_id);
    // Flush caches to ensure stale references to unsaved items are removed.
    $this->customs->clearCache($arrival);
    return $entity;
  }

  /**
   * Ensures that the entire flight has been screened first.
   *
   * This makes sure that the normalizer etc are prepared.
   *
   * @param \Drupal\entity_pilot\ArrivalInterface $arrival
   *   The flight from which the passenger is being approved.
   */
  public function screen(ArrivalInterface $arrival) {
    $this->customs->screen($arrival, FALSE);
  }

}
