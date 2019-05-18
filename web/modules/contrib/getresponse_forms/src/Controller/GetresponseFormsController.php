<?php

namespace Drupal\getresponse_forms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\getresponse_forms\Entity\GetresponseForms;

/**
 * GetResponse Forms controller.
 */
class GetresponseFormsController extends ControllerBase {

  /**
   * View a GetResponse signup form as a page.
   *
   * @param string $signup_id
   *   The ID of the GetresponseForms entity to view.
   *
   * @return array
   *   Renderable array of page content.
   */
  public function page($signup_id) {
    $content = array();

    $signup = getresponse_forms_load($signup_id);

    $form = new \Drupal\getresponse_forms\Form\GetresponseFormsPageForm();

    $form_id = 'getresponse_forms_subscribe_page_' . $signup->id . '_form';
    $form->setFormID($form_id);
    $form->setSignup($signup);

    $content = \Drupal::formBuilder()->getForm($form);

    return $content;
  }

}
