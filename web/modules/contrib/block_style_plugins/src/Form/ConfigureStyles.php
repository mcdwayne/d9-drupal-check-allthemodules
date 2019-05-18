<?php

namespace Drupal\block_style_plugins\Form;

use Drupal\block_style_plugins\Plugin\BlockStyleInterface;
use Drupal\block_style_plugins\Plugin\BlockStyleManager;
use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\layout_builder\Controller\LayoutRebuildTrait;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to configure styles.
 *
 * @internal
 */
class ConfigureStyles extends FormBase {

  use AjaxFormHelperTrait;
  use LayoutRebuildTrait;

  /**
   * The Block Styles Manager.
   *
   * @var \Drupal\block_style_plugins\Plugin\BlockStyleManager
   */
  protected $blockStyleManager;

  /**
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * The plugin form factory.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * The section storage.
   *
   * @var \Drupal\layout_builder\SectionStorageInterface
   */
  protected $sectionStorage;

  /**
   * The layout section delta.
   *
   * @var int
   */
  protected $delta;

  /**
   * The uuid of the block component.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The block styles plugin being configured.
   *
   * @var \Drupal\block_style_plugins\Plugin\BlockStyleInterface
   */
  protected $blockStyles;

  /**
   * Constructs a ConfigureStyles object.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_manager
   *   The plugin form manager.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Drupal\block_style_plugins\Plugin\BlockStyleManager $blockStyleManager
   *   The Block Style Manager.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository, PluginFormFactoryInterface $plugin_form_manager, ClassResolverInterface $class_resolver, BlockStyleManager $blockStyleManager) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
    $this->pluginFormFactory = $plugin_form_manager;
    $this->classResolver = $class_resolver;
    $this->blockStyleManager = $blockStyleManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('plugin_form.factory'),
      $container->get('class_resolver'),
      $container->get('plugin.manager.block_style.processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_style_plugins_layout_builder_configure_styles';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $uuid = NULL, $plugin_id = NULL) {
    $this->sectionStorage = $section_storage;
    $this->delta = $delta;
    $this->uuid = $uuid;

    $block_styles = $this->getComponent()->getThirdPartySetting('block_style_plugins', $plugin_id, []);

    $this->blockStyles = $this->blockStyleManager->createInstance($plugin_id);
    $this->blockStyles->setConfiguration($block_styles);

    $form['#tree'] = TRUE;
    $form['settings'] = [];
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $form['settings'] = $this->getPluginForm($this->blockStyles)->buildConfigurationForm($form['settings'], $subform_state);

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $block_styles ? $this->t('Update') : $this->t('Add Styles'),
      '#button_type' => 'primary',
    ];

    if ($this->isAjax()) {
      $form['actions']['submit']['#ajax']['callback'] = '::ajaxSubmit';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $this->getPluginForm($this->blockStyles)->validateConfigurationForm($form['settings'], $subform_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $this->getPluginForm($this->blockStyles)->submitConfigurationForm($form, $subform_state);

    $configuration = $configuration = $this->blockStyles->getConfiguration();
    $plugin_id = $this->blockStyles->getPluginId();

    $component = $this->getComponent();
    $component->setThirdPartySetting('block_style_plugins', $plugin_id, $configuration);

    $this->layoutTempstoreRepository->set($this->sectionStorage);
    $form_state->setRedirectUrl($this->sectionStorage->getLayoutBuilderUrl());
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    return $this->rebuildAndClose($this->sectionStorage);
  }

  /**
   * Retrieves the plugin form for a given block style.
   *
   * @param \Drupal\block_style_plugins\Plugin\BlockStyleInterface $blockStyles
   *   The block styles plugin.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   The plugin form for the condition.
   */
  protected function getPluginForm(BlockStyleInterface $blockStyles) {
    if ($blockStyles instanceof PluginWithFormsInterface) {
      return $this->pluginFormFactory->createInstance($blockStyles, 'configure');
    }
    return $blockStyles;
  }

  /**
   * Get the Section Component.
   *
   * @return \Drupal\layout_builder\SectionComponent
   *   The current Section Component.
   */
  public function getComponent() {
    return $this->sectionStorage->getSection($this->delta)->getComponent($this->uuid);
  }

}
