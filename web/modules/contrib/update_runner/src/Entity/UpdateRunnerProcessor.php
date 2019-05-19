<?php

namespace Drupal\update_runner\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the update runner processor entity.
 *
 * @ConfigEntityType(
 *   id = "update_runner_processor",
 *   label = @Translation("Update runner processor"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\update_runner\UpdateRunnerProcessorListBuilder",
 *     "form" = {
 *       "add" = "Drupal\update_runner\Form\UpdateRunnerProcessorForm",
 *       "edit" = "Drupal\update_runner\Form\UpdateRunnerProcessorForm",
 *       "delete" = "Drupal\update_runner\Form\UpdateRunnerProcessorDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "update_runner_processor",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/update_runner/processors/{update_runner_processor}",
 *     "add-form" = "/admin/config/update_runner/processors/add",
 *     "edit-form" = "/admin/config/update_runner/processors/{update_runner_processor}/edit",
 *     "delete-form" = "/admin/config/update_runner/processors/{update_runner_processor}/delete",
 *     "collection" = "/admin/config/update_runner/processors"
 *   }
 * )
 */
class UpdateRunnerProcessor extends ConfigEntityBase implements ConfigEntityInterface {

  /**
   * The update runner processor ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The update runner processor label.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['plugin'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Plugin'))
      ->setDescription(t('Plugin'));

    $fields['data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Data'))
      ->setDescription(t('Data.'));

    return $fields;
  }

}
