<?php

namespace Drupal\ga_tokens\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for editing Google Analytics Global Dimensions settings.
 */
class GlobalForm extends ConfigFormBase {

  /**
   * The Module Handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * GlobalForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Config Factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Module Handler service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ga_tokens_global';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ga_tokens.global',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $gaConfig = $this->config('ga.settings');
    $gaTokenGlobalConfig = $this->config('ga_tokens.global');

    $premium = $gaConfig->get('premium');
    $groupCount = $premium ? 10 : 1;

    if ($premium) {
      $form['dimensions'] = [
        '#type' => 'vertical_tabs',
        '#title' => $this->t('Dimensions'),
        '#description' => $this->t("Custom dimensions must be configured via the Google Analytics Management Interface."),
      ];
    }

    for ($groupIndex = 0; $groupIndex < $groupCount; $groupIndex++) {
      if ($premium) {
        $form['dimensiongroup' . $groupIndex] = [
          '#type' => 'details',
          '#group' => 'dimensions',
          '#title' => $this->t('Dimensions %start to %end', [
            '%start' => $groupIndex * 20 + 1,
            '%end' => ($groupIndex + 1) * 20,
          ]),
          '#tree' => TRUE,
        ];
      }
      else {
        $form['dimensiongroup' . $groupIndex] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Dimensions'),
          '#description' => $this->t("Custom dimensions must be configured via the Google Analytics Management Interface."),
          '#tree' => TRUE,
        ];
      }

      $form['dimensiongroup' . $groupIndex]['table'] = [
        '#type' => 'table',
        '#header' => [
          ['data' => $this->t('Index')],
          ['data' => $this->t('Label')],
          ['data' => $this->t('Value')],
        ],
      ];
      for ($i = $groupIndex * 20 + 1; $i <= ($groupIndex + 1) * 20; $i++) {
        $form['dimensiongroup' . $groupIndex]['table'][$i]['index'] = [
          '#type' => 'markup',
          '#markup' => $i,
        ];
        $form['dimensiongroup' . $groupIndex]['table'][$i]['label'] = [
          '#type' => 'textfield',
          '#parents' => ['dimensions', $i, 'label'],
          '#title' => $this->t('Label'),
          '#title_display' => 'invisible',
          '#default_value' => $gaTokenGlobalConfig->get('dimensions.' . $i . '.label') ?: '',
          '#size' => 30,
        ];
        $form['dimensiongroup' . $groupIndex]['table'][$i]['value'] = [
          '#type' => 'textfield',
          '#parents' => ['dimensions', $i, 'value'],
          '#title' => $this->t('Value'),
          '#title_display' => 'invisible',
          '#default_value' => $gaTokenGlobalConfig->get('dimensions.' . $i . '.value') ?: '',
          '#element_validate' => ['token_element_validate'],
          '#token_types' => [],
        ];
      }
      if ($this->moduleHandler->moduleExists('token')) {
        $form['dimensiongroup' . $groupIndex]['table']['token_help']['index'] = [];
        $form['dimensiongroup' . $groupIndex]['table']['token_help']['label'] = [];
        $form['dimensiongroup' . $groupIndex]['table']['token_help']['value'] = [
          '#theme' => 'token_tree_link',
          '#token_types' => [],
        ];
      }
    }

    if ($premium) {
      $form['metrics'] = [
        '#type' => 'vertical_tabs',
        '#title' => $this->t('Metrics'),
        '#description' => $this->t("Custom metrics must be configured via the Google Analytics Management Interface."),
        '#tree' => TRUE,
      ];
    }

    for ($groupIndex = 0; $groupIndex < $groupCount; $groupIndex++) {

      if ($premium) {
        $form['metricgroup' . $groupIndex] = [
          '#type' => 'details',
          '#group' => 'metrics',
          '#title' => $this->t('Metrics %start to %end', [
            '%start' => $groupIndex * 20 + 1,
            '%end' => ($groupIndex + 1) * 20,
          ]),
          '#tree' => TRUE,
        ];
      }
      else {
        $form['metricgroup' . $groupIndex] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Metrics'),
          '#description' => $this->t("Custom metrics must be configured via the Google Analytics Management Interface."),
          '#tree' => TRUE,
        ];
      }

      $form['metricgroup' . $groupIndex]['table'] = [
        '#type' => 'table',
        '#header' => [
          ['data' => $this->t('Index')],
          ['data' => $this->t('Label')],
          ['data' => $this->t('Value')],
        ],
      ];
      for ($i = $groupIndex * 20 + 1; $i <= ($groupIndex + 1) * 20; $i++) {
        $form['metricgroup' . $groupIndex]['table'][$i]['index'] = [
          '#type' => 'markup',
          '#markup' => $i,
        ];
        $form['metricgroup' . $groupIndex]['table'][$i]['label'] = [
          '#type' => 'textfield',
          '#parents' => ['metrics', $i, 'label'],
          '#title' => $this->t('Label'),
          '#title_display' => 'invisible',
          '#default_value' => $gaTokenGlobalConfig->get('metrics.' . $i . '.label') ?: '',
          '#size' => 30,
        ];
        $form['metricgroup' . $groupIndex]['table'][$i]['value'] = [
          '#type' => 'textfield',
          '#parents' => ['metrics', $i, 'value'],
          '#title' => $this->t('Value'),
          '#title_display' => 'invisible',
          '#default_value' => $gaTokenGlobalConfig->get('metrics.' . $i . '.value') ?: '',
          '#element_validate' => ['token_element_validate'],
          '#token_types' => [],
        ];
      }
      if ($this->moduleHandler->moduleExists('token')) {
        $form['metricgroup' . $groupIndex]['table']['token_help']['index'] = [];
        $form['metricgroup' . $groupIndex]['table']['token_help']['label'] = [];
        $form['metricgroup' . $groupIndex]['table']['token_help']['value'] = [
          '#theme' => 'token_tree_link',
          '#token_types' => [],
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $dimensions = [];
    foreach ($form_state->getValues()['dimensions'] as $index => $dimension) {
      if (!empty($dimension['label']) || !empty($dimension['value'])) {
        $dimensions[$index] = [
          'label' => $dimension['label'],
          'value' => $dimension['value'],
        ];
      }
    }
    $metrics = [];
    foreach ($form_state->getValues()['metrics'] as $index => $metric) {
      if (!empty($metric['label']) || !empty($metric['value'])) {
        $metrics[$index] = [
          'label' => $metric['label'],
          'value' => $metric['value'],
        ];
      }
    }

    $this->config('ga_tokens.global')
      ->set('dimensions', $dimensions)
      ->set('metrics', $metrics)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
