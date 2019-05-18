<?php

/**
 * @file
 * Contains \Drupal\quizard\Form\quiz_video.
 */

namespace Drupal\quizard\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\video\Plugin\Field\FieldType\VideoItem;

class quiz_video extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quiz_video';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $step = $form_state->getBuildInfo()['callback_object']->getStep($cached_values);
    $video_field = $cached_values[$step]['field_quiz_video']->view();


    $form['quiz_video'] = [
      '#type' => 'item',
      '#markup' => !empty($video_field) ? render($video_field) : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Video step. Nothing to save here.
  }

}
