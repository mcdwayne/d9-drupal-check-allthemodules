<?php

namespace Drupal\smallads\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a condition whether the current context implies any smallad type.
 *
 * @todo see https://www.drupal.org/node/2284687
 * show this only on ad blocks
 *
 * @Condition__________________________________________________________________(
 *   id = "smallad_context",
 *   label = @Translation("Smallads"),
 *   category = @Translation("Smallads"),
 *   context = { }
 * )
 * @deprecated isn't much use anyhow
 */
class AdContext extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->isNegated() ?
      $this->t('Except on ad pages') :
      $this->t('Only on ad pages');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'ad_page' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['ad_page'] = [
      '#title' => $this->t('Pages in which a smallad-type can be inferred'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['ad_page'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['ad_page'] = $form_state->getValue('ad_page');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    return $this->configuration['ad_page'] && smallad_type_from_route_match();
  }

}
