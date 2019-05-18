<?php

namespace Drupal\entity_counter\Plugin\EntityCounterRenderer;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_counter\Plugin\EntityCounterRendererBase;

/**
 * Adds plain renderer with ajax reload to entity counters.
 *
 * @EntityCounterRenderer(
 *   id = "plain_ajax_reload",
 *   label = @Translation("Plain with ajax reload"),
 *   description = @Translation("Render and update via ajax the entity counter value as a plain text string.")
 * )
 */
class PlainAjaxReload extends EntityCounterRendererBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['interval' => 30] + parent::defaultConfiguration();
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function render(array &$element) {
    // @TODO Add support to child elements.
    // $element[] = ['#plain_text' => $this->getEntityCounter()->getValue()];
    $element['#counter_value'] = $this->getEntityCounter()->getValue() * $element['#renderer_settings']['ratio'];

    if (!empty($element['#renderer_settings']['format'])) {
      $separator = !isset($element['#renderer_settings']['format']['separator']) ? '.' : $element['#renderer_settings']['format']['separator'];
      $decimals = !isset($element['#renderer_settings']['format']['decimals']) ? 0 : $element['#renderer_settings']['format']['decimals'];
      $decimal_type = !isset($element['#renderer_settings']['format']['type-decimal']) ? ',' : $element['#renderer_settings']['format']['type-decimal'];
      $element['#renderer_settings']['format'] = [
        'decimals' => $decimals,
        'separator' => $separator,
        'type-decimal' => $decimal_type,
      ];
    }

    if (!empty($element['#renderer_settings']['round'])) {
      $precision = empty($element['#renderer_settings']['format']['decimals']) ? 0 : $element['#renderer_settings']['format']['decimals'];
      $element['#counter_value'] = round($element['#counter_value'], $precision, $element['#renderer_settings']['round']);
    }

    if (!empty($element['#renderer_settings']['format'])) {
      $separator = $element['#renderer_settings']['format']['separator'];
      $decimals = $element['#renderer_settings']['format']['decimals'];
      $decimal_type = $element['#renderer_settings']['format']['type-decimal'];
      $element['#counter_value'] = number_format($element['#counter_value'], $decimals, $decimal_type, $separator);
    }

    // If the counter is closed we do not need to refresh the counter value.
    if ($this->getEntityCounter()->isOpen()) {
      $element['#attached']['library'][] = 'entity_counter/entity_counter.renderer.plain_ajax_reload';
      $ajax_settings = [
        'entity_counter' => $this->getEntityCounter()->id(),
        'url' => Url::fromRoute('entity.entity_counter.value', ['entity_counter' => $this->getEntityCounter()->id()])->toString(),
      ] + $element['#renderer_settings'];
      $element['#attached']['drupalSettings']['entity_counter']['plain_ajax_reload'][$element['#id']] = $ajax_settings;
    }
  }

}
