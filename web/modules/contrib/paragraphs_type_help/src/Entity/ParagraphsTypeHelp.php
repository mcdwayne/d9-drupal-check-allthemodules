<?php

namespace Drupal\paragraphs_type_help\Entity;

use Drupal\paragraphs_type_help\ParagraphsTypeHelpInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\user\UserInterface;

/**
 * Defines the paragraphs type entity class.
 *
 * @ContentEntityType(
 *   id = "paragraphs_type_help",
 *   label = @Translation("Paragraphs Type Help"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\paragraphs_type_help\ParagraphsTypeHelpAccessControlHandler",
 *     "list_builder" = "Drupal\paragraphs_type_help\ParagraphsTypeHelpListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\paragraphs_type_help\ParagraphsTypeHelpViewsData",
 *     "form" = {
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "default" = "Drupal\Core\Entity\ContentEntityForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   admin_permission = "administer paragraphs_type_help entity",
 *   base_table = "paragraphs_type_help",
 *   revision_table = "paragraphs_type_help_revision",
 *   data_table = "paragraphs_type_help_field_data",
 *   revision_data_table = "paragraphs_type_help_field_revision",
 *   show_revision_ui = TRUE,
 *   links = {
 *     "canonical" = "/admin/content/paragraphs-type-help/{paragraphs_type_help}",
 *     "delete-form" = "/admin/content/paragraphs-type-help/{paragraphs_type_help}/delete",
 *     "edit-form" = "/admin/content/paragraphs-type-help/{paragraphs_type_help}",
 *     "collection" = "/admin/content/paragraphs-type-help",
 *   },
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "label" = "label",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *     "published" = "status",
 *     "uid" = "uid",
 *     "weight" = "weight",
 *   },
 *   field_ui_base_route = "entity.paragraphs_type_help.admin_form",
 *   common_reference_target = TRUE,
 * )
 */
