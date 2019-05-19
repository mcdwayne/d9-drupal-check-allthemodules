<?php

namespace Drupal\social_hub\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_hub\Entity\Platform;
use Drupal\social_hub\PlatformIntegrationPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Platform form.
 *
 * @property \Drupal\social_hub\PlatformInterface $entity
 */
class PlatformForm extends EntityForm {

  /**
   * The platform integration plugin manager.
   *
   * @var \Drupal\social_hub\PlatformIntegrationPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs a new PlatformForm object.
   *
   * @param \Drupal\social_hub\PlatformIntegrationPluginManager $plugin_manager
   *   The platform plugin manager.
   */
  public function __construct(PlatformIntegrationPluginManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.social_hub.platform')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (empty($this->pluginManager->getDefinitions())) {
      $form['warning'] = [
        '#markup' => $this->t('No platform plugins found.'),
      ];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $available_plugins = $this->pluginManager->getPluginsAsOptions();

    // Use the first available plugin as the default value.
    if (empty($this->entity->getPlugins())) {
      $plugin_ids = array_keys($available_plugins);
      $plugins = reset($plugin_ids);
      $this->entity->setPlugins([$plugins]);
    }
    // The form state will have a plugin value if #ajax was used.
    $plugins = $form_state->getValue('plugins', $this->entity->getPlugins());
    $plugins_configuration = $this->entity->getConfiguration();

    $wrapper_id = Html::getUniqueId('platform-plugin-form');
    $form['#tree'] = TRUE;
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('Label for the platform.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [Platform::class, 'load'],
        'source' => ['label'],
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Available plugins'),
      '#description' =>
      $this->t('Select which integration plugins should be used for this platform.'),
      '#options' => $available_plugins,
      '#default_value' => $plugins,
      '#empty_value' => '',
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => $wrapper_id,
      ],
    ];

    $form['configuration'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#title_display' => FALSE,
    ];

    foreach ($plugins as $selected) {
      $form['configuration'][$selected] = [
        '#type' => 'social_hub_plugin_configuration',
        '#plugin_type' => 'social_hub.platform',
        '#plugin_id' => $selected,
        '#default_value' => $plugins_configuration[$selected] ?? [],
      ];
    }

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->entity->status(),
    ];

    return $form;
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The AJAX form result.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->entity->setConfiguration(array_filter($form_state->getValue(['configuration'])));
    $this->entity->setPlugins(array_filter($form_state->getValue(['plugins'])));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Clean checkboxes values.
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $message = $result === SAVED_NEW
      ? $this->t('Created new platform %label.', $message_args)
      : $this->t('Updated platform %label.', $message_args);
    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $result;
  }

}
