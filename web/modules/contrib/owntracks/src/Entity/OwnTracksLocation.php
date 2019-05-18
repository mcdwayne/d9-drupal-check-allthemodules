<?php

namespace Drupal\owntracks\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the owntracks_location entity.
 *
 * @ContentEntityType(
 *   id = "owntracks_location",
 *   label = @Translation("OwnTracks Location"),
 *   label_singular = @Translation("owntracks location"),
 *   label_plural = @Translation("owntracks locations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count owntracks location",
 *     plural = "@count owntracks locations"
 *   ),
 *   base_table = "owntracks_location",
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\owntracks\Form\OwnTracksEntityForm",
 *       "edit" = "Drupal\owntracks\Form\OwnTracksEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\owntracks\Access\OwnTracksEntityAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "Drupal\owntracks\OwnTracksLocationStorageSchema",
 *   },
 *   links = {
 *     "canonical" = "/owntracks_location/{owntracks_location}",
 *     "add-form" = "/owntracks_location/add",
 *     "edit-form" = "/owntracks_location/{owntracks_location}/edit",
 *     "delete-form" = "/owntracks_location/{owntracks_location}/delete",
 *   },
 *   admin_permission = "administer owntracks",
 *   field_ui_base_route = "entity.owntracks_location.admin_form",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "label" = "description",
 *   },
 * )
 */
class OwnTracksLocation extends OwnTracksEntityBase implements OwnTracksLocationInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['acc'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Accuracy'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('suffix', 'm')
      ->setSetting('unsigned', TRUE);

    $fields['alt'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Altitude'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('suffix', 'm');

    $fields['batt'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Battery level'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('suffix', '%')
      ->setSetting('unsigned', TRUE)
      ->setSetting('size', 'tiny')
      ->addPropertyConstraints('value', [
        'Range' => [
          'min' => 0,
          'max' => 100,
        ],
      ]);

    $fields['cog'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Heading'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('suffix', '°')
      ->setSetting('unsigned', TRUE)
      ->setSetting('size', 'small')
      ->addPropertyConstraints('value', [
        'Range' => [
          'min' => 0,
          'max' => 360,
        ],
      ]);

    $fields['event'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Event'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('allowed_values', ['enter' => 'Enter', 'leave' => 'Leave']);

    $fields['rad'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Radius'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('suffix', 'm')
      ->setSetting('unsigned', TRUE);

    $fields['t'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Trigger'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('allowed_values', [
        'c' => 'Circular',
        'b' => 'Beacon',
        'r' => 'Response',
        'u' => 'User',
        't' => 'Timer',
        'a' => 'Automatic',
        'p' => 'Ping',
        'v' => 'Frequent location',
      ])
      ->setDefaultValue('u');

    $fields['tid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tracker-ID'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vac'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Vertical accuracy'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('suffix', 'm')
      ->setSetting('unsigned', TRUE);

    $fields['vel'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Velocity'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('suffix', 'km/h')
      ->setSetting('unsigned', TRUE);

    $fields['p'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Pressure'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('suffix', 'kPa')
      ->setSetting('unsigned', TRUE);

    $fields['con'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Connection'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('allowed_values', [
        'w' => 'WiFi',
        'o' => 'Offline',
        'm' => 'Mobile',
      ]);

    return $fields;
  }

}
