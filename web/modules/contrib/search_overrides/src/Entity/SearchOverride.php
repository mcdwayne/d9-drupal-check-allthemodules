<?php

namespace Drupal\search_overrides\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the Search override entity.
 *
 * @ingroup search_overrides
 *
 * @ContentEntityType(
 *   id = "search_override",
 *   label = @Translation("Search override"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\search_overrides\Entity\SearchOverrideListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\search_overrides\Form\SearchOverrideForm",
 *       "add" = "Drupal\search_overrides\Form\SearchOverrideForm",
 *       "edit" = "Drupal\search_overrides\Form\SearchOverrideForm",
 *       "delete" = "Drupal\search_overrides\Form\SearchOverrideDeleteForm",
 *     },
 *     "access" = "Drupal\search_overrides\Entity\SearchOverrideAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\search_overrides\Entity\SearchOverrideHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "search_override",
 *   data_table = "search_override_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer search overrides",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "query",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/search/search_override/{search_override}",
 *     "add-form" = "/admin/config/search/search_override/add",
 *     "edit-form" = "/admin/config/search/search_override/{search_override}/edit",
 *     "delete-form" = "/admin/config/search/search_override/{search_override}/delete",
 *     "collection" = "/admin/config/search/search_override",
 *   },
 *   field_ui_base_route = "search_override.settings"
 * )
 */
class SearchOverride extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getQuery() {
    return $this->get('query')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setQuery($query) {
    $this->set('query', $query);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getElevated() {
    $ids = $this->getElevatedIds();
    $entities = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getElevatedIds() {
    return $this->iterateValues($this->get('elnid')->getValue(), 'target_id');
  }

  /**
   * {@inheritdoc}
   */
  public function getElevatedLabels() {
    $entities = $this->getElevated();
    $labels = [];
    foreach ($entities as $entity) {
      $labels[] = $entity->label();
    }
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getExcluded() {
    $ids = $this->getExcludedIds();
    $entities = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getExcludedIds() {
    return $this->iterateValues($this->get('exnid')->getValue(), 'target_id');
  }

  /**
   * {@inheritdoc}
   */
  protected function iterateValues($array_in, $return = 'entity') {
    $array_out = [];
    if ($array_in && is_array($array_in)) {
      foreach ($array_in as $value) {
        $array_out[] = $value[$return];
      }
    }
    return $array_out;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['query'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Query'))
      ->setDescription(t('The search term or phrase whose results will be changed.'))
      ->setSettings([
        'max_length' => 100,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->addConstraint('UniqueField', [
        'message' => 'An override for %value already exists.',
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['elnid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Promoted Nodes'))
      ->setDescription(t('Nodes that should appear at the top of the results.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'above',
        // 'type' => 'author',.
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => '',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['exnid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Excluded Nodes'))
      ->setDescription(t('Nodes that should NOT appear in the results.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'above',
        // 'type' => 'author',.
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => '',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    return $fields;
  }

}
