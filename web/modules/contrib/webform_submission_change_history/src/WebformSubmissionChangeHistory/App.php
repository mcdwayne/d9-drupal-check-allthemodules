<?php

namespace Drupal\webform_submission_change_history\WebformSubmissionChangeHistory;

use Drupal\webform_submission_change_history\traits\Singleton;
use Drupal\webform_submission_change_history\traits\CommonUtilities;
use Drupal\Core\Form\FormStateInterface;

/**
 * Represents the Webform Submission Notes App.
 */
class App {

  use Singleton;
  use CommonUtilities;

  /**
   * Testable implementation of hook_form_alter().
   */
  public function hookFormAlter(&$form, FormStateInterface $form_state, string $form_id) {
    try {
      $this->submissionFormAlter($form, $form_id);
    }
    catch (\Throwable $t) {
      drupal_set_message(t('An error occurred in webform_submission_change_history: @t at @f:@l. See the log for more details.', [
        '@t' => $t->getMessage(),
        '@f' => $t->getFile(),
        '@l' => $t->getLine(),
      ]), 'error');
      $this->watchdogThrowable($t);
    }
  }

  /**
   * Testable implementation of hook_requirements().
   */
  public function hookRequirements(string $phase) : array {
    $requirements = [];
    if ($phase == 'runtime') {
      $patched = $this->webformIsPatched();
      $requirements['webform_submission_change_history_patch'] = array(
        'title' => t('Webform Submission Notes requires a patch to Webform'),
        'value' => $patched ? t('Patch applied') : t('Patch not applied'),
        'severity' => $patched ? REQUIREMENT_OK : REQUIREMENT_ERROR,
        'description' => t('Patch https://www.drupal.org/project/webform/issues/2972498#comment-12613727 must be applied.'),
      );
    }
    return $requirements;
  }

  /**
   * Helper function to alter the submission edit form.
   */
  protected function submissionFormAlter(array &$form, string $form_id) {
    if (!empty($form['#webform_id']) && $form_id == 'webform_submission_' . $form['#webform_id'] . '_edit_form' && $this->userAccess('access webform submission log')) {
      if (!$this->webformIsPatched()) {
        drupal_set_message(t('Make sure to patch the Webform module as explained in the README.md file of the webform_submission_change_history module.'), 'error');
        return;
      }
      $id = $form['information']['#webform_submission']->id();
      $this->submissionInfo($id)->alterForm($form);
    }
  }

  /**
   * Get submission info for a submission.
   *
   * @param int $id
   *   The submission ID.
   *
   * @return SubmissionInfo
   *   Object encapsulating submission info.
   *
   * @throws Exception
   */
  public function submissionInfo(int $id) : SubmissionInfo {
    return new SubmissionInfo($id);
  }

  /**
   * Check if Webform is patched as required.
   *
   * Make sure the
   * https://www.drupal.org/files/issues/2018-05-14/2972498-3-webform-8.x-5.x-log-changes.patch
   * patch is applied.
   *
   * @return bool
   *   TRUE if the patch is applied.
   */
  public function webformIsPatched() : bool {
    $contents = file_get_contents(drupal_get_path('module', 'webform') . '/src/Entity/WebformSubmission.php');
    return (strpos($contents, 'logData') !== FALSE);
  }

}
