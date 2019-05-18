<?php

namespace Drupal\entity_reference_uuid_test\Entity;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Test entity two entity.
 *
 * @ingroup entity_reference_uuid_test
 *
 * @ContentEntityType(
 *   id = "test_entity_two",
 *   label = @Translation("Test entity two"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\entity_reference_uuid_test\Entity\TestEntityTwoViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\entity_reference_uuid_test\Form\TestEntityTwoForm",
 *       "add" = "Drupal\entity_reference_uuid_test\Form\TestEntityTwoForm",
 *       "edit" = "Drupal\entity_reference_uuid_test\Form\TestEntityTwoForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\entity_reference_uuid_test\TestEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "test_entity_two",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/test_entity_two/{test_entity_two}",
 *     "add-form" = "/admin/structure/test_entity_two/add",
 *     "edit-form" = "/admin/structure/test_entity_two/{test_entity_two}/edit",
 *     "delete-form" = "/admin/structure/test_entity_two/{test_entity_two}/delete",
 *   }
 * )
 */
class TestEntityTwo extends ContentEntityBase implements EntityPublishedInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Test entity two entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['entity_one_ref'] = BaseFieldDefinition::create('entity_reference_uuid')
      ->setLabel(t('A test_entity_one'))
      ->setDescription(t('The test_entity_one.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'test_entity_one')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => NULL])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['node_one_ref'] = BaseFieldDefinition::create('entity_reference_uuid')
      ->setLabel(t('A node'))
      ->setDescription(t('The nodes'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['test_nodetype_one', 'test_nodetype_two', 'test_nodetype_chemical']])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Test entity one is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published = NULL) {
    if ($published !== NULL) {
      @trigger_error('The $published parameter is deprecated since version 8.3.x and will be removed in 9.0.0.', E_USER_DEPRECATED);
      $value = (bool) $published;
    }
    else {
      $value = TRUE;
    }
    $this->set('status', $value);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUnpublished() {
    $this->set('status', FALSE);

    return $this;
  }

}
