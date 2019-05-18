<?php

/**
 * @file
 * Contains \Drupal\google_adwords_path\Form\GoogleAdwordsPathCodeForm.
 */

namespace Drupal\google_adwords_path\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class GoogleAdwordsPathCodeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_adwords_path_code_form';
  }


  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $cid = NULL) {
    $language = \Drupal::languageManager()->getCurrentLanguage();
    $form = [];

    // If updating existing code, add the conversion id to the form.
    if ($cid) {
      $code = google_adwords_path_load_code_by_cid($cid);
      $form['cid'] = [
        '#type' => 'value',
        '#value' => $cid,
      ];
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#default_value' => isset($code) ? $code['name'] : '',
      '#size' => 75,
      '#maxlength' => 64,
      '#required' => TRUE,
      '#description' => t('The name of this conversion code. This will appear in the administrative interface to easily identify it.'),
    ];
    $form['conversion_id'] = [
      '#type' => 'textfield',
      '#title' => t('Conversion ID'),
      '#default_value' => isset($code) ? $code['conversion_id'] : '',
      '#size' => 15,
      '#maxlength' => 64,
      '#required' => TRUE,
    ];
    // @FIXME
    // $form['conversion_language'] = array(
    //     '#type' => 'textfield',
    //     '#title' => t('Conversion Language'),
    //     '#default_value' => isset($code) ? $code['conversion_language'] : // @FIXME: This looks like another module's variable. Rewrite the call to use the correct configuration set.
    // variable_get('google_adwords_conversion_language', $language->language),
    //     '#size' => 15,
    //     '#maxlength' => 64,
    //     '#required' => TRUE,
    //   );

    // @FIXME
    // $form['conversion_format'] = array(
    //     '#type' => 'textfield',
    //     '#title' => t('Conversion Format'),
    //     '#default_value' => isset($code) ? $code['conversion_format'] : // @FIXME: This looks like another module's variable. Rewrite the call to use the correct configuration set.
    // variable_get('google_adwords_conversion_format', '2'),
    //     '#size' => 15,
    //     '#maxlength' => 64,
    //     '#required' => TRUE,
    //   );

    // @FIXME
    // $form['conversion_color'] = array(
    //     '#type' => 'textfield',
    //     '#title' => t('Conversion Color'),
    //     '#default_value' => isset($code) ? $code['conversion_color'] : // @FIXME: This looks like another module's variable. Rewrite the call to use the correct configuration set.
    // variable_get('google_adwords_conversion_color', 'FFFFFF'),
    //     '#size' => 15,
    //     '#maxlength' => 64,
    //     '#required' => TRUE,
    //   );

    $form['conversion_label'] = [
      '#type' => 'textfield',
      '#title' => t('Conversion Label'),
      '#default_value' => isset($code) ? $code['conversion_label'] : '',
      '#size' => 30,
      '#maxlength' => 64,
      '#required' => TRUE,
    ];
    $form['paths'] = [
      '#type' => 'textarea',
      '#title' => t('Paths'),
      '#default_value' => isset($code) ? $code['paths'] : '',
      '#rows' => 8,
      '#cols' => 128,
      '#required' => TRUE,
      '#description' => t('A list of paths, separated by a new line, where this conversion code should be inserted.'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];

    if ($cid) {
      $form['delete'] = [
        '#type' => 'submit',
        '#value' => t('Delete'),
      ];
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state['clicked_button']['#value'] == 'Save') {
      $form_state->cleanValues();

      google_adwords_path_save_code($form_state->getValues());
      // Updating an existing conversion code.
      if (isset($form_state->getValues()['cid'])) {
        $message = 'Successfully updated %name.';
      }
      // Adding a new conversion code.
      else {
        $message = 'Successfully added %name.';
      }

      drupal_set_message(t($message, ['%name' => $form_state->getValues()['name']]));

      // Redirect back to Google Adwords Path admin page.
      $path = 'admin/config/system/google_adwords/path';
      $response = new RedirectResponse($path);
      $response->send();
    }
    elseif ($form_state['clicked_button']['#value'] == 'Delete') {
      $path = 'admin/config/system/google_adwords/path/' . $form_state->getValues()['cid'] . '/delete';
      $response = new RedirectResponse($path);
      $response->send();
    }
  }
}
