<?php

/**
 * @file
 * Contains \Drupal\plista\Form\PlistaAdminForm.
 */

namespace Drupal\plista\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class PlistaAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'plista_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $formState) {

    $config = $this->config('plista.settings');

    $plista_basic = $config->get('plista_basic');

    $form['plista_basic'] = array(
      '#type' => 'fieldset',
      '#title' => t('Basic settings'),
      '#weight' => 5,
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
    );

    $form['plista_basic']['plista_javascript_url'] = array(
      '#type' => 'url',
      '#title' => t('Plista javascript url'),
      '#default_value' => isset($plista_basic['plista_javascript_url']) ? $plista_basic['plista_javascript_url'] : '',
      '#size' => 60,
      '#maxlength' => 90,
      '#description' => t('Javascript url for your specific plista account. Usually starts with http://static.plista.com/'),
      '#required' => TRUE,
    );

    $form['plista_basic']['plista_widgetname'] = array(
      '#type' => 'textfield',
      '#title' => t('Plista widgetname'),
      '#default_value' => isset($plista_basic['plista_widgetname']) ? $plista_basic['plista_widgetname'] : '',
      '#size' => 60,
      '#maxlength' => 90,
      '#description' => t('You will receive it from plista'),
      '#required' => TRUE,
    );

    $form['plista_basic']['plista_field_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Node title'),
      '#default_value' => isset($plista_basic['plista_field_title']) ? $plista_basic['plista_field_title'] : '[node:title]',
      '#size' => 60,
      '#maxlength' => 90,
      '#description' => t('Title of the node'),
      '#required' => TRUE,
    );

    $form['plista_basic']['plista_field_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Node summary'),
      '#default_value' => isset($plista_basic['plista_field_text']) ? $plista_basic['plista_field_text'] : '[node:summary]',
      '#size' => 60,
      '#maxlength' => 90,
      '#description' => t('Summary of the node'),
      '#required' => TRUE,
    );

    $form['plista_basic']['plista_field_img'] = array(
      '#type' => 'textfield',
      '#title' => t('Node image'),
      '#default_value' => isset($plista_basic['plista_field_img']) ? $plista_basic['plista_field_img'] : '',
      '#size' => 60,
      '#maxlength' => 90,
      '#description' => t('Small image of the node'),
      '#required' => TRUE,
    );

    $form['plista_basic']['plista_field_category'] = array(
      '#type' => 'textfield',
      '#title' => t('Node category'),
      '#default_value' => isset($plista_basic['plista_field_category']) ? $plista_basic['plista_field_category'] : '[node:field_tags]',
      '#size' => 60,
      '#maxlength' => 90,
      '#description' => t('Category of the node'),
      '#required' => TRUE,
    );
    /*
        $form['plista_basic']['tokens'] = array(
          '#title' => t('Replacement patterns'),
          '#type' => 'fieldset',
          '#theme' => 'token_tree',
          '#token_types' => array('node'),
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
        );
    */
    $types = node_type_get_types();
    $options = array();
    foreach ($types as $type => $info) {
      $options[$type] = $info->name;
    }

    $form['plista_basic']['plista_node_types'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Node types'),
      '#options' => $options,
      '#default_value' => isset($plista_basic['plista_node_types']) ? $plista_basic['plista_node_types'] : array(),
      '#description' => t('Node types on which plista widget should be enabled'),
      '#required' => TRUE,
    );

    $form['plista_basic']['plista_hidden_paths'] = array(
      '#title' => t('Hidden paths'),
      '#type' => 'textarea',
      '#description' => t('Enter URL patterns on which the plista widget should be hidden'),
      '#default_value' => isset($plista_basic['plista_hidden_paths']) ? $plista_basic['plista_hidden_paths'] : '',
    );

    $form['plista_advanced'] = array(
      '#type' => 'fieldset',
      '#title' => t('Advanced settings'),
      '#weight' => 5,
      '#collapsible' => FALSE,
      '#collapsed' => TRUE,
      '#tree' => TRUE,
      '#description' => t("Used for updating and deleting nodes through plista API. The fields are not required. Widget is also working without them."),
    );

    $plista_advanced = $config->get('plista_advanced');
    $form['plista_advanced']['plista_update_url'] = array(
      '#type' => 'url',
      '#title' => t('Update URL'),
      '#default_value' => isset($plista_advanced['plista_update_url']) ? $plista_advanced['plista_update_url'] : 'http://farm.plista.com/api/item/',
      '#size' => 60,
      '#maxlength' => 90,
      '#required' => TRUE,
    );

    $form['plista_advanced']['plista_domain_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Domain ID'),
      '#default_value' => isset($plista_advanced['plista_domain_id']) ? $plista_advanced['plista_domain_id'] : '',
      '#size' => 60,
      '#maxlength' => 90,
    );

    $form['plista_advanced']['plista_api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Plista Api Key'),
      '#default_value' => isset($plista_advanced['plista_api_key']) ? $plista_advanced['plista_api_key'] : '',
      '#size' => 60,
      '#maxlength' => 90,
    );

    return parent::buildForm($form, $formState);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {

    $this->config('plista.settings')
      ->set('plista_basic', $formState->getValue('plista_basic'))
      ->set('plista_advanced', $formState->getValue('plista_advanced'))
      ->save();

    parent::submitForm($form, $formState);
  }

  protected function getEditableConfigNames() {
    return ['plista.settings'];
  }


}
