<?php

namespace Drupal\bridtv\Plugin\Field\FieldType;

use Drupal\bridtv\Field\BridtvVideoItemInterface;
use Drupal\bridtv\BridEmbeddingInstance;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the reference to Brid.TV instances with custom player settingss.
 *
 * @FieldType(
 *   id = "bridtv_reference",
 *   label = @Translation("Brid.TV Video reference"),
 *   description = @Translation("Stores the reference to a Brid.TV video and exposes custom player settings."),
 *   category = @Translation("Reference"),
 *   default_widget = "bridtv_reference_autocomplete",
 *   default_formatter = "bridtv_js",
 *   list_class = "\Drupal\bridtv\Field\BridtvReferenceFieldItemList",
 * )
 */
class BridtvReferenceItem extends EntityReferenceItem implements BridtvVideoItemInterface {

  /**
   * The Brid.TV video instance.
   *
   * @var \Drupal\bridtv\BridEmbeddingInstance
   */
  protected $embeddingInstance = NULL;

  /**
   * {@inheritdoc}
   */
  public function getBridEmbeddingInstance() {
    if (!isset($this->embeddingInstance) && !$this->isEmpty()) {
      try {
        $entity = $this->get('entity')->getValue();
      }
      catch (\Exception $e) {
        $this->embeddingInstance = FALSE;
      }
      if (isset($entity)) {
        if (!($player_id = $this->get('player')->getValue())) {
          $resolver = $this->getBridEntityResolver();
          $items = $resolver->getFieldItemList($entity);
          if ($items->isEmpty() || !($player_id = $items->first()->get('player')->getValue())) {
            $negotiator = $this->getBridtvNegotiator();
            $player_id = $negotiator->getDefaultPlayerId();
          }
        };
        $this->embeddingInstance = new BridEmbeddingInstance($entity, $player_id);
      }
    }
    return $this->embeddingInstance ? $this->embeddingInstance : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
        'target_type' => 'media',
      ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
        'handler' => 'default:media',
        'handler_settings' => [
          'target_bundles' => ['bridtv'],
          'sort' => ['field' => 'name'],
          'auto_create' => FALSE,
        ],
      ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
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
    $schema = parent::schema($field_definition);
    $schema['columns']['player'] = [
      'type' => 'int',
      'size' => 'normal',
      'unsigned' => TRUE,
    ];
    $schema['columns']['settings'] = [
      'type' => 'blob',
      'size' => 'normal',
      'serialize' => TRUE,
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (isset($values['settings']) && is_string($values['settings'])) {
      // Unserialize the values.
      $values['settings'] = unserialize($values['settings']);
    }
    parent::setValue($values, $notify);
  }

  /**
   * @return \Drupal\bridtv\BridInfoNegotiator
   *   The Brid.TV negotiator.
   */
  protected function getBridtvNegotiator() {
    return \Drupal::service('bridtv.negotiator');
  }

  /**
   * Get the entity resolver.
   *
   * @return \Drupal\bridtv\BridEntityResolver
   *   The entity resolver.
   */
  protected function getBridEntityResolver() {
    return \Drupal::service('bridtv.entities');
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return parent::isEmpty() && empty($this->get('player')->getValue()) && empty($this->get('settings')->getValue());
  }

  /**
   * {@inheritdoc}
   */
  public function hasNewEntity() {
    return !$this->isEmpty() && $this->target_id === NULL && isset($this->entity) && $this->entity->isNew();
  }

}
