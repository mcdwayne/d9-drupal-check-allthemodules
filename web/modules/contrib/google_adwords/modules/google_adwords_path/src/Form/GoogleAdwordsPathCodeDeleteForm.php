<?php

/**
 * @file
 * Contains \Drupal\google_adwords_path\Form\GoogleAdwordsPathCodeDeleteForm.
 */

namespace Drupal\google_adwords_path\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GoogleAdwordsPathCodeDeleteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_adwords_path_code_delete_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $cid = NULL) {
    $form = [];
    $path = 'admin/config/system/google_adwords/path';

    $code = google_adwords_path_load_code_by_cid($cid);
    $form['cid'] = [
      '#type' => 'value',
      '#value' => $code['cid'],
    ];
    $form['name'] = [
      '#type' => 'value',
      '#value' => $code['name'],
    ];

    return confirm_form($form, t('Are you sure you want to delete %name?', ['%name' => $code['name']]), $path);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (google_adwords_path_delete_code_by_cid($form_state->getValues()['cid'])) {
      $message = 'Successfully deleted %name.';
    }
    else {
      $message = 'There was a problem deleting Google Adwords Conversion code, %name.';
    }

    drupal_set_message(t($message, ['%name' => $form_state->getValues()['name']]));

    $path = 'admin/config/system/google_adwords/path';
    $response = new RedirectResponse($path);
    $response->send();
  }
}
