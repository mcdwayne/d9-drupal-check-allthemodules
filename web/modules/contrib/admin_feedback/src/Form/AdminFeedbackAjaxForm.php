<?php

/**
 * @file
 * Contains \Drupal\admin_feedback\Form\AdminFeedbackForm.
 */

namespace Drupal\admin_feedback\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\admin_feedback\Controller\AdminFeedbackController;

/**
 * My Custom form.
 */
class AdminFeedbackAjaxForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_feedback_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['feedback_message'] = array(
      '#type' => 'textarea',
      '#prefix' => '<div id="feedback_msg_result"></div>',
      '#attributes' => array(
        'id' => 'edit-feedback-msg',
      ),
    );

    $form['feedback_id'] = array(
      '#type' => 'hidden',
      '#attributes' => array('id' => 'feedback_id'),
    );

    $form['feedback_send'] = array(
      '#type' => 'button',
      '#value' => t('Send feedback'),
      '#ajax' => array(
        'callback' => '::validateFeedbackMsg',
      ),

    );

    return $form;
  }

  /**
   * Validates the feecback form.
   */
  public function validateFeedbackMsg(array &$form, FormStateInterface $form_state) {

    $warning = t('Form is empty!');
    $thank_you_msg = t('Thank you!');

    $response = new AjaxResponse();

    $feedback_id = $form_state->getValue('feedback_id');
    $feedback_message = $form_state->getValue('feedback_message');

    if ($feedback_id == '' || $feedback_message == '') {
      $response->addCommand(new HtmlCommand('#feedback_msg_result', '<p class="feedback_warning">' . $warning . '</p>'));
    }
    else {
      $feedback_controller_obj = new AdminFeedbackController();
      $feedback_controller_obj->updateFeedback($feedback_id, $feedback_message);
    }
    $response->addCommand(new ReplaceCommand('#feedback-message', '<h2 class="feedback_webform_upper_text">' . $thank_you_msg . '</h2>'));

    return $response;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
