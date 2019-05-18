<?php

namespace Drupal\hp\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\hp\FormStrategyManager;

class FormStrategyForm extends EntityForm {

  /**
   * The hp form strategy plugin manager.
   *
   * @var \Drupal\hp\FormStrategyManager;
   */
  protected $pluginManager;

  /**
   * Constructs a new PaymentGatewayForm object.
   *
   * @param \Drupal\hp\FormStrategyManager $plugin_manager
   *   The HP form strategy plugin manager.
   */
  public function __construct(FormStrategyManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.hp_form_strategy')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\hp\Entity\FormStrategyInterface $form_strategy */
    $form_strategy = $this->entity;
    $plugins = array_column($this->pluginManager->getDefinitions(), 'label', 'id');
    asort($plugins);
    foreach ($plugins as $plugin_id => $label) {
      $plugin = $this->pluginManager->createInstance($plugin_id, []);
      if (!$plugin->access()) {
        unset($plugins[$plugin_id]);
      }
    }

    // Use the first available plugin as the default value.
    if (!$form_strategy->getPluginId()) {
      $plugin_ids = array_keys($plugins);
      $plugin_id = reset($plugin_ids);
      $form_strategy->setPluginId($plugin_id);
    }
    // The form state will have a plugin value if #ajax was used.
    $plugin_id = $form_state->getValue('plugin', $form_strategy->getPluginId());
    // Pass the plugin configuration only if the plugin hasn't been changed via #ajax.
    $plugin_configuration = $form_strategy->getPluginId() == $plugin_id ? $form_strategy->getPluginConfiguration() : [];
    /** @var \Drupal\hp\Plugin\hp\FormStrategyInterface $plugin */
    $plugin = $this->pluginManager->createInstance($plugin_id, $plugin_configuration);

    $wrapper_id = Html::getUniqueId('hp-form-strategy-form');
    $form['#tree'] = TRUE;
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';

    $form['#tree'] = TRUE;
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Form ID (or group ID)'),
      '#description' => $this->t('Enter a form ID to protect a specific form. To protect a group of forms matched via regular expression, enter a unique ID using lowercase letters, numbers, and underscores.'),
      '#maxlength' => 255,
      '#default_value' => $form_strategy->id(),
      '#machine_name' => [
        'exists' => '\Drupal\hp\Entity\FormStrategy::load',
      ],
      '#required' => TRUE,
    ];
    $form['group_matching'] = [
      '#type' => 'details',
      '#title' => $this->t('Group matching parameters'),
    ];
    $form['group_matching']['regexp'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Regular expression'),
      '#description' => $this->t('Enter a forward-slash delimited regular expression to protect multiple forms via this configuration, e.g. <i>/^contact_.+_form$/</i> to match both <i>contact_user_form</i> and <i>contact_site_form</i>.') . '<br />' . $this->t('Defining forms individually is recommended to prevent regular expression matching from slowing down your site.'),
      '#maxlength' => 255,
      '#default_value' => $form_strategy->getRegexp(),
    ];
    $form['plugin'] = [
      '#type' => 'radios',
      '#title' => $this->t('Plugin'),
      '#options' => $plugins,
      '#default_value' => $plugin_id,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => $wrapper_id,
      ],
    ];
    $form['configuration'] = [
      '#type' => 'container',
      '#default_configuration' => $plugin_configuration,
      '#parents' => ['configuration', $plugin_id],
    ];
    $form['configuration'] = $plugin->buildConfigurationForm($form['configuration'], $form_state);

    return $form;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->entity->setRegexp($form_state->getValue('group_matching')['regexp']);

    /** @var \Drupal\hp\Entity\FormStrategyInterface $form_strategy */
    $form_strategy = $this->entity;
    $form_strategy->setPluginConfiguration($form_state->getValue(['configuration']));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message($this->t('Saved the protected form %label.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.hp_form_strategy.collection');
  }

}
