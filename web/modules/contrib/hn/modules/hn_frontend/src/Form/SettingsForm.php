<?php

namespace Drupal\hn_frontend\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Class ConfigForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hn_frontend.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hn_frontend_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hn_frontend.settings');

    $routes = implode(PHP_EOL, $config->get('routes'));

    $form['routes'] = [
      '#type' => 'textarea',
      '#title' => t('Routes to exclude'),
      '#description' => t('One configuration name per line.<br />Examples: <ul><li>user.login</li><li>hn.endpoint</li><li>rest.* (will ignore all routes that starts with <em>rest.</em>)</li><li>~rest.example.rest (will force redirect on this route, even if ignored by a wildcard)</li></ul>'),
      '#default_value' => $routes,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $routes = array_map('trim',
      array_filter(explode(PHP_EOL, $values['routes']))
    );

    /* @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
    $route_provider = \Drupal::service('router.route_provider');

    if (!in_array('user.login', $routes)) {
      $form_state->setErrorByName('user.login', t('Route %name should not be removed.', [
        '%name' => 'user.login',
      ]));
      array_push($routes, 'user.login');
    }

    if (!in_array('hn.endpoint', $routes)) {
      drupal_set_message(t("You removed %name from excluded routes, we don\'t recommend this.", [
        '%name' => 'hn.endpoint',
      ]), 'warning');
    }

    // When a route does not exists getRouteByName
    // will return a RouteNotFoundException.
    foreach ($routes as $route) {
      try {
        $route_provider->getRouteByName($route);
      }
      catch (RouteNotFoundException $exception) {
        if (strpos($route, '*') === FALSE && strpos($route, '~') === FALSE) {
          $form_state->setErrorByName($route, t('Route %name does not exist', [
            '%name' => $route,
          ]));
        }
      }
    }

    $form_state->setValue('routes', $routes);

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Save the config.
    $this->config('hn_frontend.settings')
      ->set('routes', $values['routes'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
