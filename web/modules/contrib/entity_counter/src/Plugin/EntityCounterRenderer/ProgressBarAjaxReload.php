<?php

namespace Drupal\entity_counter\Plugin\EntityCounterRenderer;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Url;
use Drupal\entity_counter\Plugin\EntityCounterRendererBase;
use Drupal\loading_bar\Form\LoadingBarConfigurationForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds progress bar renderer with ajax reload to entity counters.
 *
 * @EntityCounterRenderer(
 *   id = "progress_bar_ajax_reload",
 *   label = @Translation("Progress bar with ajax reload"),
 *   description = @Translation("Render and update via ajax the entity counter value as a progress bar.")
 * )
 */
class ProgressBarAjaxReload extends EntityCounterRendererBase {

  /**
   * The loading bar configuration form instance.
   *
   * @var \Drupal\loading_bar\Form\LoadingBarConfigurationForm
   */
  protected $configurationForm;

  /**
   * Constructs an ProgressBarAjaxReload object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The currently active global container.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContainerInterface $container) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configurationForm = LoadingBarConfigurationForm::create($container);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'interval' => 30,
      'progress_bar' => LoadingBarConfigurationForm::defaultConfiguration(),
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Interval'),
      '#description' => $this->t('The refresh interval in seconds.'),
      '#step' => 1,
      '#min' => 5,
      '#default_value' => $this->configuration['interval'],
      '#required' => TRUE,
    ];

    $form['progress_bar'] = [
      '#tree' => TRUE,
      '#parents' => array_merge($form['#parents'], ['progress_bar']),
    ];
    $subform_state = SubformState::createForSubform($form['progress_bar'], $form, $form_state);
    $subform_state->setValue('progress_bar', $this->getConfiguration()['settings']['progress_bar']);
    // @TODO Try to enable ajax reload.
    $form['progress_bar'] = $this->configurationForm->buildForm($form['progress_bar'], $subform_state, FALSE);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $subform_state = SubformState::createForSubform($form['progress_bar'], $form, $form_state);
    $subform_state->setValue('progress_bar', $this->getConfiguration()['settings']['progress_bar']);
    $this->configurationForm->validateForm($form['progress_bar'], $subform_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->applyFormStateToConfiguration($form, $form_state);

    $subform_state = SubformState::createForSubform($form['progress_bar'], $form, $form_state);
    $subform_state->setValue('progress_bar', $this->getConfiguration()['settings']['progress_bar']);
    $this->configurationForm->submitForm($form['progress_bar'], $subform_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(array &$element) {
    // Set min and max values.
    $element['#renderer_settings']['progress_bar']['min'] = $this->getEntityCounter()->getMin();
    $element['#renderer_settings']['progress_bar']['max'] = $this->getEntityCounter()->getMax();
    // @TODO Add support to child elements and ajax.
    $element['#counter_value'] = [
      '#type' => 'loading_bar',
      '#configuration' => $element['#renderer_settings']['progress_bar'],
      '#value' => $this->getEntityCounter()->getValue() * $element['#renderer_settings']['ratio'],
    ];
    if (!empty($element['#renderer_settings']['round'])) {
      $element['#counter_value']['#value'] = round($element['#counter_value']['#value'], 0, $element['#renderer_settings']['round']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = [];
    foreach ($this->configuration as $key => $value) {
      if ($key != 'progress_bar') {
        $summary[] = $this->t('@setting: @value.', [
          '@setting' => Unicode::lcfirst($key),
          '@value' => $value,
        ]);
      }
      else {
        $value['height'] = '100%';
        $summary[] = [
          '#type' => 'loading_bar',
          '#configuration' => $value,
          '#value' => 50,
          '#attributes' => [
            'class' => ['loading-bar-demo'],
          ],
        ];
      }
    }

    return $summary;
  }

}
