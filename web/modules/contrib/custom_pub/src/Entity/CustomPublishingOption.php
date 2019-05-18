<?php

/**
 * @file
 * Contains \Drupal\custom_pub\Entity\CustomPublishingOption.
 */

namespace Drupal\custom_pub\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\custom_pub\CustomPublishingOptionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Custom publishing option entity.
 *
 * @ConfigEntityType(
 *   id = "custom_publishing_option",
 *   label = @Translation("Custom publishing option"),
 *   handlers = {
 *     "list_builder" = "Drupal\custom_pub\CustomPublishingOptionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\custom_pub\Form\CustomPublishingOptionForm",
 *       "edit" = "Drupal\custom_pub\Form\CustomPublishingOptionForm",
 *       "delete" = "Drupal\custom_pub\Form\CustomPublishingOptionDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\custom_pub\CustomPublishingOptionHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "custom_publishing_option",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/content/custom_publishing_option/{custom_publishing_option}",
 *     "add-form" = "/admin/config/content/custom_publishing_option/add",
 *     "edit-form" = "/admin/config/content/custom_publishing_option/{custom_publishing_option}/edit",
 *     "delete-form" = "/admin/config/content/custom_publishing_option/{custom_publishing_option}/delete",
 *     "collection" = "/admin/config/content/custom_publishing_option"
 *   }
 * )
 */
class CustomPublishingOption extends ConfigEntityBase implements CustomPublishingOptionInterface {

  /**
   * The option ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The option label.
   *
   * @var string
   */
  protected $label;

  /**
   * The option description.
   * @var string
   */
  protected $description;

  /**
   * Return the description of this option.
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Commit the save as normal, and create or update corresponding field as necessary.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   * @param bool $update
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    $storage_definition = \Drupal::entityDefinitionUpdateManager()->getFieldStorageDefinition($this->id(), 'node');

    if (!isset($storage_definition)) {
      // create the field definition for the node
      $storage_definition = BaseFieldDefinition::create('boolean')
        ->setLabel(t('@label', ['@label' => $this->label()]))
        ->setDescription(t('@description', ['@description' => $this->getDescription()]))
        ->setRevisionable(TRUE)
        ->setTranslatable(TRUE)
        ->setDefaultValue(FALSE)
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions(
          'form',
          [
            'type' => 'boolean_checkbox',
            'settings' => [
              'display_label' => TRUE,
            ],
            'weight' => 20,
          ]
        );

      \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition($this->id(), 'node', 'custom_pub', $storage_definition);
    } else {
      // update the label and description on the definition
      $storage_definition
        ->setLabel(t('@label', ['@label' => $this->label()]))
        ->setDescription(t('@description', ['@description' => $this->getDescription()]));

      \Drupal::entityDefinitionUpdateManager()->updateFieldStorageDefinition($storage_definition);
    }

    \Drupal::cache('config')->deleteAll();
  }

  /**
   * Commit the delete as normal, and remove field definitions.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   * @param array $entities
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    $manager = \Drupal::entityDefinitionUpdateManager();

    foreach ($entities as $entity) {
      $storage_definition = $manager->getFieldStorageDefinition($entity->id(), 'node');

      if (isset($storage_definition)) {
        $manager->uninstallFieldStorageDefinition($storage_definition);
      }
    }

    \Drupal::cache('config')->deleteAll();
  }
}
