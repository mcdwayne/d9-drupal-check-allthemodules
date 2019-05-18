<?php

namespace Drupal\group_content_field\Plugin\Field\FieldType;

use Drupal;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\entity_reference_revisions\EntityNeedsSaveInterface;
use Drupal\group\Entity\GroupContent;

/**
 * Plugin implementation of the 'datetime' field type.
 *
 * @FieldType(
 *   id = "group_content_item",
 *   label = @Translation("Group content item"),
 *   description = @Translation("Manage GroupContent entities on target bundles edit pages."),
 *   default_widget = "group_select",
 *   default_formatter = "group_content_list",
 * )
 */
class GroupContentItem extends FieldItemBase {
  /**
   * Saves current memberships.
   */
  protected $entityGidsValues = [];
  protected $membershipLoader;
  protected $groupRole;
  protected $groupType;
  /**
   * @var \Drupal\group_content_field\Plugin\GroupContentManager
   */
  protected $pluginManager;
  protected $groupContent;
  /**
   * @var \Drupal\group_content_field\Plugin\GroupContentDecoratorInterface
   */
  protected $decoratorInstance;


  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->membershipLoader = \Drupal::service('group.membership_loader');
    $this->groupType = \Drupal::entityTypeManager()->getStorage('group_type');
    $this->groupContent = \Drupal::entityTypeManager()->getStorage('group_content');
    $this->pluginManager= \Drupal::service('plugin.manager.group_content_decorator');

    $plugin_type_default = $this->getSetting('plugin_type');
    if ($this->pluginManager->hasDefinition($plugin_type_default)) {
      $this->decoratorInstance = $this->pluginManager->createInstance($plugin_type_default, ['group_content_item' => $this]);
    }
  }

  /**
   * @inheritdoc
   *
   * TODO Values from DB its to easy for us.
   */
  public function getValue() {
    $parent_entity = $this->getParent()->getParent()->getValue();
    if (!empty($parent_entity->id()) && empty($this->values['from_widget']) && !empty($this->decoratorInstance)) {
      $this->entityGidsValues = $this->values['entity_gids'] = $this->decoratorInstance->getDefaultValues($parent_entity);
    }

    return parent::getValue();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'group_roles' => [],
      'group_type' => [],
      'plugin_type' => [],
      'plugin_enabler_id' => [],
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];
    $options = [];
    $group_types = $this->groupType->loadMultiple();
    $group_type_default = $form_state->getValue('settings')['group_type'] ?? $this->getSetting('group_type');
    foreach ($group_types as $group_type) {
      $options[$group_type->id()] = $group_type->label();
    }
    $element['group_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Group type'),
      '#options' => $options,
      '#default_value' => $group_type_default,
      '#required' => TRUE,
      '#ajax' => array(
        'callback' => 'Drupal\group_content_field\Plugin\Field\FieldType\GroupContentItem::updatePluginType',
        'wrapper' => 'edit-plugin-type-wrapper',
      ),
    ];
    if ($group_type_default) {
      $options = [];
      $plugin_type_default = $form_state->getValue('settings')['plugin_type'] ?? $this->getSetting('plugin_type');
      // TODO Ask for supported entities. E.g. Following supports only user entities.
      $plugin_types = $this->pluginManager->getAll($this);

      foreach ($plugin_types as $plugin_type) {
        $options[$plugin_type->getPluginId()] = $plugin_type->getLabel();
      }

      $element['plugin_type'] = [
        '#type' => 'radios',
        '#title' => $this->t('Plugin type'),
        '#options' => $options,
        '#default_value' => $plugin_type_default,
        '#required' => TRUE,
        '#ajax' => [
          'callback' => 'Drupal\group_content_field\Plugin\Field\FieldType\GroupContentItem::updatePluginType',
          'wrapper' => 'edit-plugin-type-wrapper',
        ],
      ];

      if ($plugin_type_default && $this->pluginManager->hasDefinition($plugin_type_default)) {
        $instance = $this->pluginManager->createInstance($plugin_type_default, ['group_content_item' => $this]);
        $element += $instance->fieldStorageSettings();
      }
    }

    $element['#prefix'] = '<div id="edit-plugin-type-wrapper">';
    $element['#suffix'] = '</div>';
    return $element;
  }

  public function updatePluginType($form, FormStateInterface $form_state) {
    return $form['settings'];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['entity_gids'] = MapDataDefinition::create()
      ->setLabel(t('Entity gids'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'entity_gids' => [
          'description' => 'Serialized array of gids.',
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    $parent_entity = $this->getParent()->getParent()->getValue();
    $value = $this->getValue();
    if ($update == FALSE) {
      if ($parent_entity->getRevisionId()) {
        $parent_entity->setNewRevision(FALSE);
      }
      // TODO Need user id here.
      $parent_entity->save();
    }
    elseif (!empty($value['from_widget'])) {
      $value = $this->getValue();
      $value_gid = $value['entity_gids'];

      $this->syncGroupContents($this->entityGidsValues, $value_gid, $parent_entity);
    }
  }

  /**
   * @param array $old
   *  Array with loaded from database groups.
   * @param array $new
   *  Array with changed groups
   * @param \Drupal\Core\Entity\ContentEntityBase $parent_entity
   *
   */
  private function syncGroupContents(array $old, array $new, ContentEntityBase $parent_entity) {
    foreach (array_diff($old, $new) as $delete_gid) {
      $this->decoratorInstance->removeMemberContent($parent_entity, $delete_gid);
    }

    foreach (array_diff($new, $old) as $add_gid) {
      $this->decoratorInstance->createMemberContent($parent_entity, $add_gid);
    }
  }

  /**
   * Returns the value of a field setting.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  public function getSetting($setting_name) {
    return $this->getFieldDefinition()->getSetting($setting_name);
  }

}
