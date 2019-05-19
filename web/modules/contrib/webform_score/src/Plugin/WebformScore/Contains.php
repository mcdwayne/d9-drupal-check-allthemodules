<?php

namespace Drupal\webform_score\Plugin\WebformScore;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\webform_score\Plugin\WebformScoreInterface;

/**
 * @WebformScore(
 *   id="contains",
 *   label=@Translation("Contains"),
 *   compatible_data_types={"string"},
 * )
 */
class Contains extends WebformScoreBase implements WebformScoreInterface {

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
    $answer = $answer->getValue();
    $expected = $this->configuration['expected'];
    if (!$this->configuration['case_sensitive']) {
      $answer = Unicode::strtolower($answer);
      $expected = Unicode::strtolower($expected);
    }

    return Unicode::strpos($answer, $expected) === FALSE ? 0 : $this->getMaxScore();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['expected'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Match'),
      '#size' => 32,
      '#required' => TRUE,
      '#default_value' => $this->configuration['expected'],
      '#description' => $this->t('The answer should contain this string to qualify as correct.'),
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
