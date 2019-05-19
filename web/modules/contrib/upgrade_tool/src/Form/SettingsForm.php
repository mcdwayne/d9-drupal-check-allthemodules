<?php

namespace Drupal\upgrade_tool\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\features\FeaturesManagerInterface;

/**
 * Provides upgrade_tool settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The FeaturesManager.
   *
   * @var \Drupal\features\FeaturesManagerInterface
   */
  protected $featuresManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, FeaturesManagerInterface $features_manager) {
    parent::__construct($config_factory);
    $this->featuresManager = $features_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('features.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'upgrade_tool_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'upgrade_tool.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $features_modules = array_keys($this->featuresManager->getFeaturesModules());
    $config = $this->config('upgrade_tool.settings');

    $form['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#options' => [
        'disabled' => $this->t('Disabled'),
        'all' => $this->t('All configs'),
        'features' => $this->t('Features'),
      ],
      '#default_value' => ($config->get('mode')) ? $config->get('mode') : 'disabled',
      '#description' => $this->t('Set upgrade tool mode: Disabled - not track configs, All configs - track all configs, Features - track features configs'),
    ];
    if ($features_modules) {
      $form['features'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Features'),
        '#default_value' => ($config->get('features')) ? $config->get('features') : [],
        '#options' => array_combine($features_modules, $features_modules),
        '#states' => [
          'visible' => [
            'select[name="mode"]' => ['value' => 'features'],
          ],
        ],
        '#description' => $this->t('Track configs related to selected features'),
      ];
    }
    else {
      $form['features'] = [
        '#markup' => $this->t('Features not found, please create new feature.'),
        '#states' => [
          'visible' => [
            'select[name="mode"]' => ['value' => 'features'],
          ],
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('upgrade_tool.settings');
    $config->set('mode', $form_state->getValue('mode'))->save();
    $config->set('features', $form_state->getValue('features'))->save();
    parent::submitForm($form, $form_state);
  }

}
