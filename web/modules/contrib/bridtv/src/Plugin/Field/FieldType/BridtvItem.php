<?php

namespace Drupal\bridtv\Plugin\Field\FieldType;

use Drupal\bridtv\BridEmbeddingInstance;
use Drupal\bridtv\Field\BridtvVideoItemInterface;
use Drupal\bridtv\BridSerialization;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin definition of the Brid.TV video item.
 *
 * @FieldType(
 *   id = "bridtv",
 *   label = @Translation("Brid.TV Video"),
 *   description = @Translation("Stores the information for a Brid.TV video, including any retrieved metadata."),
 *   category = @Translation("Media"),
 *   default_widget = "bridtv_id",
 *   default_formatter = "bridtv_js",
 *   module = "bridtv"
 * )
 */
class BridtvItem extends FieldItemBase implements BridtvVideoItemInterface {

  /**
   * The decoded data retrieved from Brid.TV.
   *
   * @var array
   */
  protected $decodedData;

  /**
   * The Brid.TV video instance to embed.
   *
   * @var \Drupal\bridtv\BridEmbeddingInstance
   */
  protected $embeddingInstance = NULL;

  /**
   * {@inheritdoc}
   */
  public function getBridEmbeddingInstance() {
    if (!isset($this->embeddingInstance)) {
      if (!($player_id = $this->get('player')->getValue())) {
        $negotiator = $this->getBridtvNegotiator();
        $player_id = $negotiator->getDefaultPlayerId();
      }
      $this->embeddingInstance = new BridEmbeddingInstance($this->getEntity(), $player_id);
    }
    return $this->embeddingInstance;
  }

  /**
   * Get the decoded data retrieved from Brid.TV.
   *
   * @return array|null
   *   The decoded data as array, or NULL if not given.
   */
  public function getBridApiData($decode = TRUE) {
    if ($this->isEmpty()) {
      return NULL;
    }
    if ($decode) {
      if (!isset($this->decodedData)) {
        $this->decodedData = BridSerialization::decode($this->get('data')->getValue());
      }
      return $this->decodedData ? $this->decodedData : NULL;
    }
    return $this->get('data')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['video_id'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('The video id at Brid.TV.'))
      ->setRequired(TRUE);
    $properties['title'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('The video title.'));
    $properties['description'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('The video description.'));
    $properties['publish_date'] = DataDefinition::create('datetime_iso8601')
      ->setLabel(new TranslatableMarkup('Date value'));
    $properties['data'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Retrieved metadata from Brid.TV about the video.'));
    $properties['player'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Brid.TV Player id'));
    $properties['settings'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('Custom settings'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [];
    $schema['columns'] = [
      'video_id' => [
        'type' => 'int',
        'size' => 'normal',
        'unsigned' => TRUE,
        'not null' => TRUE,
      ],
      'title' => [
        'type' => 'varchar',
        'length' => 187,
      ],
      'description' => [
        'type' => 'text',
        'size' => 'normal',
      ],
      'publish_date' => [
        'type' => 'varchar',
        'length' => 20,
      ],
      'data' => [
        'type' => 'text',
        'size' => 'medium',
      ],
      'player' => [
        'type' => 'int',
        'size' => 'normal',
        'unsigned' => TRUE,
      ],
      'settings' => [
        'type' => 'blob',
        'size' => 'normal',
        'serialize' => TRUE,
      ],
    ];
    $schema['indexes'] = [
      'i_video_id' => ['video_id'],
      'i_title' => ['title'],
    ];
    $schema['unique keys'] = [
      'u_video_id' => ['video_id'],
    ];
    return $schema;
  }

  /**
   * Get the negotiator service.
   *
   * @return \Drupal\bridtv\BridInfoNegotiator
   *   The Brid.TV negotiator.
   */
  protected function getBridtvNegotiator() {
    return \Drupal::service('bridtv.negotiator');
  }

}
