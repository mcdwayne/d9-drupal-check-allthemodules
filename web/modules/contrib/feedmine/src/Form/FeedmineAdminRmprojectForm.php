<?php
/**
 * @file
 * Contains \Drupal\feedmine\Form\FeedmineAdminRmprojectForm.
 */

namespace Drupal\feedmine\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implements an test form.
 */
class FeedmineAdminRmprojectForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'feedmine_rmproject_select';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $rmkey_check = \Drupal::config('feedmine.settings')->get('feedmine_rmapikey');
    if(!isset($rmkey_check)){
      $form['notice'] = array(
        '#type' => 'item',
        '#title' => 'NOTICE',
        '#markup' => t('Configure your <a href="/admin/config/feedmine/feedmine_settings/rmapi">Redmine API settings</a> first.'),
      );
      return $form;
    }
    else {
      // Retrieve and set the Redmine UID to set as default asignee.
      $rmuid = feedmine_rmuid();
      // Verify a UID exists
      if(isset($rmuid)){
        \Drupal::configFactory()->getEditable('feedmine.settings')
        ->set('feedmine_rmuid', $rmuid)
        ->save();
      }
      else{
        // Notify user before proceeding.
        drupal_set_message(t('Unable to assign a Redmine UID.'), $type='warning');
      }
      // Retrieve list of available projects from Redmine.
      $projects = feedmine_getrmprojects();
    }

    // Verify there are projects to select from.
    if(!isset($projects)){
      $msg = 'Suggestions:<ul><li>Verify your !apisettings.</li><li>Check the !recentlogs for additional details.</li></ul>';
      $args = array('!apisettings' => '<a href="/admin/config/feedmine/feedmine_settings/rmapi">Redmine API settings</a>', '!recentlogs' => '<a href="/admin/reports/dblog">recent log entries</a>');
      $form['notice'] = array(
        '#type' => 'item',
        '#title' => t('Unable to retireve a list of projects from Redmine:'),
        '#markup' => t($msg, $args),
      );
      return $form;
    }
    else{
      // Return a project selection form.
      $form['feedmine_rmprojectid'] = array(
        '#type' => 'radios',
        '#title' => t('Select a Redmine project to post feedback issues.'),
        '#options' => $projects,
        '#required' => TRUE,
        '#default_value' => \Drupal::config('feedmine.settings')->get('feedmine_rmprojectid'), 
      );
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => 'Finish',
      );
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('feedmine.settings')
    ->set('feedmine_rmprojectid', $form_state->getValue('feedmine_rmprojectid'))
    ->save();
  }

}


