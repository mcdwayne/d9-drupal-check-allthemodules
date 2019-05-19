<?php

namespace Drupal\webform_score\Plugin\WebformScore;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\webform_score\Plugin\WebformScoreInterface;

/**
 * @WebformScore(
 *   id="equals",
 *   label=@Translation("Equals"),
 *   compatible_data_types={"string"},
 * )
 */
class Equals extends WebformScoreBase implements WebformScoreInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'expected' => '',
      'case_sensitive' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function score(TypedDataInterface $answer) {
    if ($this->configuration['case_sensitive']) {
      $is_equal = (string) $this->configuration['expected'] === (string) $answer->getValue();
    }
    else {
      $is_equal = Unicode::strcasecmp((string) $this->configuration['expected'], (string) $answer->getValue()) === 0;
    }

    return $is_equal ? $this->getMaxScore() : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['expected'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Expected answer'),
      '#size' => 32,
      '#required' => TRUE,
      '#default_value' => $this->configuration['expected'],
    ];

    $form['case_sensitive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Case sensitive?'),
      '#description' => $this->t('Whether to compare the answer in case sensitive mode.'),
      '#default_value' => $this->configuration['case_sensitive'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['expected'] = $form_state->getValue('expected');
    $this->configuration['case_sensitive'] = (bool) $form_state->getValue('case_sensitive');
  }

}
