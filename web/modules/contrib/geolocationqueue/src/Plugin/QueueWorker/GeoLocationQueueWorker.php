<?php

namespace Drupal\geolocationqueue\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;

/**
 * GeoLocationQueue worker.
 *
 * @QueueWorker(
 *   id = "geolocation",
 *   title = @Translation("Geo Location Field Updater"),
 *   cron = {"time" = 10}
 * )
 */
class GeoLocationQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * GeoLocationQueueWorker constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->httpClient = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    foreach ([1, 2] as $attempts) {
      try {
        $this->_processItem($data);
        return;
      }
      // Re-process RequeueException here because drush run-queue does not.
      // @todo: remove this when that is fixed.
      catch (RequeueException $e) {
        // If it fails a second time, then we're over the daily limit.
        if ($attempts == 2) {
          throw $e;
        }
        usleep(1000000);
      }
    }
  }

  /**
   * Works on a single queue item.
   *
   * @param mixed $data
   *   The data that was passed to
   *   \Drupal\Core\Queue\QueueInterface::createItem() when the item was queued.
   *
   * @see QueueWorkerBase::processItem()
   */
  private function _processItem($data) {
    // Load the entity.
    if (!$entity = entity_load($data->entity_type, $data->entity_id)) {
      return;
    }
    $entity_url = $entity->toUrl()->toString();

    // Get the entities address field.
    if (!$address = $this->buildAddress($entity, $data->field_address)) {
      return;
    }

    // Get the address's geo coordinates from Google APIs. Don't send the second
    // line of the address, which causes too many APPROXIMATE matches.
    $googlemap_url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address);
    $response = $this->httpClient->get($googlemap_url);
    $status_code = $response->getStatusCode();
    if ($status_code != 200) {
      throw new SuspendQueueException(t('HTTP %type/%id %status_code', [
        '%type' => $data->entity_type,
        '%id' => $data->entity_id,
        '%status_code' => $status_code,
      ]));
    }
    $body = $response->getBody();
    $json = json_decode($body);
    if (empty($json->status) || $json->status != 'OK') {
      // If exceeding the rate limit, requeue.
      if (!empty($json) && $json->status == 'OVER_QUERY_LIMIT') {
        \Drupal::logger('geolocationqueue')->warning('%status %entity_url %googlemap_url', [
          '%status' => $json->status,
          '%entity_url' => $entity_url,
          '%googlemap_url' => $googlemap_url,
        ]);
        throw new RequeueException();
      }
      throw new \UnexpectedValueException(t('JSON %type/%id %json', [
        '%type' => $data->entity_type,
        '%id' => $data->entity_id,
        '%json' => $body,
      ]));
    }
    $result = reset($json->results);
    $match = $result->geometry->location_type;
    $coordinates = $result->geometry->location;

    // Save the coordinates.
    // Save any match if no coordinates exist.
    // Save changed matches on exact 'ROOFTOP' match.
    $field_name = $data->field_coordinates;
    $lat = $entity->{$field_name}->lat;
    $lng = $entity->{$field_name}->lng;
    $good_match = in_array($match, ['ROOFTOP', 'GEOMETRIC_CENTER']);
    if ((!$good_match && (!$lat || !$lng)) || ($good_match && ($lat != $coordinates->lat || $lng != $coordinates->lng))) {
      $entity->{$field_name}->lat = $coordinates->lat;
      $entity->{$field_name}->lng = $coordinates->lng;
      $entity->save();
    }
  }

  /**
   * Return a string representation of the entities address field.
   *
   * @param EntityInterface $entity
   *   The entity.
   * @param string $field_address
   *   The Drupal field name of the address field.
   */
  protected function buildAddress(EntityInterface $entity, $field_address) {
    if ($address = $entity->get($field_address)->first()) {
      // @todo: don't make this US-centric. Ese a renderer. The problem is that
      // the render adds HTML markup, with no commas separating the fields.
      return implode(', ', [
        $address->address_line1,
        $address->locality,
        preg_replace('/^US-/', '', $address->administrative_area),
        $address->postal_code,
        $address->country_code,
      ]);
    }

    return NULL;
  }

}
