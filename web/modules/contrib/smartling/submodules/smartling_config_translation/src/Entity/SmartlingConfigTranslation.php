<?php

/**
 * @file
 * Contains \Drupal\smartling\Entity\SmartlingSubmission.
 */

namespace Drupal\smartling_config_translation\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\smartling\SmartlingSubmissionInterface;

/**
 * Defines the smartling entity class.
 *
 * @ContentEntityType(
 *   id = "smartling_config_translation",
 *   label = @Translation("Smartling config translation"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   base_table = "smartling_config_translation",
 *   admin_permission = "use smartling entity translation",
 *   links = {
 *     "canonical" = "/smartling/{smartling_submission}",
 *   },
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   }
 * )
 */
class SmartlingConfigTranslation extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Smartling submission ID'))
      ->setDescription(t('The smartling submission entity ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Config name'))
      ->setDescription(t('Machine name for configuration entity.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH);

    $fields['bundle'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Config bundle'))
      ->setDescription(t('Machine name for configuration bundle.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Config label'))
      ->setDescription(t('Human-readable label for configuration entity.'))
      ->setSetting('is_ascii', FALSE)
      ->setSetting('max_length', EntityTypeInterface::BUNDLE_MAX_LENGTH);

    return $fields;
  }

}
