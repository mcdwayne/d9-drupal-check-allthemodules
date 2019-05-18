<?php

namespace Drupal\panels_extended\Form;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Display\VariantManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefinition;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\panels\Plugin\DisplayBuilder\DisplayBuilderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for configuration values related to this module.
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * Name of the configuration which is edited.
   */
  const CFG_NAME = 'panels_extended.settings';

  const CFG_DEFAULT_DISPLAY_VARIANT = 'default_display_variant';

  const CFG_EXCLUDE_DISPLAY_BUILDERS = 'exclude_display_builders';

  const CFG_DEFAULT_DISPLAY_BUILDER = 'default_display_builder';

  const CFG_EXCLUDE_LAYOUTS = 'exclude_layout_providers';

  const CFG_EXCLUDE_BLOCKS = 'exclude_blocks_providers';

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The variant manager.
   *
   * @var \Drupal\Core\Display\VariantManager
   */
  protected $variantManager;

  /**
   * The display builder manager.
   *
   * @var \Drupal\panels\Plugin\DisplayBuilder\DisplayBuilderManagerInterface
   */
  protected $displayBuilderManager;

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutManager;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Display\VariantManager $variant_manager
   *   The variant manager.
   * @param \Drupal\panels\Plugin\DisplayBuilder\DisplayBuilderManagerInterface $display_builder_manager
   *   The display builder manager.
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layout_manager
   *   The layout plugin manager.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler,
    VariantManager $variant_manager,
    DisplayBuilderManagerInterface $display_builder_manager,
    LayoutPluginManagerInterface $layout_manager,
    BlockManagerInterface $block_manager
  ) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->variantManager = $variant_manager;
    $this->displayBuilderManager = $display_builder_manager;
    $this->layoutManager = $layout_manager;
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('plugin.manager.display_variant'),
      $container->get('plugin.manager.panels.display_builder'),
      $container->get('plugin.manager.core.layout'),
      $container->get('plugin.manager.block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panels_extended_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CFG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config(self::CFG_NAME);

    $variants = [];
    foreach ($this->variantManager->getDefinitions() as $pluginId => $definition) {
      if (in_array($pluginId, ['simple_page', 'block_page'])) {
        /* @see \Drupal\page_manager_ui\Form\PageGeneralForm::buildForm why excluding these. */
        continue;
      }
      $variants[$pluginId] = $definition['admin_label'] . ' - (' . $definition['provider'] . ')';
    }
    $form[self::CFG_DEFAULT_DISPLAY_VARIANT] = [
      '#title' => $this->t('Set default display variant'),
      '#type' => 'select',
      '#options' => $variants,
      '#default_value' => $config->get(self::CFG_DEFAULT_DISPLAY_VARIANT) ?: NULL,
    ];

    $displayBuilders = $this->displayBuilderManager->getDefinitions();
    $dbList = array_combine(array_keys($displayBuilders), array_map(function ($v) {
      return $v['label'] . ' - (' . $v['provider'] . ')';
    }, $displayBuilders));
    $form[self::CFG_EXCLUDE_DISPLAY_BUILDERS] = [
      '#title' => $this->t('Hide display builder(s) for extended panels variant'),
      '#type' => 'checkboxes',
      '#options' => $dbList,
      '#default_value' => $config->get(self::CFG_EXCLUDE_DISPLAY_BUILDERS) ?: [],
    ];
    $form[self::CFG_DEFAULT_DISPLAY_BUILDER] = [
      '#title' => $this->t('Default display builder for extended panels variant'),
      '#type' => 'select',
      '#options' => $dbList,
      '#default_value' => $config->get(self::CFG_DEFAULT_DISPLAY_BUILDER) ?: NULL,
    ];

    $layoutProviders = array_values(array_unique(array_map(function (LayoutDefinition $layout) {
      return $layout->getProvider();
    }, $this->layoutManager->getDefinitions())));
    natcasesort($layoutProviders);

    $form[self::CFG_EXCLUDE_LAYOUTS] = [
      '#title' => $this->t('Hide layouts from module(s):'),
      '#description' => $this->t('By selecting a module, the provided layouts will not be shown.'),
      '#type' => 'checkboxes',
      '#options' => array_combine($layoutProviders, $layoutProviders),
      '#default_value' => $config->get(self::CFG_EXCLUDE_LAYOUTS) ?: [],
    ];

    $blockProviders = array_values(array_unique(array_map(function (array $definition) {
      return $definition['provider'];
    }, $this->blockManager->getDefinitions())));
    natcasesort($blockProviders);

    $form[self::CFG_EXCLUDE_BLOCKS] = [
      '#title' => $this->t('Hide block from module(s):'),
      '#description' => $this->t('By selecting a module, the provided blocks will not be shown.'),
      '#type' => 'checkboxes',
      '#options' => array_combine($blockProviders, $blockProviders),
      '#default_value' => $config->get(self::CFG_EXCLUDE_BLOCKS) ?: [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $excludeDisplayBuilders = $form_state->getValue(self::CFG_EXCLUDE_DISPLAY_BUILDERS);
    if (!array_search(FALSE, $excludeDisplayBuilders)) {
      $form_state->setErrorByName(self::CFG_EXCLUDE_DISPLAY_BUILDERS, $this->t('Not allowed to exclude all.'));
    }
    $defaultDisplayBuilder = $form_state->getValue(self::CFG_DEFAULT_DISPLAY_BUILDER);
    if (!isset($excludeDisplayBuilders[$defaultDisplayBuilder]) || $excludeDisplayBuilders[$defaultDisplayBuilder] !== 0) {
      $form_state->setErrorByName(self::CFG_DEFAULT_DISPLAY_BUILDER, $this->t('The default display builder is disabled'));
    }

    $excludeLayoutModules = $form_state->getValue(self::CFG_EXCLUDE_LAYOUTS);
    if (!array_search(FALSE, $excludeLayoutModules)) {
      $form_state->setErrorByName(self::CFG_EXCLUDE_LAYOUTS, $this->t('Not allowed to exclude all.'));
    }

    $excludeBlockModules = $form_state->getValue(self::CFG_EXCLUDE_BLOCKS);
    if (!array_search(FALSE, $excludeBlockModules)) {
      $form_state->setErrorByName(self::CFG_EXCLUDE_BLOCKS, $this->t('Not allowed to exclude all.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config(self::CFG_NAME)
      ->set(self::CFG_DEFAULT_DISPLAY_VARIANT, $form_state->getValue(self::CFG_DEFAULT_DISPLAY_VARIANT))
      ->set(self::CFG_EXCLUDE_DISPLAY_BUILDERS, array_filter($form_state->getValue(self::CFG_EXCLUDE_DISPLAY_BUILDERS)))
      ->set(self::CFG_DEFAULT_DISPLAY_BUILDER, $form_state->getValue(self::CFG_DEFAULT_DISPLAY_BUILDER))
      ->set(self::CFG_EXCLUDE_LAYOUTS, array_filter($form_state->getValue(self::CFG_EXCLUDE_LAYOUTS)))
      ->set(self::CFG_EXCLUDE_BLOCKS, array_filter($form_state->getValue(self::CFG_EXCLUDE_BLOCKS)))
      ->save();
  }

}
