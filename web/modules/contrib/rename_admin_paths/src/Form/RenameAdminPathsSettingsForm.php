<?php

namespace Drupal\rename_admin_paths\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RenameAdminPathsSettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  private $routeBuilder;

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'rename_admin_paths_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'rename_admin_paths.settings',
    ];
  }

  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Routing\RouteBuilderInterface $routeBuilder
   */
  public function __construct(ConfigFactoryInterface $configFactory, RouteBuilderInterface $routeBuilder) {
    parent::__construct($configFactory);

    $this->routeBuilder = $routeBuilder;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('rename_admin_paths.settings');
    $callbacks = new RenameAdminPathsCallbacks();

    $form['admin_path'] = array(
      '#type' => 'fieldset',
      '#title' => t('Rename admin path'),
    );

    $form['admin_path']['admin_path'] = array(
      '#type' => 'checkbox',
      '#title' => t('Rename admin path'),
      '#default_value' => $config->get('admin_path'),
      '#description' => t('If checked, "admin" will be replaced by the following term in admin path.')
    );

    $form['admin_path']['admin_path_value'] = array(
      '#type' => 'textfield',
      '#title' => t('Replace "admin" in admin path by'),
      '#default_value' => $config->get('admin_path_value'),
      '#description' => t('This value will replace "admin" in admin path.'),
      '#element_validate' => array(array($callbacks, 'validatePath')),
    );

    $form['user_path'] = array(
      '#type' => 'fieldset',
      '#title' => t('Rename user path'),
    );

    $form['user_path']['user_path'] = array(
      '#type' => 'checkbox',
      '#title' => t('Rename user path'),
      '#default_value' => $config->get('user_path'),
      '#description' => t('If checked, "user" will be replaced by the following term in user path.'),
    );

    $form['user_path']['user_path_value'] = array(
      '#type' => 'textfield',
      '#title' => t('Replace "user" in user path by'),
      '#default_value' => $config->get('user_path_value'),
      '#description' => t('This value will replace "user" in user path.'),
      '#element_validate' => array(array($callbacks, 'validatePath')),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save configuration
    $config = $this->configFactory()->getEditable('rename_admin_paths.settings');
    $config->set('admin_path', $form_state->getValue('admin_path'));
    $config->set('admin_path_value', $form_state->getValue('admin_path_value'));
    $config->set('user_path', $form_state->getValue('user_path'));
    $config->set('user_path_value', $form_state->getValue('user_path_value'));
    $config->save();

    // Rebuild routes
    $this->routeBuilder->rebuild();

    // Make sure we end up at the same form again
    $form_state->setRedirect('rename_admin_paths.admin');
  }
}
