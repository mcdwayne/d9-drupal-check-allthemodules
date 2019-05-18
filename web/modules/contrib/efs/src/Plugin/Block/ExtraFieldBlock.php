<?php

namespace Drupal\efs\Plugin\Block;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\efs\ExtraFieldFormatterPluginManager;
use Drupal\layout_builder\Plugin\Block\ExtraFieldBlock as BaseBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Override layout_builder extra-field block plugin.
 *
 * This plugin allow EFS extra-fields to be configured through the UI
 * when layout_builder is enabled for given entity. The base block
 * class supports only core's extra-field which does not have a
 * formatter thus cannot be configured.
 *
 * This plugin is not auto-discoverable, since can only be registered
 * if the layout_builder module is enabled. Therefore we implement
 * hook_block_alter to register this class for the 'extra_field_block'
 * block plugin.
 *
 * @see \Drupal\Core\Block\BlockManager
 * @see efs_block_alter()
 *
 * @phpcs:disable Drupal.WhiteSpace.ScopeIndent.IncorrectExact
 */
class ExtraFieldBlock extends BaseBlock {

  public const EXTRA_FIELD_ENTITY_ID = 'extra_field';

  public const VIEW_DISPLAY_CONTEXT = 'display';

  /**
   * The storage service.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  private $storage;

  /**
   * The extra-field formatter manager service.
   *
   * @var \Drupal\efs\ExtraFieldFormatterPluginManager
   */
  private $pluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    ExtraFieldFormatterPluginManager $efs_formatter_manager) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager,
      $entity_field_manager
    );
    $this->storage = $this->entityTypeManager->getStorage(self::EXTRA_FIELD_ENTITY_ID);
    $this->pluginManager = $efs_formatter_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.efs.formatters')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $extra_field = $this->getExtraField();

    if ($extra_field === NULL) {
      return $form;
    }

    /** @var \Drupal\efs\ExtraFieldFormatterPluginBase $plugin */
    $plugin = $this->pluginManager->createInstance($extra_field->getPlugin());
    $settings = $extra_field->getSettings();
    $settings += $plugin::defaultContextSettings($extra_field->getContext());
    $plugin->setSettings($settings);

    // Load the view display entity so we can rebuild it's form
    // in order to extract the extra-field form.
    $entity_view_display = $this->entityTypeManager->getStorage('entity_view_display')
      ->load($extra_field->getTargetEntityTypeId() . '.' . $extra_field->getBundle() . '.' . $extra_field->getMode());
    /** @var \Drupal\field_ui\Form\EntityDisplayFormBase $form_class */
    $form_class = $this->entityTypeManager->getFormObject($entity_view_display->getEntityTypeId(), 'edit');
    $form['extra_field_settings'] = $plugin
      ->settingsForm($form_class, $form, $form_state, $extra_field, $extra_field->getName());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $extra_field_settings = $form_state->getValue('extra_field_settings');
    $extra_field = $this->getExtraField();

    if ($extra_field !== NULL) {
      $extra_field->setSettings($extra_field_settings);
      $extra_field->save();
    }

    parent::blockSubmit($form, $form_state);
  }

  /**
   * Get extra-field entity.
   *
   * @return \Drupal\efs\Entity\ExtraFieldInterface|null
   *   The extra-field entity instance or NULL if cannot be loaded.
   */
  private function getExtraField() {
    [,
      $entity_type,
      $entity_bundle,
      $extra_field_name,
    ] = explode(self::DERIVATIVE_SEPARATOR, $this->pluginId);

    $extra_fields = $this->entityFieldManager->getExtraFields($entity_type, $entity_bundle);
    $definition = NestedArray::getValue($extra_fields, [
      self::VIEW_DISPLAY_CONTEXT,
      $extra_field_name,
    ]);

    // If the extra-field definition cannot be retrieved
    // notify it and return the base form.
    if (empty($definition)) {
      trigger_error($this->t('Cannot recover extra-field definition for block ":block".', [
        ':block' => $this->pluginId,
      ]));

      return NULL;
    }

    /** @var \Drupal\efs\Entity\ExtraFieldInterface $extra_field */
    $extra_field = $this->storage->load($definition['id']);

    // If the extra-field entity cannot be loaded notify it
    // and return the base form.
    if ($extra_field === NULL) {
      trigger_error($this->t('Cannot load extra-field entity ":extra_field" for block ":block".', [
        ':extra_field' => $definition['id'],
        ':block' => $this->pluginId,
      ]));

      return NULL;
    }

    return $extra_field;
  }

}
