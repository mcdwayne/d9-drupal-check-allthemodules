<?php

namespace Drupal\webform_prepopulate\Controller;

use Drupal\webform_prepopulate\Form\PrepopulateListForm;
use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\Entity\Webform;

/**
 * Class WebformPrepopulateController.
 */
class WebformPrepopulateController extends ControllerBase {

  /**
   * Get read, validate and delete operations on the prepopulate data.
   *
   * @todo add generate hash and download file operations.
   *
   * @return array
   *   Render array.
   */
  public function getDataOperations(Webform $webform) {
    // @todo check the mapping of the columns (elements could have been deleted after import)
    $form_class = PrepopulateListForm::class;
    return [
      'prepopulate_list_form' => \Drupal::formBuilder()->getForm($form_class),
      // 'Add or replace' button is provided by a local action.
      // So data manipulation actions are available
      // at the top and the bottom of the list.
      'delete_link' => [
        '#type' => 'link',
        '#title' => $this->t('Delete prepopulate data'),
        '#attributes' => [
          'class' => ['button', 'button--danger'],
        ],
        '#url' => Url::fromRoute('webform_prepopulate.delete_form', ['webform' => $webform->id()]),
      ],
    ];
  }

}
