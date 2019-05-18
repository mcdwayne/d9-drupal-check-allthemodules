<?php

namespace Drupal\facebook_comments_box\Form;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure custom settings for this site.
 */
class CustomForm extends ConfigFormBase {
  /**
   * Constructor for ComproCustomForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'facebook_comments_box_admin_form';
  }
  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['config.facebook_comments_box'];
  }
  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $form['facebook_comments_box_global'] = array(
    '#type' => 'fieldset',
    '#title' => t('Global Settings'),
    '#collapsible' => TRUE,
    );

    $form['facebook_comments_box_global']['facebook_comments_box_admin_id'] = array(
    '#type' => 'textfield',
    '#title' => t('Facebook Admin ID'),
    '#default_value' => $this->config('facebook_comments_box.settings')->get('facebook_comments_box_admin_id'),
    '#description' => t('Your Facebook Admin Username, ID or App ID. More than one admin can be separated by commas.'),
    '#required' => TRUE,
    );

    $form['facebook_comments_box_default_block'] = array(
    '#type' => 'fieldset',
    '#title' => t('Default Block Settings'),
    '#collapsible' => TRUE,
   );

   $form['facebook_comments_box_default_block']['facebook_comments_box_default_comments'] = array(
    '#type' => 'select',
    '#title' => t('Default Number of Comments'),
    '#default_value' => $this->config('facebook_comments_box.settings')->get('facebook_comments_box_default_comments'),
    '#options' => array(
      10 => 10,
      20 => 20,
      30 => 30,
      50 => 50,
      100 => 100,
    ),
    '#description' => t('The number of comments to show by default on the page displaying them.'),
    '#required' => TRUE,
    );


    $form['facebook_comments_box_default_block']['facebook_comments_box_default_width'] = array(
    '#type' => 'textfield',
    '#title' => t('Default Width of Comments (in pixels)'),
    '#default_value' => $this->config('facebook_comments_box.settings')->get('facebook_comments_box_default_width'),
    '#size' => 4,
    '#maxlength' => 4,
    '#description' => t('The width of the comments with a minimum of 400px.'),
    '#required' => TRUE,
   );

    $form['facebook_comments_box_default_block']['facebook_comments_box_default_theme'] = array(
    '#type' => 'select',
    '#title' => t('Default Theme'),
    '#default_value' => $this->config('facebook_comments_box.settings')->get('facebook_comments_box_default_theme'),
    '#options' => array(
      'light' => t('Light'),
      'dark' => t('Dark'),
    ),
    '#description' => t('The default theme to use for comments.'),
    '#required' => TRUE,
    );


    //Store the node type keys as values for easier comparison.
    $fcb_all_node_types_keys = array_keys(node_type_get_types());
    $fcb_all_node_types = array();
    foreach ($fcb_all_node_types_keys as $node_type) {
    $fcb_all_node_types[$node_type] = $node_type;
    }

    $form['facebook_comments_box_default_block']['facebook_comments_box_default_node_types'] = array(
    '#type' => 'select',
    '#title' => t('Default Node Types'),
    '#default_value' => $this->config('facebook_comments_box.settings')->get('facebook_comments_box_node_types'),
    '#options' => $fcb_all_node_types,
    '#multiple' => TRUE,
    '#description' => t('The node types to attach comments to. This will make comments available for each of the selected node types.'),
    '#required' => TRUE,
    );

    

    return parent::buildForm($form, $form_state);
  }

  /**
   * Youtube API credentials form validate.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $admin_id = $form_state->getValue('facebook_comments_box_admin_id');
    $default_comments = $form_state->getValue('facebook_comments_box_default_comments');
    $default_width = $form_state->getValue('facebook_comments_box_default_width');
    $default_theme = $form_state->getValue('facebook_comments_box_default_theme');
    $node_types = $form_state->getValue('facebook_comments_box_default_node_types');
    
    if (isset($admin_id) && isset($default_comments) && isset($default_width) && isset($default_theme) && isset($node_types)) {
      $this->configFactory()->getEditable('facebook_comments_box.settings')->set('facebook_comments_box_admin_id', $admin_id)->save();
      $this->configFactory()->getEditable('facebook_comments_box.settings')->set('facebook_comments_box_default_comments', $default_comments)->save();
      $this->configFactory()->getEditable('facebook_comments_box.settings')->set('facebook_comments_box_default_width', $default_width)->save();
      $this->configFactory()->getEditable('facebook_comments_box.settings')->set('facebook_comments_box_default_theme', $default_theme)->save();
      $this->configFactory()->getEditable('facebook_comments_box.settings')->set('facebook_comments_box_node_types', $node_types)->save();
      
      drupal_set_message(t('Facebook comments box credentials saved successfully'));
    }
  }

}
