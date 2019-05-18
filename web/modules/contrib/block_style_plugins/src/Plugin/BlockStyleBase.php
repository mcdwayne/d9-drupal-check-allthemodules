<?php

namespace Drupal\block_style_plugins\Plugin;

use Drupal\block_style_plugins\IncludeExcludeStyleTrait;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Base class for Block style plugins.
 */
abstract class BlockStyleBase extends PluginBase implements BlockStyleInterface, ContainerFactoryPluginInterface {

  use IncludeExcludeStyleTrait;

  /**
   * Plugin ID for the Block being configured.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * Plugin instance for the Block being configured.
   *
   * @var object
   */
  protected $blockPlugin;

  /**
   * Bundle type for 'Block Content' blocks.
   *
   * @var string
   */
  protected $blockContentBundle;

  /**
   * Instance of the Entity Repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Instance of the Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct method for BlockStyleBase.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   An Entity Repository instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   An Entity Type Manager instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityRepositoryInterface $entityRepository, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Store our dependencies.
    $this->entityRepository = $entityRepository;
    $this->entityTypeManager = $entityTypeManager;
    // Store the plugin ID.
    $this->pluginId = $plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Set configuration if this is the Layout Builder.
    if (isset($form['#form_id']) && $form['#form_id'] == 'block_style_plugins_layout_builder_configure_styles') {
      $values = $form_state->getValues();
      if ($values) {
        $this->setConfiguration($values);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareForm(array $form, FormStateInterface $form_state) {
    // Get the current block config entity.
    /** @var \Drupal\block\Entity\Block $entity */
    $entity = $form_state->getFormObject()->getEntity();

    // Set properties and configuration.
    $this->blockPlugin = $entity->getPlugin();
    $this->setBlockContentBundle();

    // Find the plugin ID or block content bundle id.
    $plugin_id = $this->blockPlugin->getPluginId();
    if ($this->blockContentBundle) {
      $plugin_id = $this->blockContentBundle;
    }

    // Check to see if this should only apply to includes or if it has been
    // excluded.
    if ($this->allowStyles($plugin_id, $this->pluginDefinition)) {

      // Create a fieldset to contain style fields.
      if (!isset($form['block_styles'])) {
        $form['block_styles'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Block Styles'),
          '#collapsible' => FALSE,
          '#collapsed' => FALSE,
          '#weight' => 0,
        ];
      }

      $styles = $entity->getThirdPartySetting('block_style_plugins', $this->pluginId, []);
      $this->setConfiguration($styles);

      // Create containers to place each plugin style settings into the styles
      // fieldset.
      $form['third_party_settings']['block_style_plugins'][$this->pluginId] = [
        '#type' => 'container',
        '#group' => 'block_styles',
      ];

      // Allow plugins to add field elements to this form.
      $subform_state = SubformState::createForSubform($form['third_party_settings']['block_style_plugins'][$this->pluginId], $form, $form_state);
      $form['third_party_settings']['block_style_plugins'][$this->pluginId] += $this->buildConfigurationForm($form['third_party_settings']['block_style_plugins'][$this->pluginId], $subform_state);

      // Allow plugins to alter this form.
      $form = $this->formAlter($form, $form_state);

      // Add form Validation.
      $form['#validate'][] = [$this, 'validateForm'];

      // Add the submitForm method to the form.
      array_unshift($form['actions']['submit']['#submit'], [$this, 'submitForm']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formAlter(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Adds block style specific validation handling for the block form.
   *
   * @param array $form
   *   The form definition array for the full block configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    // Allow plugins to manipulate the validateForm.
    $subform_state = SubformState::createForSubform($form['third_party_settings']['block_style_plugins'][$this->pluginId], $form, $form_state);
    $this->validateConfigurationForm($form['third_party_settings']['block_style_plugins'][$this->pluginId], $subform_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    // Allow plugins to manipulate the submitForm.
    $subform_state = SubformState::createForSubform($form['third_party_settings']['block_style_plugins'][$this->pluginId], $form, $form_state);
    $this->submitConfigurationForm($form['third_party_settings']['block_style_plugins'][$this->pluginId], $subform_state);
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $variables) {
    // Is the Layout Builder being used?
    $layout_builder = (empty($variables['elements']['#id'])) ? TRUE : FALSE;

    $styles = $this->getStylesFromVariables($variables);

    if ($styles) {
      // Layout Builder needs a '#'.
      $hash = ($layout_builder) ? '#' : '';

      // Add styles to the configuration array so that they can be accessed in a
      // preprocess $variables['configuration']['block_styles'] or in a twig
      // template as {{ configuration.block_styles.plugin_id.field_name }}.
      $variables[$hash . 'configuration']['block_styles'][$this->pluginId] = $styles;

      // Add each style value as a class.
      foreach ($styles as $class) {
        // Don't put a boolean from a checkbox as a class.
        if (is_int($class)) {
          continue;
        }

        // Ensure that we have a block id. If not, the Layout Builder is used.
        $variables[$hash . 'attributes']['class'][] = $class;
      }
    }

    return $variables;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function themeSuggestion(array $suggestions, array $variables) {
    return $suggestions;
  }

  /**
   * Set the block content bundle type.
   */
  public function setBlockContentBundle() {
    $base_id = $this->blockPlugin->getBaseId();
    $uuid = $this->blockPlugin->getDerivativeId();

    if ($base_id == 'block_content') {
      $plugin = $this->entityRepository->loadEntityByUuid('block_content', $uuid);

      if ($plugin) {
        $this->blockContentBundle = $plugin->bundle();
      }
    }
  }

  /**
   * Get styles for a block set in a preprocess $variables array.
   *
   * @param array $variables
   *   Block variables coming from a preprocess hook.
   *
   * @return array|false
   *   Return the styles array or FALSE
   */
  protected function getStylesFromVariables(array $variables) {
    // Ensure that we have a block id. If not, then the Layout Builder is used.
    if (empty($variables['elements']['#id'])) {
      $styles = $this->getConfiguration();

      // Style config might not be set if this is happening in a hook so we will
      // check if a block_styles variable is set and get the config.
      if (empty($styles) && isset($variables['elements']['#configuration']['block_styles'][$this->pluginId])) {
        $this->setConfiguration($variables['elements']['#configuration']['block_styles'][$this->pluginId]);
        $styles = $styles = $this->getConfiguration();
      }
    }
    else {
      // Load the block config entity.
      /** @var \Drupal\block\Entity\Block $block */
      $block = $this->entityTypeManager->getStorage('block')
        ->load($variables['elements']['#id']);
      $styles = $block->getThirdPartySetting('block_style_plugins', $this->pluginId);
      if ($styles) {
        $this->setConfiguration($styles);
      }
    }
    return $styles;
  }

}
