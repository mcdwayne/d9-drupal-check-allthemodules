<?php

namespace Drupal\ad_entity\Form;

use Drupal\ad_entity\Plugin\AdContextManager;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for building AdContext form elements.
 *
 * This is not a standalone form and won't work on its own.
 * It's usually used as a sub-form element inside content entity forms,
 * or inside the global settings form to define site-wide context.
 * Context data is being attached to field data like the ContextItem,
 * or to configuration like the global settings for Advertising entities.
 * This element class does nothing regards attaching the data.
 */
class AdContextElementBuilder {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * Contains the current values of user-defined context data.
   *
   * @var array
   */
  protected $contextValues;

  /**
   * The Advertising context manager.
   *
   * @var \Drupal\ad_entity\Plugin\AdContextManager
   */
  protected $contextManager;

  /**
   * The storage of Advertising entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $adEntityStorage;

  /**
   * Creates a new instance of the element builder.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return \Drupal\ad_entity\Form\AdContextElementBuilder
   *   The instance of the element builder.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager')->getStorage('ad_entity'), $container->get('ad_entity.context_manager'));
  }

  /**
   * AdContextElementBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $ad_entity_storage
   *   The storage of Advertising entities.
   * @param \Drupal\ad_entity\Plugin\AdContextManager $ad_context_manager
   *   The Advertising context manager.
   */
  public function __construct(EntityStorageInterface $ad_entity_storage, AdContextManager $ad_context_manager) {
    $this->adEntityStorage = $ad_entity_storage;
    $this->contextManager = $ad_context_manager;
    $this->clearValues();
  }

  /**
   * Builds the context form element.
   *
   * @param array $element
   *   The form element array.
   * @param array &$form
   *   The form array where the context is being configured.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form element array.
   */
  public function buildElement(array $element, array &$form, FormStateInterface $form_state) {
    $context_definitions = $this->contextManager->getDefinitions();
    $options = [];
    foreach ($context_definitions as $id => $definition) {
      $options[$id] = $definition['label'];
    }
    $selector = Crypt::randomBytesBase64(2);
    $element['#tree'] = TRUE;
    $element['context']['context_plugin_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Context type'),
      '#required' => FALSE,
      '#options' => $options,
      '#empty_value' => '',
      '#attributes' => ['data-context-selector' => $selector],
      '#default_value' => !empty($this->contextValues['plugin_id']) ? $this->contextValues['plugin_id'] : '',
      '#weight' => 10,
    ];

    $element['context']['context_settings'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['ad-entity-context-settings']],
      '#states' => [
        'invisible' => [
          'select[data-context-selector="' . $selector . '"]' => ['value' => ''],
        ],
      ],
      '#weight' => 20,
    ];

    /** @var \Drupal\ad_entity\Entity\AdEntityInterface[] $entities */
    $entities = $this->adEntityStorage->loadMultiple();
    $options = [];
    foreach ($entities as $entity) {
      $options[$entity->id()] = $entity->label();
    }
    $element['context']['apply_on'] = [
      '#type' => 'select',
      '#title' => $this->t('Apply on ads'),
      '#description' => $this->t('Choose none to apply this context on any ad which would appear.'),
      '#required' => FALSE,
      '#multiple' => TRUE,
      '#options' => $options,
      '#empty_value' => '',
      '#default_value' => !empty($this->contextValues['apply_on']) ? $this->contextValues['apply_on'] : [],
      '#weight' => 30,
      '#states' => [
        'invisible' => [
          'select[data-context-selector="' . $selector . '"]' => ['value' => ''],
        ],
      ],
    ];

    // Build the settings form elements for the context plugins.
    $settings_element = [];
    foreach ($context_definitions as $id => $definition) {
      $context_plugin = $this->contextManager->loadContextPlugin($id);
      $context_settings = !empty($this->contextValues['settings'][$id]) ? $this->contextValues['settings'][$id] : [];
      $settings_element[$id] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['ad-entity-context-' . $id]],
        '#states' => [
          'visible' => [
            'select[data-context-selector="' . $selector . '"]' => ['value' => $id],
          ],
        ],
      ];
      $settings_element[$id] += $context_plugin->settingsForm($context_settings, $form, $form_state);
    }
    $element['context']['context_settings'] += $settings_element;
    return $element;
  }

  /**
   * Set the value for the chosen context plugin.
   *
   * @param string $plugin_id
   *   The context plugin is to set.
   *
   * @return \Drupal\ad_entity\Form\AdContextElementBuilder
   *   The element builder itself.
   */
  public function setContextPluginValue($plugin_id) {
    $this->contextValues['plugin_id'] = $plugin_id;
    return $this;
  }

  /**
   * Set the settings value for a certain context.
   *
   * @param string $plugin_id
   *   The context plugin id the settings belong to.
   * @param string|array $settings
   *   The encoded or decoded settings for the context plugin.
   *
   * @return \Drupal\ad_entity\Form\AdContextElementBuilder
   *   The element builder itself.
   */
  public function setContextSettingsValue($plugin_id, $settings) {
    if (is_string($settings)) {
      $plugin = $this->contextManager->loadContextPlugin($plugin_id);
      $settings = $plugin::getJsonDecode($settings);
    }
    $this->contextValues['settings'][$plugin_id] = $settings;
    return $this;
  }

  /**
   * Set the apply on value.
   *
   * @param array $apply_on
   *   The Advertising entity ids to apply the context on.
   *
   * @return \Drupal\ad_entity\Form\AdContextElementBuilder
   *   The element builder itself.
   */
  public function setContextApplyOnValue(array $apply_on) {
    $this->contextValues['apply_on'] = $apply_on;
    return $this;
  }

  /**
   * Resets the form submission values.
   *
   * @return \Drupal\ad_entity\Form\AdContextElementBuilder
   *   The element builder itself.
   */
  public function clearValues() {
    $this->contextValues = [];
    return $this;
  }

  /**
   * Massages the form values into the format expected for field values.
   *
   * @param array $values
   *   The submitted form values.
   *
   * @return array
   *   An array of field values.
   */
  public function massageFormValues(array $values) {
    // Let the context plugin massage its settings for storage and output.
    if (!empty($values['context']['context_plugin_id'])) {
      $id = $values['context']['context_plugin_id'];
      if ($this->contextManager->hasDefinition($id)) {
        $context_plugin = $this->contextManager->loadContextPlugin($id);
        $context_settings = !empty($values['context']['context_settings'][$id]) ?
          $values['context']['context_settings'][$id] : [];
        $context_settings = $context_plugin->massageSettings($context_settings);
        $values['context']['context_settings'] = [$id => $context_settings];
      }
    }
    return $values;
  }

}
