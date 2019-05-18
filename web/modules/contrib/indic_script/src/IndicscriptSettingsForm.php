<?php

/**
 * @file
 * Contains \Drupal\indic_script\IndicscriptSettingsForm
 */
namespace Drupal\indic_script;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure hello settings for this site.
 */
class IndicscriptSettingsForm extends ConfigFormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'indic_script_admin_settings';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'indic_script.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('indic_script.settings');
    $form_fields = indic_script_get_languages('lang');
  
    $options['en'] = t('English');
    $options = array();
    foreach ($form_fields as $field) {  
      $options[$field['method']] = $field['title'];
    }

	$form['indic_script_langs'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Languages and typing methods'),
      '#default_value' => $config->get('indic_script_langs'),
      '#options' => $options,
      '#multiple' => TRUE,
      '#description' => t('Choose the Languages and typing methods you need to available to Site Users.'),
    );

	$form['indic_script_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Visibility settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
  
   $form['indic_script_settings']['indic_script_page_excl_mode'] = array(
     '#type' => 'radios',
     '#title' => t('Page inclusion or exclusion mode'),
     '#default_value' =>$config->get('indic_script_page_excl_mode'),
     '#options' => array('0' => t('All pages expect the following pages'), '1' => t('Only the following pages')),
     '#description' => t('Choose the way of disabling/enabling Indic Script on selected paths'),
   );

   /**
    * get excluded paths - so we can have normal textareas too
    * split the phrase by any number of commas or space characters,
    * which include " ", \r, \t, \n and \f
    */
  $form['indic_script_settings']['indic_script_excl_paths'] = array(
    '#type' => 'textarea',
    '#title' => t('Paths to exclude/include'),
    '#cols' => 60,
    '#rows' => 5,
    '#default_value' =>$config->get('indic_script_excl_paths'),
    '#description' => t("Enter one page per line as Drupal paths start with /. The '*' character is a wildcard. Example paths are blog for the blog page and blog/* for every personal blog. <front> is the front page."),
  );

  $form['indic_script_settings']['indic_script_excl_mode'] = array(
    '#type' => 'select',
    '#title' => t('Use inclusion or exclusion mode'),
    '#default_value' => $config->get('indic_script_excl_mode'),
    '#options' => array('0' => t('exclude'), '1' => t('include')),
    '#description' => t('Choose the way of disabling/enabling Indic Script on selected fields/paths (see below). Use exclude to disable Indic Script on selected fields/paths. Use include if you want to load Indic Script only on selected paths/fields.'),
  );

  /**
   * get excluded fields - so we can have normal textareas too
   * split the phrase by any number of commas or space characters,
   * which include " ", \r, \t, \n and \f
   */
  $form['indic_script_settings']['indic_script_excl_fields'] = array(
    '#type' => 'textarea',
    '#title' => t('Fields to exclude/include'),
    '#cols' => 60,
    '#rows' => 5,
    '#default_value' => $config->get('indic_script_excl_fields'),
    '#description' => t("Enter names (HTML ID's) of fields that may or may not have an Indic Script, depending on the chosen option for the inclusion/exclusion mode.<br />You may separate the different entries by commas, spaces or newlines."),
  );

  // demo page switch
  $form['indic_script_settings']['indic_script_enable_demo'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable Tamil Typing Demo Page?'),
    '#options' => array(0 => t('Disable'), 1 => t('Enable')),
    '#default_value' => $config->get('indic_script_enable_demo'),
    '#description' => t("If checked, the new page will be available at path tamil_type."),
  );
    
  return parent::buildForm($form, $form_state);
}

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('indic_script.settings')
      ->set('indic_script_langs', $form_state->getValue('indic_script_langs'))
      ->set('indic_script_page_excl_mode', $form_state->getValue('indic_script_page_excl_mode'))
      ->set('indic_script_excl_paths', $form_state->getValue('indic_script_excl_paths'))
      ->set('indic_script_excl_mode', $form_state->getValue('indic_script_excl_mode'))
      ->set('indic_script_excl_fields', $form_state->getValue('indic_script_excl_fields'))
      ->set('indic_script_enable_demo', $form_state->getValue('indic_script_enable_demo'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
