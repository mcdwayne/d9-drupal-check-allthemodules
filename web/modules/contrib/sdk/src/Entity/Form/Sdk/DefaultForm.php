<?php

namespace Drupal\sdk\Entity\Form\Sdk;

use Drupal\Core\Render\Element;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sdk\SdkPluginManager;

/**
 * Default entity form for every SDK.
 *
 * @method \Drupal\sdk\Entity\Sdk getEntity()
 *
 * @property \Drupal\sdk\Entity\Sdk $entity
 */
class DefaultForm extends EntityForm {

  /**
   * Instance of the "plugin.manager.sdk" service.
   *
   * @var SdkPluginManager
   */
  protected $pluginManager;

  /**
   * DefaultForm constructor.
   *
   * @param \Drupal\sdk\SdkPluginManager $plugin_manager
   *   Instance of the "plugin.manager.sdk" service.
   */
  public function __construct(SdkPluginManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.sdk'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $this->entity->set('id', $form_state->getValue('type', $this->entity->id()));

    $entity_id = $this->entity->id();
    $form_id = strtolower(strtr(static::class, '\\', '_'));
    $options = [];

    foreach ($this->pluginManager->getDefinitions() as $option => $plugin) {
      $options[$option] = $plugin->getLabel();
    }

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => $options,
      '#required' => TRUE,
      '#disabled' => !$this->entity->isNew(),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $entity_id,
      '#ajax' => [
        'callback' => '::reloadForm',
        'wrapper' => $form_id,
      ],
    ];

    if (!empty($entity_id)) {
      $form['label'] = [
        '#type' => 'hidden',
        '#value' => $options[$entity_id],
      ];

      $form['settings'] = [];

      $this->invoke(__FUNCTION__, $form_state, $form['settings']);

      static::addWrapper($form['settings'], $form_id . '_' . $entity_id);
      static::processSettings($form['settings'], $this->entity->settings);
    }

    static::addWrapper($form, $form_id);

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function reloadForm(array $form) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form['settings'])) {
      // Checks that user creates a new configuration but this kind of types
      // already exists. In this case controller validation will be rejected.
      if ($this->entity->isNew() && NULL !== $this->entityTypeManager->getStorage($this->entity->getEntityTypeId())->load($this->entity->id())) {
        $form_state->setError($form['type'], $this->t('@sdk SDK is already configured! @link', [
          '@sdk' => $this->entity->label(),
          '@link' => $this->entity->link($this->t('Check out it here.'), 'edit-form'),
        ]));
      }
      else {
        $this->invoke(__FUNCTION__, $form_state, $form['settings']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $plugin = $this->invoke(__FUNCTION__, $form_state, $form['settings']);
    $redirect = $this->entity->toUrl('collection');

    if ($plugin->isLoginCallbackOverridden()) {
      $form_state->setResponse($plugin->requestToken($redirect));
    }
    else {
      $form_state->setRedirectUrl($redirect);
    }
  }

  /**
   * Invoke one of methods from a form controller.
   *
   * @param string $method
   *   Name of method.
   * @param FormStateInterface $form_state
   *   A state of form.
   * @param array[] $form
   *   Form element definitions.
   *
   * @return \Drupal\sdk\SdkPluginBase
   *   SDK plugin.
   */
  protected function invoke($method, FormStateInterface $form_state, array &$form = []) {
    $plugin =& $form_state->getTemporaryValue('sdk');

    if (NULL === $plugin) {
      $plugin = $this->pluginManager->createInstance($this->entity->id());
      $plugin->setConfig($this->entity);
    }

    $plugin->getConfigurationForm()->{$method}($form, $form_state);

    return $plugin;
  }

  /**
   * Recursively set form settings.
   *
   * @param array[] $form
   *   Form element definitions.
   * @param mixed $settings
   *   Settings list.
   */
  protected static function processSettings(array &$form, &$settings) {
    foreach (Element::children($form) as $child) {
      if (isset($settings[$child])) {
        $form[$child]['#default_value'] = $settings[$child];
      }
      elseif (isset($form[$child]['#default_value'])) {
        $settings[$child] = $form[$child]['#default_value'];
      }

      if (is_array($form[$child])) {
        call_user_func_array(__METHOD__, [&$form[$child], &$settings[$child]]);
      }
    }
  }

  /**
   * Add HTML wrapper for an element.
   *
   * @param array $element
   *   Element definition.
   * @param string $id
   *   HTML identifier of an element.
   */
  protected static function addWrapper(array &$element, $id) {
    $element['#tree'] = TRUE;
    $element['#prefix'] = '<div id="' . $id . '">';
    $element['#suffix'] = '</div>';
  }

}
