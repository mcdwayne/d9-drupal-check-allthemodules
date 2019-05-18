<?php

namespace Drupal\flyout_menu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\breakpoint\BreakpointManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a form for the flyout menu admin settings.
 */
class FlyoutMenuSettingsForm extends ConfigFormBase {

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The breakpoint manager.
   *
   * @var \Drupal\breakpoint\BreakpointManager
   */
  protected $breakpointManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, BreakpointManager $breakpoint_manager) {
    parent::__construct($config_factory);
    $this->config = $this->configFactory->getEditable('flyout_menu.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->breakpointManager = $breakpoint_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('breakpoint.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flyout_menu_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['flyout_menu.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the menu to use.'),
      '#default_value' => $this->config->get('menu'),
      '#options' => $this->getMenuOptions(),
      '#required' => TRUE,
    ];

    $form['position'] = [
      '#type' => 'select',
      '#options' => [
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
      ],
      '#title' => $this->t('Which side the mobile menu panel should slide out from'),
      '#default_value' => $this->config->get('position'),
      '#required' => TRUE,
    ];

    $form['breakpoint'] = [
      '#type' => 'select',
      '#title' => $this->t('Breakpoint'),
      '#description' => $this->t('Choose a breakpoint to trigger the desktop format menu at'),
      '#default_value' => $this->config->get('breakpoint'),
      '#options' => $this->getBreakpointOptions(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config
      ->set('menu', $values['menu'])
      ->set('position', $values['position'])
      ->set('breakpoint', $values['breakpoint'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get a list of available menus.
   *
   * @return array
   *   An array of menus keyed by name.
   */
  protected function getMenuOptions() {
    $menus = $this->entityTypeManager->getStorage('menu')->loadMultiple(NULL);

    $options = [];
    /** @var \Drupal\system\MenuInterface[] $menus */
    foreach ($menus as $menu) {
      $options[$menu->id()] = $menu->label();
    }

    return $options;
  }

  /**
   * Get breakpoints for the current theme.
   *
   * @return array
   *   An array of breakpoints.
   */
  protected function getBreakpointOptions() {
    $default_theme = $this->configFactory->get('system.theme')->get('default');
    $breakpoints = $this->breakpointManager->getBreakpointsByGroup($default_theme);

    $options = [];
    foreach ($breakpoints as $breakpoint) {
      $query = $breakpoint->getMediaQuery();
      $options[$query] = $query;
    }

    return $options;
  }

}
