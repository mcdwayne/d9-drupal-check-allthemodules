<?php

namespace Drupal\masked_input;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * provides Configure settings.
 */

class MaskedInputSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'masked_input_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'masked_input.settings'
    ];
  }

 /**
   * {@inheritdoc}
   */
  
  public function buildForm(array $form, FormStateInterface $form_state) {   

   $config = $this->config('masked_input.settings');    
  	$definitions = $config->get('masked_input_definitions');
  	$definitions_count = count($definitions) + 1;
    
    $form['definition'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Mask Definitions'),
    );
    
    $form['definition']['definitions'] = array(
        '#type' => 'table',
        '#prefix' => '<div id="masked_input-definitions">',
        '#suffix' => '</div>',
        '#tree' => TRUE,
    );
    
    for ($i = 0; $i < $definitions_count; $i++) {
      $form['definition']['definitions'][$i] = _maskinput_definitions_element($i, $definitions);
    }
    
    $form['definition']['add_another_definition'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Add another'),
        '#submit' => array('_masked_input_definitions_add_another_submit'),
        '#ajax' => array(
            'callback' => '_masked_input_definitions_add_another_callback',
            'wrapper' => 'masked_input-definitions',
        ),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state_values = $form_state->getValue(['definitions']);
    foreach ($form_state_values as $key => $value) {
      if (empty($value['character']) || empty($value['regex'])) {
        unset($form_state_values[$key]);
      }
    }
    
    $this->config('masked_input.settings')->set('masked_input_definitions', $form_state_values)->save();
    
    parent::submitForm($form, $form_state);
  }


/**
 * Ajax callback: return definitions element.
 * @see masked_input_settings()
 */
function _masked_input_definitions_add_another_callback(array &$form, FormStateInterface $form_state) {
  return $form['definition']['definitions'];
}


/**
 * Ajax submission callback: adds another definition form element.
 * @see masked_input_settings()
 */
function _masked_input_definitions_add_another_submit(array &$form, FormStateInterface $form_state) {
  $form_state['masked_input']['count']++;
  $form_state['rebuild'] = TRUE;
  $form['definition']['definitions'][] = _maskinput_definitions_element();
}
}
/**
 * Helper function: builds masked input definition form element.
 */
function _maskinput_definitions_element($delta = 'new', $definitions = array()) {
  return array(
      '#type' => 'container',
      '#attributes' => array(),
      'character' => array(
          '#type' => 'textfield',
          '#size' => 1,
          '#maxlength' => 1,
          '#default_value' => isset($definitions[$delta]['character']) ? $definitions[$delta]['character'] : '',
      ),
      'regex' => array(
          '#type' => 'textfield',
          '#size' => 40,
          '#default_value' => isset($definitions[$delta]['regex']) ? $definitions[$delta]['regex'] : '',
      ),
      'description' => array(
          '#type' => 'textfield',
          '#size' => 40,
          '#default_value' => isset($definitions[$delta]['description']) ? $definitions[$delta]['description'] : '',
      ),
  );
}
