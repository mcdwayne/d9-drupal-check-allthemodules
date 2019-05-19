<?php

namespace Drupal\simplemeta\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the SimpleMeta entity.
 *
 * @ingroup simplemeta
 *
 * @ContentEntityType(
 *   id = "simplemeta",
 *   label = @Translation("SimpleMeta"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\simplemeta\SimplemetaEntityListBuilder",
 *     "views_data" = "Drupal\simplemeta\Entity\SimplemetaEntityViewsData",
 *     "translation" = "Drupal\simplemeta\SimplemetaEntityTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\simplemeta\Form\SimplemetaEntityForm",
 *       "add" = "Drupal\simplemeta\Form\SimplemetaEntityForm",
 *       "edit" = "Drupal\simplemeta\Form\SimplemetaEntityForm",
 *       "delete" = "Drupal\simplemeta\Form\SimplemetaEntityDeleteForm",
 *     },
 *     "access" = "Drupal\simplemeta\SimplemetaEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\simplemeta\SimplemetaEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "simplemeta",
 *   data_table = "simplemeta_field_data",
 *   fieldable = FALSE,
 *   translatable = TRUE,
 *   admin_permission = "administer simplemeta",
 *   entity_keys = {
 *     "id" = "sid",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/simplemeta/simplemeta/{simplemeta}",
 *     "add-form" = "/admin/content/simplemeta/simplemeta/add",
 *     "edit-form" = "/admin/content/simplemeta/simplemeta/{simplemeta}/edit",
 *     "delete-form" = "/admin/content/simplemeta/simplemeta/{simplemeta}/delete",
 *     "collection" = "/admin/content/simplemeta",
 *   }
 * )
 */
class SimplemetaEntity extends ContentEntityBase implements SimplemetaEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    return t('SimpleMeta @id', ['@id' => $this->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Path'))
      ->setDescription(t('Drupal path this SimpleMeta entity related to.'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ]
      )
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fit'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Fit'))
      ->setDescription(t('A numeric representation of how specific the path is.'));

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('Serialized array of metadata.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    $path = $this->get('path')->get(0)->getValue();
    $fit = static::calculateFit($path);
    $this->get('fit')->set(0, $fit);

    parent::preSave($storage);
  }

  /**
   * Calculate how much specific the path is.
   *
   * @param string $path
   *   Page path to calculate a fit for.
   *
   * @return int
   *   A numeric representation of how specific the path is.
   */
  public static function calculateFit($path) {
    $fit = 0;
    $parts = explode('/', trim($path, '/'));
    $number_parts = count($parts);
    $slashes = $number_parts - 1;
    foreach ($parts as $k => $part) {
      if ($part != '*') {
        $fit |= 1 << ($slashes - $k);
      }
    }
    return $fit;
  }

}
