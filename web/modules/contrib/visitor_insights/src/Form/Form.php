<?php
namespace Drupal\visitor_insights\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

define('KEY_LENGTH', 24);
define('KEY_ERROR', t('Invalid key - be sure that you are copying the 24 character key that starts with pa-. Do not include "pa-" in your key. Only digits and letters are permitted.'));

/**
 * Implements an example form.
 */
class Form extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'visitor_insights';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['visitor_insights_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Visitor Insights (Pingdom) Key'),
      '#description' => t('Look for the pingdom key or visitor insights snippet that starts with \'pa-\'
        and copy that into this field. See <a href="https://help.pingdom.com/hc/en-us/articles/115005626165" target="_blank">here</a>
        for more information on how to find that key.'),
      '#required' => TRUE,
      '#default_value' => \Drupal::config('visitor_insights.settings')->get('visitor_insights_key'),
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // This is a single-item field so we only need to validate the first item.
    $key = $form_state->getValue('visitor_insights_key');
    if (strlen($key) < KEY_LENGTH) {
      $form_state->setErrorByName('visitor_insights_key', KEY_ERROR);
    }
    // The key can only contain digits and alphabet characters.
    elseif (preg_match('/[^A-Za-z0-9]/', $key)) {
      $form_state->setErrorByName('visitor_insights_key', KEY_ERROR);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('visitor_insights.settings')->set('visitor_insights_key', $form_state->getValue('visitor_insights_key'))->save();
  }

}
