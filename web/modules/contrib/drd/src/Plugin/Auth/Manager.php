<?php

namespace Drupal\drd\Plugin\Auth;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides the DRD Auth plugin manager.
 */
class Manager extends DefaultPluginManager {
  use StringTranslationTrait;

  /**
   * Constructor for DrdAuthManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Auth', $namespaces, $module_handler, 'Drupal\drd\Plugin\Auth\BaseInterface', 'Drupal\drd\Annotation\Auth');

    $this->alterInfo('drd_drd_auth_info');
    $this->setCacheBackend($cache_backend, 'drd_drd_auth_plugins');
  }

  /**
   * Callback to provide an select list of available plugins for the form API.
   *
   * @return array
   *   Associated array of available authentication methods.
   */
  public function selectList() {
    $list = [];

    foreach ($this->getDefinitions() as $def) {
      $list[$def['id']] = $def['label'];
    }

    return $list;
  }

  /**
   * A form API container with all the settings for DRD authentication.
   *
   * @param array $form
   *   The array form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The settings form for selecting the authentication method.
   */
  public static function authForm(array $form, FormStateInterface $form_state) {
    $type = \Drupal::service('plugin.manager.drd_auth');
    $element['drd_auth'] = [
      '#type' => 'fieldset',
      '#title' => $type->t('Authentication type'),
    ];
    $element['drd_auth']['description'] = [
      '#markup' => $type->t('The method how DRD should authenticate each request on the remote domains on this core.'),
    ];
    $element['drd_auth']['drd_auth_type'] = [
      '#type' => 'select',
      '#options' => $type->selectList(),
      '#default_value' => 'shared_secret',
    ];
    foreach ($type->getDefinitions() as $def) {
      /** @var BaseInterface $auth */
      $auth = $type->createInstance($def['id']);
      $condition = ['select#edit-drd-auth-type' => ['value' => $def['id']]];
      $element['drd_auth'][$def['id']] = [
        '#type' => 'container',
        '#states' => [
          'visible' => $condition,
        ],
      ];
      $auth->settingsForm($element['drd_auth'][$def['id']], $condition);
    }
    return $element;
  }

  /**
   * Callback to extract all auth settings from the submitted form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   Associated array with the selected authentication method and the
   *   settings for all authentication methods.
   */
  public static function authFormValues(FormStateInterface $form_state) {
    $type = \Drupal::service('plugin.manager.drd_auth');

    $values = [
      'auth' => $form_state->getValue('drd_auth_type'),
      'authsetting' => [],
    ];
    foreach ($type->getDefinitions() as $def) {
      /** @var BaseInterface $auth */
      $auth = $type->createInstance($def['id']);
      $values['authsetting'][$def['id']] = $auth->settingsFormValues($form_state);
    }
    return $values;
  }

}
