<?php

namespace Drupal\eform\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;
use Drupal\eform\Entity\EFormType;
use Drupal\eform\Form\EFormTypeForm;

/**
 * Returns responses for eForm module routes.
 */
class EFormTypeController extends EFormControllerBase {

  /**
   * Return Submissions page for the Entityform type.
   *
   * @param \Drupal\eform\Entity\EFormType $eform_type
   * @param null $views_display_id
   *
   * @return array
   */
  public function submissionsPage(EFormType $eform_type, $views_display_id = NULL, $view_name = NULL, $route_name = NULL) {
    $eform_type->loadDefaults();
    $view_name = $eform_type->getAdminView();
    $output = parent::submissionsPage($eform_type, $views_display_id, $view_name, 'entity.eform_type.submissions');
    return $output;
  }

  /**
   * Return Submissions page for the Entityform type.
   *
   * @param \Drupal\eform\Entity\EFormType $eform_type
   * @param null $views_display_id
   *
   * @return array
   */
  public function userSubmissionsPage(EFormType $eform_type, $views_display_id = NULL) {
    $eform_type->loadDefaults();
    $view_name = $eform_type->getUserView();
    $output = parent::submissionsPage($eform_type, $views_display_id, $view_name, 'entity.eform_submission.user_submissions');
    $url = Url::fromRoute('entity.eform_submission.submit_page',
      ['eform_type' => $eform_type->type]
    );
    $output['form_link']['#markup'] = $this->l('Return to form.', $url);
    $output['form_link']['#weight'] = -100;

    return $output;
  }

  /**
   * Submission Page access check.
   *
   * @param \Drupal\eform\Entity\EFormType $eform_type
   * @param null $views_display_id
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function submissionsPageAccess(EFormType $eform_type, $views_display_id = NULL) {
    return AccessResult::allowedIf($eform_type->getAdminView() != EFormTypeForm::VIEW_NONE);
  }

  /**
   * User Submission Page title callback.
   *
   * @param \Drupal\eform\Entity\EFormType $eform_type
   * @param null $views_display_id
   *
   * @return string
   */
  public function userSubmissionsTitle(EFormType $eform_type, $views_display_id = NULL) {
    return $this->t('@form_label: Your previous submissions', ['@form_label' => $eform_type->label()]);
  }

  /**
   * User Submissions page access checker.
   *
   * @param \Drupal\eform\Entity\EFormType $eform_type
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function userSubmissionsPageAccess(EFormType $eform_type) {
    return AccessResult::allowedIf($this->userHasViewSubmissions($eform_type));
  }

}
