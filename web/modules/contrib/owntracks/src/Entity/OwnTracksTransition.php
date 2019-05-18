<?php

namespace Drupal\owntracks\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the owntracks_transition entity.
 *
 * @ContentEntityType(
 *   id = "owntracks_transition",
 *   label = @Translation("OwnTracks Transition"),
 *   label_singular = @Translation("owntracks transition"),
 *   label_plural = @Translation("owntracks transitions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count owntracks transition",
 *     plural = "@count owntracks transitions"
 *   ),
 *   base_table = "owntracks_transition",
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\owntracks\Form\OwnTracksEntityForm",
 *       "edit" = "Drupal\owntracks\Form\OwnTracksEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\owntracks\Access\OwnTracksEntityAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "Drupal\owntracks\OwnTracksTransitionStorageSchema",
 *   },
 *   links = {
 *     "canonical" = "/owntracks_transition/{owntracks_transition}",
 *     "add-form" = "/owntracks_transition/add",
 *     "edit-form" = "/owntracks_transition/{owntracks_transition}/edit",
 *     "delete-form" = "/owntracks_transition/{owntracks_transition}/delete",
 *   },
 *   admin_permission = "administer owntracks",
 *   field_ui_base_route = "entity.owntracks_transition.admin_form",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "label" = "description",
 *   },
 * )
 */
class OwnTracksTransition extends OwnTracksEntityBase implements OwnTracksTransitionInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['acc'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Accuracy'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'settings' => ['scale' => 3],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('suffix', 'm')
      ->setSetting('precision', 10)
      ->setSetting('scale', 3)
      ->setRequired(TRUE)
      ->addPropertyConstraints('value', [
        'Range' => [
          'min' => 0,
        ],
      ]);

    $fields['event'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Event'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('allowed_values', ['enter' => 'Enter', 'leave' => 'Leave']);

    $fields['wtst'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Waypoint timestamp'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['waypoint'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Waypoint'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 0,
      ])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('target_type', 'owntracks_waypoint')
      ->setRequired(FALSE);

    $fields['t'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Trigger'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('allowed_values', [
        'c' => 'Circular',
        'b' => 'Beacon',
        'l' => 'Location',
      ]);

    $fields['tid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tracker-ID'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
