<?php

namespace Drupal\onlyone\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class OnlyOneAdminSettings.
 */
class OnlyOneAdminSettings extends ConfigFormBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteBuilderInterface $route_builder) {
    parent::__construct($config_factory);

    $this->configFactory = $config_factory;
    $this->routeBuilder = $route_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'), $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['onlyone.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'onlyone_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['onlyone_new_menu_entry'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show configured content types in a new menu entry'),
      '#description' => $this->t("If you check this item a new menu entry named 'Add content (Only One)' will be created in Administration » Content, and all the configured content types to have Only One content will be moved there."),
      '#default_value' => $this->config('onlyone.settings')->get('onlyone_new_menu_entry'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Getting the value from the form.
    $onlyone_new_menu_entry_checked = $form_state->getValue('onlyone_new_menu_entry');
    // Getting the onlyone_new_menu_entry variable.
    $onlyone_new_menu_entry = $this->config('onlyone.settings')->get('onlyone_new_menu_entry');

    // Checking if we have or not changes in the form.
    if ($onlyone_new_menu_entry_checked == $onlyone_new_menu_entry) {
      $this->messenger()->addWarning($this->t("You don't have changes in the form."));
    }
    else {
      parent::submitForm($form, $form_state);
      // Saving the module configuration.
      $this->config('onlyone.settings')
        ->set('onlyone_new_menu_entry', $onlyone_new_menu_entry_checked)
        ->save();

      $this->routeBuilder->rebuild();
    }
  }

}
