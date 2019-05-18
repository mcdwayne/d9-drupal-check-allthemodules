<?php

namespace Drupal\bridtv;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

/**
 * The Brid.TV information negotiator.
 *
 * This service delivers required information either from the
 * local storage, or from the Brid.TV service provider when
 * data is not yet existing in the local storage.
 */
class BridInfoNegotiator {

  /**
   * The Brid.TV settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * The Brid API consumer.
   *
   * @var \Drupal\bridtv\BridApiConsumer
   */
  protected $consumer;

  /**
   * The entity resolver.
   *
   * @var \Drupal\bridtv\BridEntityResolver
   */
  protected $entityResolver;

  /**
   * The key value storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * BridInfoNegotiator constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\bridtv\BridApiConsumer $consumer
   *   The Brid API consumer.
   * @param \Drupal\bridtv\BridEntityResolver $entity_resolver
   *   The entity resolver.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   *   The key value factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, BridApiConsumer $consumer, BridEntityResolver $entity_resolver, KeyValueFactoryInterface $kv_factory) {
    $this->settings = $config_factory->get('bridtv.settings');
    $this->consumer = $consumer;
    $this->entityResolver = $entity_resolver;
    $this->keyValue = $kv_factory->get('bridtv');
  }

  public function getVideoData($id, $decode = TRUE) {
    $resolver = $this->entityResolver;
    if ($entity = $resolver->getEntityForVideoId($id)) {
      if ($data = $resolver->getVideoData($entity, $decode)) {
        return $data;
      }
    }
    $consumer = $this->consumer;
    if ($consumer->isReady()) {
      return $decode ? $consumer->getDecodedVideoData($id) : $consumer->fetchVideoData($id);
    }
    return NULL;
  }

  public function getPlayersList($decode = TRUE) {
    if (!($value = $this->keyValue->get('players_list'))) {
      $consumer = $this->consumer;
      if ($consumer->isReady() && ($value = $consumer->fetchPlayersList())) {
        if (BridSerialization::decode($value)) {
          $this->keyValue->set('players_list', $value);
        }
      }
    }
    if ($value) {
      return $decode ? BridSerialization::decode($value) : $value;
    }
    return NULL;
  }

  public function getPlayersDataList($decode = TRUE) {
    if (!($value = $this->keyValue->get('players_data'))) {
      $consumer = $this->consumer;
      if ($consumer->isReady() && ($value = $consumer->fetchPlayersDataList())) {
        if (BridSerialization::decode($value)) {
          $this->keyValue->set('players_data', $value);
        }
      }
    }
    if ($value) {
      return $decode ? BridSerialization::decode($value) : $value;
    }
    return NULL;
  }

  public function getPlayersListOptions() {
    return $this->getPlayersList();
  }

  /**
   * Get the player sizes (width and height).
   *
   * @param int $id
   *   The player ID.
   *
   * @return array
   *   Either an array having width and height key,
   *   or an empty array if not available.
   */
  public function getPlayerSizes($id) {
    $key = 'player.' . $id . '.size';
    if (!($value = $this->keyValue->get($key))) {
      $value = [];
      if ($players = $this->getPlayersDataList()) {
        foreach ($players as $player_data) {
          if (!empty($player_data['Player']['id']) && ($id == $player_data['Player']['id'])) {
            if (!empty($player_data['Player']['width']) && !empty($player_data['Player']['height'])) {
              $value = [
                'width' => $player_data['Player']['width'],
                'height' => $player_data['Player']['height'],
              ];
              $this->keyValue->set($key, $value);
            }
            break;
          }
        }
      }
    }
    return $value;
  }

  /**
   * @return int|null
   */
  public function getDefaultPlayerId() {
    $player_id = (string) $this->settings->get('default_player');
    $players = $this->getPlayersListOptions();
    if ($player_id && isset($players[$player_id])) {
      return (int) $player_id;
    }
    if (!empty($players)) {
      $player_ids = array_keys($players);
      return reset($player_ids);
    }
    return NULL;
  }

}
