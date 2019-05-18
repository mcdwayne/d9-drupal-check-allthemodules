<?php

namespace Drupal\invite\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class InviteConfig.
 *
 * @package Drupal\invite\Form
 */
class InviteConfig extends ConfigFormBase {

  /**
   * Route provider.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routeProvider;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Routing\RouteProvider $routeProvider
   *   The route provider service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteProvider $routeProvider) {
    parent::__construct($config_factory);
    $this->routeProvider = $routeProvider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'invite.invite_config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'invite_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('invite.invite_config');

    $form['invite_expiration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Invite Expiration'),
      '#description' => $this->t('Enter the number of days before the invitation expires.'),
      '#maxlength' => 6,
      '#size' => 6,
      '#default_value' => $config->get('invite_expiration'),
    ];

    $form['accept_redirect'] = [
      '#type' => 'textfield',
      '#required' => FALSE,
      '#title' => $this->t('Accept Redirect'),
      '#description' => $this->t('The route the user will be redirected to when registering. Defaults to "user.register"'),
      '#default_value' => $config->get('accept_redirect'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $route_name = $form_state->getValue('accept_redirect');

    $route_exists = count($this->routeProvider->getRoutesByNames([$route_name])) === 1;

    if (!$route_exists) {
      $form_state->setErrorByName('accept_redirect', $this->t('Route "@route" does not exist.', ['@route' => $route_name]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('invite.invite_config')
      ->set('invite_expiration', $form_state->getValue('invite_expiration'))
      ->set('accept_redirect', $form_state->getValue('accept_redirect'))
      ->save();
  }

}
