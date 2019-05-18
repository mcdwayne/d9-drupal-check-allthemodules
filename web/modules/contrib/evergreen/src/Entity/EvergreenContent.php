<?php

namespace Drupal\evergreen\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\evergreen\Entity\EvergreenContentInterface;

/**
 * Evergreen content entities that store configuration & details for content.
 *
 * When the evergreen module is configured for an entity, an evergreen_config
 * entity is created. This stores the "entity configuration" or detauls for
 * all content for that entity type. When content specific configurations are
 * necessary or when an entity has specific settings (status, expiry, the
 * expiration date, etc) then it needs an evergreen_content entity.
 *
 * @ContentEntityType(
 *   id = "evergreen_content",
 *   label = @Translation("Evergreen content settings"),
 *   base_table = "evergreen_content",
 *   admin_permission = "administer content",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\evergreen\Form\EvergreenContentForm",
 *       "edit" = "Drupal\evergreen\Form\EvergreenContentForm",
 *     },
 *     "views_data" = "Drupal\evergreen\EvergreenContentViewsData"
 *   },
 * )
 */
class EvergreenContent extends ContentEntityBase implements EvergreenContentInterface {

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id']->setLabel(t('Evergreen content ID'))
      ->setDescription(t('The evergreen content ID.'));

    $fields['entity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Entity'))
      ->setDescription(t('The content this relates to'));

    $fields['evergreen_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Type'))
      ->setDescription(t('The entity type this relates to'));

    $fields['evergreen_bundle'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Bundle'))
      ->setDescription(t('The bundle this relates to'));

    $fields['evergreen_status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Evergreen status'))
      ->setDescription(t('Whether or not this content expires'))
      ->setDefaultValue(EVERGREEN_STATUS_EVERGREEN);

    $fields['evergreen_expiry'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Evergreen expiration time'))
      ->setDescription(t('Timespan after an edit this content expires'));

    $fields['evergreen_expires'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Content expiration time'))
      ->setDescription(t('Timestamp when this content expires'));

    $fields['evergreen_reviewed'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Evergreen status reviewed'))
      ->setDescription(t('The time that the entity was last reviewed.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * Get the evergreen status for this content.
   */
  public function getEvergreenStatus() {
    return $this->get('evergreen_status')->value;
  }

  /**
   * Get the evergreen expiry time.
   *
   * The expiry time is the time this content is considered "fresh" between
   * review times.
   */
  public function getEvergreenExpiry() {
    return $this->get('evergreen_expiry')->value;
  }

  /**
   * Get the expiration date for this content.
   */
  public function getEvergreenExpires() {
    return $this->get('evergreen_expires')->value;
  }

  /**
   * Return the last time this was reviewed.
   */
  public function getEvergreenReviewed() {
    return $this->get('evergreen_reviewed')->value;
  }

  /**
   * Get the bundle
   */
  public function getEvergreenBundle() {
    return $this->get('evergreen_bundle')->value;
  }

  /**
   * Get the entity type.
   */
  public function getEvergreenEntityType() {
    return $this->get('evergreen_entity_type')->value;
  }

  /**
   * Mark the content as reviewed and update pertinent fields.
   */
  public function reviewed($time = NULL) {
    if (!$time) {
      $time = time();
    }
    $this->set('evergreen_reviewed', $time);
    $expiry = $this->get('evergreen_expiry')->first();
    if ($expiry) {
      $this->set('evergreen_expires', $time + $expiry->value);
    }
  }

  /**
   * Check if the content is considered evergreen (does not expire).
   */
  public function isEvergreen() {
    return $this->getEvergreenStatus() == EVERGREEN_STATUS_EVERGREEN;
  }

  /**
   * Check if the content has expired.
   */
  public function isExpired($time = NULL) {
    if (!$time) {
      $time = time();
    }
    if (!$this->isEvergreen()) {
      $expires = $this->get('evergreen_expires')->first();
      if ($expires && $expires->value < $time) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setChangedTime($timestamp) {
    $this->set('changed', $timestamp);
    return $this;
  }

}