class ParagraphsTypeHelp extends ContentEntityBase implements ParagraphsTypeHelpInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['host_bundle'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Paragraph Type'))
      ->setDescription(t('The help will be displayed on the edit form of this paragraph type.'))
      ->setSetting('target_type', 'paragraphs_type')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -10,
      ]);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Admin label'))
      ->setDescription(t("The admin label of this help. Leave empty to auto-generate a default label."))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'weight' => -9,
      ]);

    $fields['host_form_mode'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Active Paragraph Form mode'))
      ->setDescription(t('This help will be displayed only on the selected form modes of the selected paragraph type. If "Default" is selected, then this help will be used on any form mode that does not have any defined help.'))
      ->setSetting('allowed_values_function', 'paragraphs_type_help_paragraph_form_mode_options')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue('default')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -6,
      ]);

    $fields['host_view_mode'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Active Paragraph View mode'))
      ->setDescription(t('This help will be displayed only on the selected view modes of the selected paragraph type. If "Default" is selected, then this help will be used on any view mode that does not have any defined help.'))
      ->setSetting('allowed_values_function', 'paragraphs_type_help_paragraph_view_mode_options')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -5,
      ]);

    // Published / status base fields.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    // Published checkbox.
    if (isset($fields['status'])) {
      $fields['status']
        ->setDisplayOptions('form', [
          'type' => 'boolean_checkbox',
          'weight' => 16,
          'settings' => [
            'display_label' => TRUE,
          ],
        ])
        ->setDisplayConfigurable('form', TRUE);
    }

    // Listing weight.
    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t("The weight controls the order of the help when rendered on the Paragraphs Type display and the order in admin lists. The lighter (smaller) numbers are ordered to the top of the list."))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'list_integer',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Revision fields.
    $fields['revision_log'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Revision log message'))
      ->setDescription(t('The log entry explaining the changes in this revision.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 25,
        'settings' => [
          'rows' => 4,
        ],
      ]);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the help was last edited.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['revision_created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Revision create time'))
      ->setDescription(t('The time that the current revision was created.'))
      ->setRevisionable(TRUE);

    $fields['revision_user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision user'))
      ->setDescription(t('The user ID of the author of the current revision.'))
      ->setSetting('target_type', 'user')
      ->setRevisionable(TRUE);

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getHostBundle() {
    return $this->host_bundle->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getHostBundleId() {
    if ($bundle = $this->getHostBundle()) {
      return $bundle->id();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getHostBundleLabel() {
    if ($bundle = $this->getHostBundle()) {
      return $bundle->label();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getHostFormMode() {
    return $this->get('host_form_mode')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getHostFormModeLabel() {
    if ($mode = $this->getHostFormMode()) {
      $options = paragraphs_type_help_paragraph_form_mode_options();
      return isset($options[$mode]) ? $options[$mode] : NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getHostViewMode() {
    return $this->get('host_view_mode')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getHostViewModeLabel() {
    if ($mode = $this->getHostViewMode()) {
      $options = paragraphs_type_help_paragraph_view_mode_options();
      return isset($options[$mode]) ? $options[$mode] : NULL;
    }
  }

  /**
   * Provide an auto-generated default label.
   *
   * @return string
   *   The untranslated label.
   */
  public function defaultLabel() {
    if ($host_bundle = $this->getHostBundleLabel()) {
      return $host_bundle;
    }

    return 'Unknown';
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->set('label', $label);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionCreationTime() {
    return $this->get('revision_created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionCreationTime($timestamp) {
    $this->set('revision_created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionUser() {
    return $this->get('revision_user')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionUser(UserInterface $account) {
    $this->set('revision_user', $account);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionUserId() {
    return $this->get('revision_user')->entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionUserId($user_id) {
    $this->set('revision_user', $user_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionLogMessage() {
    return $this->get('revision_log')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionLogMessage($revision_log_message) {
    $this->set('revision_log', $revision_log_message);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if (!$this->get('label')->value && ($default_label = $this->defaultLabel())) {
      $this->set('label', $default_label);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByProperties(array $values = []) {
    $entity_manager = \Drupal::entityManager();
    return $entity_manager->getStorage($entity_manager->getEntityTypeFromClass(get_called_class()))->loadByProperties($values);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadPublishedByProperties(array $values = []) {
    $values['status'] = 1;
    return static::loadByProperties($values);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadPublishedByHostBundle($host_bundle, $host_form_mode = NULL, $host_view_mode = NULL) {
    $values = [
      'host_bundle.target_id' => $host_bundle,
    ];

    if (!empty($host_form_mode)) {
      $values['host_form_mode'] = $host_form_mode;
    }

    if (!empty($host_view_mode)) {
      $values['host_view_mode'] = $host_view_mode;
    }

    return static::loadPublishedByProperties($values);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadPublishedByHostDisplay(EntityDisplayInterface $host_display) {
    $host_entity_type = $host_display->getTargetEntityTypeId();

    // Only paragraphs are supported.
    if ($host_entity_type !== 'paragraph') {
      return [];
    }

    $host_bundle = $host_display->getTargetBundle();
    // Exit if there is no host bundle.
    if (empty($host_bundle)) {
      return [];
    }

    // Determine display type and mode.
    $display_type = $host_display instanceof EntityViewDisplay ? 'view' : 'form';
    $display_mode = $host_display->getMode() ?: 'default';

    // Query provided display mode.
    if ($display_type === 'form') {
      $helps = static::loadPublishedByHostBundle($host_bundle, $display_mode);
    }
    else {
      $helps = static::loadPublishedByHostBundle($host_bundle, NULL, $display_mode);
    }

    // Fallback to default form mode for non-default queries.
    if (empty($helps) && $display_mode !== 'default') {
      if ($display_type === 'form') {
        $helps = static::loadPublishedByHostBundle($host_bundle, 'default');
      }
      else {
        $helps = static::loadPublishedByHostBundle($host_bundle, NULL, 'default');
      }
    }

    return $helps;
  }

}
