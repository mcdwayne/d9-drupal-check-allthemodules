<?php

namespace Drupal\bridtv;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;

/**
 * Class for representing a specific video instance to embed.
 *
 * The instance consists of any known information
 * required for embedding the video on a page.
 * Its information can be altered any time, until
 * it's being finally displayed.
 */
class BridEmbeddingInstance {

  /**
   * The entity representaion of the Brid.TV video.
   *
   * @var \Drupal\Core\Entity\FieldableEntityInterface
   */
  protected $entity;

  /**
   * The currently used Brid.TV player id.
   *
   * @var int
   */
  protected $playerId;

  /**
   * In-memory cached values.
   *
   * @var array
   */
  protected $cached = [];

  protected $settings = [];

  /**
   * BridEmbeddingInstance constructor.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity representaion of the Brid.TV video.
   * @param int $player_id
   *   The Brid.TV player id.
   * @param array $settings
   *   (Optional) Custom settings to use.
   */
  public function __construct(FieldableEntityInterface $entity, $player_id, array $settings = []) {
    $this->setEntity($entity);
    $this->setPlayerId($player_id);
    $this->setSettings($settings);
  }

  /**
   * Get the entity representation of the Brid.TV video.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Set the entity representation of the Brid.TV video.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   */
  public function setEntity(FieldableEntityInterface $entity) {
    $this->entity = $entity;
    $this->cached = [];
  }

  /**
   * Get the Brid.TV video id.
   *
   * @return int|null
   *   The video id.
   */
  public function getVideoId() {
    return $this->get('video_id');
  }

  /**
   * Get the currently used player id.
   *
   * @return int|null
   *   The player id.
   */
  public function getPlayerId() {
    return $this->playerId;
  }

  /**
   * Set the Brid.TV player id to use.
   *
   * @param int $player_id
   *   The player id to use.
   */
  public function setPlayerId($player_id) {
    $this->playerId = $player_id;
  }

  public function getSettings() {
    return $this->settings;
  }

  /**
   * Set custom settings.
   *
   * @param array $settings
   */
  public function setSettings(array $settings) {
    $this->settings = $settings + [
      'show_desc' => TRUE,
      'format' => NULL,
      'width' => 16,
      'height' => 9,
    ];
  }

  /**
   * Get a field value from the Brid.TV item.
   *
   * @param $name
   *   The name of the value to get.
   *
   * @return mixed
   *   The value, if any.
   */
  public function get($name) {
    $entity = $this->entity;
    if (empty($this->cached[$name])) {
      $resolver = $this->getEntityResolver();
      $this->cached[$name] = FALSE;
      if (($resolver::ENTITY_TYPE === 'media') && ($entity->getEntityTypeId() === $resolver::ENTITY_TYPE)) {
        $this->cached[$name] = $entity->getType()->getField($entity, $name);
      }
      else {
        foreach ($entity->getFieldDefinitions() as $field => $definition) {
          if ($definition->getType() === $resolver::FIELD) {
            if (!$entity->get($field)->isEmpty()) {
              try {
                $this->cached[$name] = $entity->get($field)->first()->get($name)->getValue();
              }
              catch (MissingDataException $e) {}
            }
            break;
          }
        }
      }
    }
    return $this->cached[$name] ? $this->cached[$name] : NULL;
  }

  /**
   * Returns the description for being displayed.
   *
   * @return string
   *   The description as safe markup.
   */
  public function getDescriptionOutput() {
    if (!($text = $this->get('description'))) {
      $text = '';
    }
    if (!empty($this->settings['format'])) {
      $text = (string) check_markup($text, $this->settings['format']);
    }
    else {
      $text = trim(Xss::filter(strip_tags($text)));
    }
    return $text;
  }

  /**
   * @param null $enabled
   *
   * @return bool
   */
  public function isDescriptionOutputEnabled($enabled = NULL) {
    if (isset($enabled)) {
      $this->settings['show_desc'] = !empty($enabled);
    }
    return !empty($this->settings['show_desc']);
  }

  /**
   * @return \Drupal\bridtv\BridEntityResolver
   */
  protected function getEntityResolver() {
    return \Drupal::service('bridtv.entities');
  }

}
