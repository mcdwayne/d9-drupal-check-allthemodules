<?php

namespace Drupal\nsw_feedback_assist\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a block with for feedback assist.
 *
 * @Block(
 *   id = "nsw_feedback_assist_block",
 *   admin_label = @Translation("NSW Feedback Block")
 * )
 */
class NswFeedbackAssistBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return [
      '#attached' => [
        'library' => [
          'nsw_feedback_assist/nsw_feedback_assist_js',
        ],
        'drupalSettings' => [
          'type' => 'setting',
          'data' => $this->getConfiguration(),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $form['nsw_feedback_assist_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Request URL'),
      '#size' => 200,
      '#default_value' => $config['nsw_feedback_assist_url'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $parent = parent::blockSubmit($form, $form_state);
    $this->setConfigurationValue(
      'nsw_feedback_assist_url',
      $form_state->getValue('nsw_feedback_assist_url')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'nsw_feedback_assist_url' => 'https://feedbackassist.onegov.nsw.gov.au/feedbackassist',
    ];
  }

}
