<?php
/**
* @file
* Contains \Drupal\pinger\Form\PingerSiteForm.
*/

namespace Drupal\pinger\Form;

use Drupal\Core\Form\ConfigFormBase;

class PingerSiteForm extends ConfigFormBase {

  public function getFormID() {
    return 'pinger_site_form';
  }

  public function buildForm(array $form, array &$form_state) {
    $form['url'] = array(
      '#type' => 'textfield',
      '#title' => t('URL'),
      '#description' => t('Enter the ABSOLUTE URL of the site that you would like to monitor.'),
    );

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, array &$form_state) {
    $vals = $form_state['values'];
    if (!valid_url($vals['url'], TRUE)) {
      form_set_error('url', 'The url you entered is not valid.');
    }
  }

  public function submitForm(array &$form, array &$form_state) {
    $vals = $form_state['values'];
    $url = $vals['url'];

    $site = entity_create('pinger_site', array(
      'url' => $url,
    ));
    $site->save();
    if ($site->id()){
      drupal_set_message(t('The site @url was saved.', array('@url' => $url)));
    }

    parent::submitForm($form, $form_state);
  }
}
