<?php

namespace Drupal\slack_rtm\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\slack_rtm\SlackRtmApi;

/**
 * Class SlackRtmUpdateForm.
 */
class SlackRtmMessageUpdateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'slack_rtm_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @todo add checks for config.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update Messages'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $api = (new SlackRtmApi())->getMessages();

  }

}
