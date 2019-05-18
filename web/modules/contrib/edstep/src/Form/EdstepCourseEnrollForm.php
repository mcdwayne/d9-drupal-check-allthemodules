<?php

namespace Drupal\edstep\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\edstep\Entity\EdstepCourse;
use GuzzleHttp\Exception\RequestException;

/**
 * Class EdstepCourseEnrollForm.
 */
class EdstepCourseEnrollForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edstep_course_enroll_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EdstepCourse $edstep_course = NULL) {
    $form['#edstep_course'] = $edstep_course;
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $edstep_course->isEnrolled() ? $this->t('Continue course') : $this->t('Enroll'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $edstep_course = $form['#edstep_course'];
    $response = \Drupal::service('edstep.edstep')->authorize();

    if($response) {
      return $form_state->setResponse($response);
    }
    if(!$edstep_course->isEnrolled()) {
      try {
        $edstep_course->getRemote()->enroll();
        drupal_set_message($this->t('Enroll successful.'));
      } catch(RequestException $e) {
        drupal_set_message($this->t('Could not enroll.'),'error');
        return;
      }
    }

    $url = $edstep_course->getContinueUrl();

    $form_state->setRedirectUrl($url);
  }

}
