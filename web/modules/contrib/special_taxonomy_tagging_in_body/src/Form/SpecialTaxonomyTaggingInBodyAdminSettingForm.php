<?php

/**
 * @file
 * Contains \Drupal\special_taxonomy_tagging_in_body\Form\SpecialTaxonomyTaggingInBodyAdminSettingForm.
 */

namespace Drupal\special_taxonomy_tagging_in_body\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class SpecialTaxonomyTaggingInBodyAdminSettingForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'special_taxonomy_tagging_in_body_admin_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('special_taxonomy_tagging_in_body.settings');

    // foreach (Element::children($form) as $variable) {
    //   $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    // }
    // $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);

    $select_node_types = $form_state->getValue('select_node_types');
    $taxonomy_list = $form_state->getValue('taxonomy_list');
    $specialChar = $form_state->getValue('special_taxonomy_tagging_in_body_special_taging');
    $target = $form_state->getValue('special_taxonomy_tagging_in_body_target');

     $config->set('select_node_types', $select_node_types)
        ->set('taxonomy_list', $taxonomy_list)
        ->set('special_taxonomy_tagging_in_body_special_taging',$specialChar)
        ->set('special_taxonomy_tagging_in_body_target',$target)
        ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['special_taxonomy_tagging_in_body.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {


    $config = $this->config('special_taxonomy_tagging_in_body.settings');
    // Get List Of All Taxonomy ID.
    $vocabulary = \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();
    $checklist_vocab_array = []; /* Change to array('0' => '--none--'); if you want a none option */
    
    foreach ($vocabulary as $item) {
        $key = $item->id();
        $value = $item->get('name');



      $checklist_vocab_array[$key] = $value;
    }

    // Get List Of Content Type.
    $node_type_raw = node_type_get_names();
    

    // Define Form Field for tagging configuration.
    // @FIXME
    // // @FIXME
    // // This looks like another module's variable. You'll need to rewrite this call
    // // to ensure that it uses the correct configuration object.
    $form['special_taxonomy_tagging_in_body']['select_node_types'] = array(
        '#default_value' => is_array($config->get('select_node_types')) ? array_filter($config->get('select_node_types')) : array(NULL),
        '#options' => $node_type_raw,
        '#type' => 'checkboxes',
        '#title' => t('List of content type in which you want tagging'),
        '#description' => t('List of Content type for special tagging.'),
      );


    $options_taxonomy = ['@' => '@', '#' => '#', '&' => '&', '$' => '$'];
    $form['special_taxonomy_tagging_in_body']['special_taxonomy_tagging_in_body_special_taging'] = [
      '#type' => 'select',
      '#options' => $options_taxonomy,
      //'#default_value' => \Drupal::config('special_taxonomy_tagging_in_body.settings')->get('special_taxonomy_tagging_in_body_special_taging'),
      '#default_value' => $config->get('special_taxonomy_tagging_in_body_special_taging'),
      '#title' => t('Choose one special character for tagging with taxonomy'),
      '#description' => t('Choose single special character with tagging.'),
    ];
    // @FIXME
    // // @FIXME
    // // This looks like another module's variable. You'll need to rewrite this call
    // // to ensure that it uses the correct configuration object.
    $form['special_taxonomy_tagging_in_body']['taxonomy_list'] = array(
        '#default_value' => is_array($config->get('taxonomy_list')) ? array_filter($config->get('taxonomy_list')) : array(NULL),
        '#type' => 'checkboxes',
        '#title' => t('List of current Vocabularies.'),
        '#position' => 'left',
        '#options' => $checklist_vocab_array,
        '#description' => t('List of vocabularies displayed as checkboxes.'),
      );

    $options_special_taxonomy_tagging_in_body_target = [
      '_blank' => 'Blank',
      'none' => 'None',
    ];
    $form['special_taxonomy_tagging_in_body']['special_taxonomy_tagging_in_body_target'] = [
      '#type' => 'select',
      '#options' => $options_special_taxonomy_tagging_in_body_target,
      //'#default_value' => \Drupal::config('special_taxonomy_tagging_in_body.settings')->get('special_taxonomy_tagging_in_body_target'),
      '#default_value' => $config->get('special_taxonomy_tagging_in_body_target'),
      '#title' => t('Target Link'),
      '#description' => t('Target for taxonomy link.'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
