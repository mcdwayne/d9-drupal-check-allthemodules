<?php

namespace Drupal\simply_signups\Form\Config;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements a config form.
 */
class SimplySignupsConfigForm extends ConfigFormBase {

  protected $configFactory;

  /**
   * Implements __construct.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Implements create.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simply_signups_config_form';
  }

  /**
   * Implements getEditableConfigNames.
   */
  protected function getEditableConfigNames() {
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('simply_signups.config');
    $form['#attached']['library'][] = 'simply_signups/styles';
    $form['#attributes'] = [
      'class' => ['simply-signups-settings-form', 'simply-signups-form'],
    ];
    $form['signup_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Simply signups configuration form'),
    ];
    if (is_array($config->get('bundles'))) {
      $form['signup_fieldset']['bundles'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Select which content types to enable signups for.'),
        '#options' => node_type_get_names(),
        '#default_value' => $config->get('bundles'),
        '#required' => TRUE,
      ];
    }
    else {
      $form['signup_fieldset']['bundles'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Select which content types to enable signups for.'),
        '#options' => node_type_get_names(),
        '#required' => TRUE,
      ];
    }
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#attributes' => [
        'class' => [
          'button--primary',
          'btn-primary',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bundles = [];
    $field = $form_state->getValue('bundles');
    foreach ($field as $bundle) {
      if ($bundle !== 0) {
        $bundles[] = $bundle;
      }
    }
    $config = $this->configFactory->getEditable('simply_signups.config');
    $config->set('bundles', $bundles)->save();
    drupal_flush_all_caches();
    drupal_set_message($this->t('Signup configuration saved successfully.'));
  }

}
