<?php
/**
 * @file
 * Contains \Drupal\feedmine\Form\FeedmineAdminRmapiForm.
 */

namespace Drupal\feedmine\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implements an test form.
 */
class FeedmineAdminRmapiForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'feedmine_admin_rmapi_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['feedmine_rmurl'] = array(
        '#type' => 'textfield',
        '#title' => t('Redmine URL'),
        '#description' => t('The complete URL for Redmine.'),
        '#default_value' => \Drupal::config('feedmine.settings')->get('feedmine_rmurl'),
        '#required' => TRUE,
    );
    $form['feedmine_rmapikey'] = array(
        '#type' => 'textfield',
        '#title' => t('Redmine API access key'),
        '#description' => t('Redmine API access key for a authorized user.  Located under \'My account\' in your Redmine installation.'),
        '#default_value' => \Drupal::config('feedmine.settings')->get('feedmine_rmapikey'),
        '#required' => TRUE,
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => 'Next',
    );
    
    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $feedmine_rmurl    = $form_state->getValue('feedmine_rmurl');
    $feedmine_rmapikey = $form_state->getValue('feedmine_rmapikey');
    if (!filter_var($feedmine_rmurl, FILTER_VALIDATE_URL)) {
      $form_state->setErrorByName('feedmine_rmurl', $this->t('Please enter a valid URL. i.e. http://example.redmine.com'));
    };

    if (strlen($feedmine_rmapikey) < 40) {
      $form_state->setErrorByName('feedmine_rmapikey', $this->t('Please enter a valid Redmine API access key. (Min. 40 char.)'));
    };
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('feedmine.settings')
    ->set('feedmine_rmurl', $form_state->getValue('feedmine_rmurl'))
    ->set('feedmine_rmapikey', $form_state->getValue('feedmine_rmapikey'))
    ->save();
    $form_state->setRedirect('feedmine.settings.rmproject');
  }

}