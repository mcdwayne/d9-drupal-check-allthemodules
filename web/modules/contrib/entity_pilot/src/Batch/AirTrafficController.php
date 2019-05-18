<?php

namespace Drupal\entity_pilot\Batch;

use Drupal\Core\Url;
use Drupal\entity_pilot\ArrivalInterface;
use Drupal\entity_pilot\Data\FlightManifestInterface;
use Drupal\entity_pilot\DepartureInterface;
use Drupal\entity_pilot\Exception\TransportException;
use Drupal\entity_pilot\Utility\FlightStub;
use Drupal\Core\Utility\Error;

/**
 * Does the bidding for the air traffic control service in a batch API.
 */
class AirTrafficController {

  /**
   * Batch API callback.
   *
   * @param \Drupal\entity_pilot\DepartureInterface $departure
   *   Entity to send.
   * @param array $context
   *   Context from batch API.
   *
   * @throws \Drupal\entity_pilot\Exception\TransportException
   *   When cannot be sent.
   */
  public static function takeoff(DepartureInterface $departure, array &$context) {
    $air_traffic = \Drupal::service('entity_pilot.air_traffic_control');
    $translation = \Drupal::translation();
    try {
      $context['results'][] = $departure;
      $stub = FlightStub::create($departure->getRevisionId())
        ->setEntity($departure);
      $air_traffic->takeoff($stub);
    }
    catch (TransportException $e) {
      drupal_set_message($e->getMessage() . '(' . $e->getCode() . ')', 'error');
      $message = '%type: @message in %function (line %line of %file).';
      $variables = Error::decodeException($e);
      \Drupal::logger('entity_pilot')->error($message, $variables);
      $context['results']['errors'] = TRUE;
    }
    catch (\CryptoTestFailedException $e) {
      $context['results']['errors'] = TRUE;
      drupal_set_message($translation->translate('An error occurred whilst encrypting your flight, please check your secret is valid.'), 'error');
    }
    catch (\CannotPerformOperationException $e) {
      drupal_set_message($translation->translate('An error occurred whilst encrypting your flight, please check your secret is valid.'), 'error');
      $context['results']['errors'] = TRUE;
    }
  }

  /**
   * Batch API finished callback.
   *
   * @param bool $success
   *   TRUE if successful.
   * @param array $results
   *   Array of results.
   */
  public static function sent($success, array $results) {
    $departure = reset($results);
    $translation = \Drupal::translation();
    if ($success && empty($results['errors'])) {
      /* @var \Drupal\entity_pilot\DepartureInterface $departure */
      $account = $departure->getAccount();
      $watchdog_args = [
        '@type' => $departure->bundle(),
        '%info' => $departure->label(),
      ];
      $t_args = [
        '@type' => $account->label(),
        '%info' => $departure->label(),
      ];
      \Drupal::logger('entity_pilot')->notice('@type: updated %info.', $watchdog_args);
      drupal_set_message($translation->translate('Departure for @type account named %info has been updated and sent.', $t_args));
    }
    else {
      drupal_set_message($translation->translate('Departure %label could not be sent to Entity Pilot, please try again later or check logs for more detail.', [
        '%label' => $departure->label(),
      ]), 'error');
    }
  }

  /**
   * Batch API callback to fetch contents for an arrival.
   *
   * @param \Drupal\entity_pilot\ArrivalInterface $arrival
   *   The arrival from which the contents are being fetched.
   * @param \Drupal\entity_pilot\Data\FlightManifestInterface $flight
   *   Incoming flight manifest.
   * @param string $site
   *   Site URI.
   * @param array $context
   *   Context of the batch operation.
   */
  public static function land(ArrivalInterface $arrival, FlightManifestInterface $flight, $site, array &$context) {
    $arrival
      ->setContents($flight->getTransposedContents($site, \Drupal::service('entity_pilot.transport'), $arrival->getAccount()))
      ->setFieldMap($flight->getFieldMapping(TRUE))
      ->save();
    $context['results']['arrival'] = $arrival;
  }

  /**
   * Batch API callback to link an arrival to a departure.
   *
   * @param \Drupal\entity_pilot\ArrivalInterface $arrival
   *   The arrival to link.
   */
  public static function linkDeparture(ArrivalInterface $arrival) {
    // Create a linked departure if needed.
    $departure = $arrival->createLinkedDeparture(\Drupal::entityManager(), \Drupal::service('rest.link_manager.type'));
    drupal_set_message(t('Created <a href=":url">linked Departure</a>.', [
      ':url' => Url::fromRoute('entity.ep_departure.edit_form', [
        'ep_departure' => $departure->id(),
      ])->toString(),
    ]));
  }

  /**
   * Batch API finished callback for fetching contents.
   */
  public static function landed() {
    drupal_set_message(\Drupal::translation()->translate('Fetched contents of remote flight - flight is now ready for approval.'));
  }

}
