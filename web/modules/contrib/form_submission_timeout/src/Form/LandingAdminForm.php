<?php
/**
 * @file
 * Contains \Drupal\form_submission_timeout\Form\LandingAdminForm.
 */

namespace Drupal\form_submission_timeout\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Landing Admin form.
 */
class LandingAdminForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_submission_timeout_configuration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $countdownUrl = Url::fromRoute('form_submission_timeout.countdown');
    $countdownLink = \Drupal::l(t('Submission Countdown'), $countdownUrl);
    $timedUrl = Url::fromRoute('form_submission_timeout.countdown');
    $timedLink = \Drupal::l(t('Submission Timed'), $timedUrl);
    $form['sub_out_countdown'] = array(
      '#markup' => '<div>' . $countdownLink . '</div>'
    );
    $form['sub_out_countdown_description'] = array(
      '#markup' => '<div>You can time each and every form on the drupal site.
         If user does not submit a form within a timeout period, the form will have
         to be either filled out again, or the page will have to be refreshed in
         order to make a successful form submission</div>',
    );

    $form['sub_out_timed'] = array(
      '#markup' => '<div>' . $timedLink . '</div>'
    );
    $form['sub_out_timed_description'] = array(
      '#markup' => '<div>You can keep a time limitation on a form. You can decide when
         will the submission on a form can start or end in a day, week, or a month.
         Basically, you can decide when does a particular form gets activated.</div>',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
//    $config = \Drupal::getContainer()->get('config.factory')->getEditable('form_submission_timeout.settings');
    return $form;
  }
}
