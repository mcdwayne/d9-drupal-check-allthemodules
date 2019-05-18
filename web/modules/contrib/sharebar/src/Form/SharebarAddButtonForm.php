<?php
/**
 * @file
 * Contains \Drupal\sharebar\Form\SharebarAddButtonForm.
 */

namespace Drupal\sharebar\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\Core\Form\ConfigFormBase;


/**
 * Implements an example form.
 */
class SharebarAddButtonForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'sharebar_addbutton';
  }

  /**
   * Form builder: Configure the sharebar system.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $args = explode('/', current_path());

    $mname = $args[4];
    $array1 = '';
    $button = (object)$array1;
    $button->name = $button->machine_name = $button->big_button = $button->small_button = $button->enabled = $button->weight = '';
    if ($mname) {
      $buttons = unserialize(\Drupal::config('sharebar.settings')->get('sharebar_buttons'));
      if (empty($buttons)) {
        $buttons = unserialize(sharebar_buttons_def());
      }

     // print_r($buttons); die;

      if (is_array($buttons) && count($buttons)) {
        $button = $buttons[$mname];
      }
    }
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#default_value' => $button->name,
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    $form['machine_name'] = array(
      '#type' => 'machine_name',
      '#default_value' => $button->machine_name,
      '#maxlength' => 21,
      '#machine_name' => array(
        'exists' => 'sharebar_machine_name_load',
      ),
    );
    $form['old_machine_name'] = array(
      '#type' => 'value',
      '#value' => $button->machine_name,
    );
    $form['big_button'] = array(
      '#type' => 'textarea',
      '#title' => t('Big Button'),
      '#default_value' => $button->big_button,
      '#required' => TRUE,
    );
    $form['small_button'] = array(
      '#type' => 'textarea',
      '#title' => t('Small Button'),
      '#default_value' => $button->small_button,
      '#required' => TRUE,
    );
    $form['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#default_value' => $button->enabled,
    );

    $form['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight'),
      '#delta' => 50,
      '#default_value' => $button->weight,
    );

    $form['submit'] = array('#type' => 'submit', '#value' => t('Save'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
   // echo "<pre>"; print($form_state); echo "<hello>"; die;

    if ($form_state->getValue('clicked_button') == t('Delete')) {
      //if ($form_state['clicked_button']['#value'] == t('Delete')) {
      $form_state['rebuild'] = TRUE;
      $form_state['confirm_delete'] = TRUE;
      return;
    }

    $buttons = unserialize(\Drupal::config('sharebar.settings')->get('sharebar_buttons'));
    if (empty($buttons)) {
      $buttons = unserialize(sharebar_buttons_def());
    }

    if ($form_state->getValue('old_machine_name') != '' && $form_state->getValue('old_machine_name') != $form_state->getValue('machine_name')) {
      unset($buttons[$form_state['values']['old_machine_name']]);
    }

    $array1 = '';
    //$button = (object)$array1;
    $m_name = $form_state->getValue('machine_name');
    $buttons[$m_name] = (object)$array1;
    $buttons[$m_name]->machine_name = $m_name;
    $buttons[$m_name]->name = $form_state->getValue('name');
    $buttons[$m_name]->big_button = $form_state->getValue('big_button');
    $buttons[$m_name]->small_button = $form_state->getValue('small_button');
    $buttons[$m_name]->enabled = $form_state->getValue('enabled');
    $buttons[$m_name]->weight = $form_state->getValue('weight');

    //variable_set('sharebar_buttons', serialize($buttons));

   // Drupal::config('sharebar.settings')->set('sharebar_buttons')

    $this->config('sharebar.settings')
      ->set('sharebar_buttons', serialize($buttons))
      ->save();

   // $form_state['redirect'] = 'admin/config/sharebar/settings';

    $form_state->setRedirect('sharebar.admin_settings');
  }
}
?>