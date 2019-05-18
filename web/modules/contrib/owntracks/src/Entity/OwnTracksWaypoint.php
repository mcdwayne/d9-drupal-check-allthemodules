<?php

namespace Drupal\owntracks\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the owntracks_waypoint entity.
 *
 * @ContentEntityType(
 *   id = "owntracks_waypoint",
 *   label = @Translation("OwnTracks Waypoint"),
 *   label_singular = @Translation("owntracks waypoint"),
 *   label_plural = @Translation("owntracks waypoints"),
 *   label_count = @PluralTranslation(
 *     singular = "@count owntracks waypoint",
 *     plural = "@count owntracks waypoints"
 *   ),
 *   base_table = "owntracks_waypoint",
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\owntracks\Form\OwnTracksEntityForm",
 *       "edit" = "Drupal\owntracks\Form\OwnTracksEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\owntracks\Access\OwnTracksEntityAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "Drupal\owntracks\OwnTracksWaypointStorageSchema",
 *   },
 *   links = {
 *     "canonical" = "/owntracks_waypoint/{owntracks_waypoint}",
 *     "add-form" = "/owntracks_waypoint/add",
 *     "edit-form" = "/owntracks_waypoint/{owntracks_waypoint}/edit",
 *     "delete-form" = "/owntracks_waypoint/{owntracks_waypoint}/delete",
 *   },
 *   admin_permission = "administer owntracks",
 *   field_ui_base_route = "entity.owntracks_waypoint.admin_form",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "label" = "description",
 *   },
 * )
 */
class OwnTracksWaypoint extends OwnTracksEntityBase implements OwnTracksWaypointInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['description']->setRequired(TRUE);

    $fields['rad'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Radius'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('suffix', 'm')
      ->setSetting('unsigned', TRUE);

    return $fields;
  }

}
