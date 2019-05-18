<?php

/**
 * @file
 * Contains \Drupal\gamabhana\gamabhanaSettingsForm
 */
namespace Drupal\gamabhana;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * provides Configure settings.
 */
class gamabhanaSettingsForm extends ConfigFormBase {

/**
  * {@inheritdoc}
  */
public function getFormId() {
  return 'gamabhana_admin_settings';
}

/**
  * {@inheritdoc}
  */
protected function getEditableConfigNames() {
  return ['gamabhana.settings'];
}

/**
  * {@inheritdoc}
  */
  
public function buildForm(array $form, FormStateInterface $form_state) {
  $config = $this->config('gamabhana.settings');
  if(empty($config->get('gamabhana_phonetic_ids_char'))) {
    $gamabhana_phonetic_ind = 0;
  }
  else {
	  $gamabhana_phonetic_ind = $config->get('gamabhana_phonetic_ids_char');
  }
	
  if(empty($config->get('gamabhana_phonetic_ids'))) {
    $default_gamabhana_phonetic_ids = "edit-title-0-value edit-keys";
  }
  else {
    $default_gamabhana_phonetic_ids = $config->get('gamabhana_phonetic_ids');
  }
    
  if(empty($config->get('gamabhana_phonetic_default_lang'))) {
    $gamabhana_phonetic_default_lang = '__roman__';
  }
  else {
    $gamabhana_phonetic_default_lang = $config->get('gamabhana_phonetic_default_lang');
  }
    
  if(empty($config->get('gamabhana_phonetic_secondary_lang'))) {
    $gamabhana_phonetic_secondary_lang = '__devanagari__';
  }
  else {
    $gamabhana_phonetic_secondary_lang = $config->get('gamabhana_phonetic_secondary_lang');
  }
	
  if(empty($config->get('gamabhana_default_lang_label'))) {
    $gamabhana_default_lang_label = 'English';
  }
  else {
    $gamabhana_default_lang_label = $config->get('gamabhana_default_lang_label');
  }
	
  if(empty($config->get('gamabhana_secondary_lang_label'))) {
    $gamabhana_secondary_lang_label = 'Marathi';
  }
  else {
    $gamabhana_secondary_lang_label = $config->get('gamabhana_secondary_lang_label');
  }
    
  $form['gamabhana_phonetic_ind'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable gamabhana phonetics'),
    '#default_value' => $gamabhana_phonetic_ind,
    '#description' => t("Add gamabhana phonetics to elements with element IDs specified below.")
  );
    
  $form['gamabhana_phonetic_ids'] = array(
    '#type' => 'textarea',
    '#title' => t('Element IDs'),
    '#required' => true,
    '#default_value' => $default_gamabhana_phonetic_ids,
    '#description' => t('White space separated list of element IDs of elements for which Gamabhana phonetics will be enabled.')
  );
    
  $form['gamabhana_phonetic_default_lang'] = array(
    '#type' => 'select',
    '#title' => t('Default typing language/script'),
    '#default_value' => $gamabhana_phonetic_default_lang,
    '#options' => gamabhana_getLangList(1),
    '#required' => true,
    '#description' => t('Select default language/script for typing using gamabhana phonetics.')
  );
	
  $form['gamabhana_default_lang_label'] = array(
    '#type' => 'textfield',
    '#title' => t('Default language label'),
    '#default_value' => $gamabhana_default_lang_label,
    '#required' => true,
    '#description' => t('Enter label for default language.'),
  );
    
  $form['gamabhana_phonetic_secondary_lang'] = array(
    '#type' => 'select',
    '#title' => t('Secondary typing language/script'),
    '#default_value' => $gamabhana_phonetic_secondary_lang,
    '#options' => gamabhana_getLangList(2),
    '#required' => true,
    '#description' => t('Select secondary language/script for typing using gamabhana phonetics. Do not forget to change labels in gamabhana block accordingly.')
  );
	
  $form['gamabhana_secondary_lang_label'] = array(
    '#type' => 'textfield',
    '#title' => t('Secondary language label'),
    '#default_value' => $gamabhana_secondary_lang_label,
    '#required' => true,
    '#description' => t('Enter label for secondary language.'),
  );
	    
  $form['gamabhana_mls_ind'] = array(
    '#type' => 'checkbox',
    '#title' => t('Multiple Language Selection'),
    '#default_value' => $config->get('gamabhana_mls_ind', 0),
    '#description' => t("Enable multiple language selection for primary language. This will show drop-down selection in gamabhana block")
  );
    
  return parent::buildForm($form, $form_state);
}

/**
  * {@inheritdoc}
  */
public function submitForm(array &$form, FormStateInterface $form_state) {
  $this->config('gamabhana.settings')->set('gamabhana_phonetic_ind', $form_state->getValue('gamabhana_phonetic_ind'))
	->set('gamabhana_phonetic_ids', $form_state->getValue('gamabhana_phonetic_ids'))
	->set('gamabhana_phonetic_default_lang', $form_state->getValue('gamabhana_phonetic_default_lang'))
	->set('gamabhana_phonetic_secondary_lang', $form_state->getValue('gamabhana_phonetic_secondary_lang'))
	->set('gamabhana_mls_ind', $form_state->getValue('gamabhana_mls_ind'))
	->set('gamabhana_default_lang_label', $form_state->getValue('gamabhana_default_lang_label'))
	->set('gamabhana_secondary_lang_label', $form_state->getValue('gamabhana_secondary_lang_label'))->save();
    
  parent::submitForm($form, $form_state);
  }
}

/* Return languages array for select options for gamabhan settings form */
function gamabhana_getLangList($ind) {
  if($ind == 2) {
    return array(
      '__devanagari__' => 'Hindi/Marathi',
      '__gujarati__' => 'Gujarati',
      '__gurumukhi__' => 'Gurumukhi',
      '__bengoli__' => 'Bengoli',
      '__kannada__' => 'Kannada',
      '__malayalam__' => 'Malayalam',
      '__tamil__' => 'Tamil',
      '__telugu__' => 'Telugu',
      '__urdu__' => 'Urdu',
      '__roman__' => 'English'
    );
  }
  
  if($ind == 1) {
    return array(
      '__roman__' => 'English',
      '__devanagari__' => 'Hindi/Marathi',
      '__gujarati__' => 'Gujarati',
      '__gurumukhi__' => 'Gurumukhi',
      '__bengoli__' => 'Bengoli',
      '__kannada__' => 'Kannada',
      '__malayalam__' => 'Malayalam',
      '__tamil__' => 'Tamil',
      '__telugu__' => 'Telugu',
      '__urdu__' => 'Urdu'
    );
  }
}
