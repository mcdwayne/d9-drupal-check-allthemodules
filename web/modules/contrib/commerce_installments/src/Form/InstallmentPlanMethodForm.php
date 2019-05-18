<?php

namespace Drupal\commerce_installments\Form;

use Drupal\commerce\Form\CommercePluginEntityFormBase;
use Drupal\commerce_installments\Plugin\InstallmentPlanMethodManager;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InstallmentPlanMethodForm extends CommercePluginEntityFormBase {

  /**
   * The installment plan method plugin manager.
   *
   * @var \Drupal\commerce_installments\Plugin\InstallmentPlanMethodManager
   */
  protected $pluginManager;

  /**
   * InstallmentPlanMethodForm constructor.
   *
   * @param \Drupal\commerce_installments\Plugin\InstallmentPlanMethodManager $plugin_manager
   */
  public function __construct(InstallmentPlanMethodManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_installment_plan_methods')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (empty($this->pluginManager->getDefinitions())) {
      $form['warning'] = [
        '#markup' => $this->t('No installment plan method plugins found. Please install a module which provides one.'),
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
    /** @var \Drupal\commerce_installments\Entity\InstallmentPlanMethodInterface $method */
    $method = $this->entity;
    $plugins = array_column($this->pluginManager->getDefinitions(), 'label', 'id');
    asort($plugins);

    // Use the first available plugin as the default value.
    if (!$method->getPluginId()) {
      $plugin_ids = array_keys($plugins);
      $plugin = reset($plugin_ids);
      $method->setPluginId($plugin);
    }
    // The form state will have a plugin value if #ajax was used.
    $plugin = $form_state->getValue('plugin', $method->getPluginId());
    // Pass the plugin configuration only if the plugin hasn't been changed via #ajax.
    $plugin_configuration = $method->getPluginId() == $plugin ? $method->getPluginConfiguration() : [];

    $wrapper_id = Html::getUniqueId('installment-method-form');
    $form['#tree'] = TRUE;
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $method->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $method->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_installments\Entity\InstallmentPlanMethod::load',
      ],
    ];
    $form['plugin'] = [
      '#type' => 'radios',
      '#title' => $this->t('Plugin'),
      '#options' => $plugins,
      '#default_value' => $plugin,
      '#required' => TRUE,
      '#disabled' => !$method->isNew(),
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => $wrapper_id,
      ],
    ];
    $form['configuration'] = [
      '#type' => 'commerce_plugin_configuration',
      '#plugin_type' => 'commerce_installment_plan_methods',
      '#plugin_id' => $plugin,
      '#default_value' => $plugin_configuration,
    ];
    $form['conditions'] = [
      '#type' => 'commerce_conditions',
      '#title' => $this->t('Conditions'),
      '#parent_entity_type' => 'installment_plan_method',
      '#entity_types' => ['commerce_order'],
      '#default_value' => $method->get('conditions'),
    ];
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $method->status(),
    ];

    return $this->protectPluginIdElement($form);
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

    /** @var \Drupal\commerce_installments\Entity\InstallmentPlanMethod */
    $entity = $this->entity;
    $entity->setPluginConfiguration($form_state->getValue(['configuration']));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message($this->t('Saved the %label installment plan method.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.installment_plan_method.collection');
  }

}
