<?php

/**
 * @file
 * Contains Drupal\gpx_track_elevation\Form\GPXTrackEleForm.
 */

namespace Drupal\gpx_track_elevation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class GPXTrackEleForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'gpx_track_elevation_form';
  }
  
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor
    $form = parent::buildForm($form, $form_state);
    // Default settings
    $config = $this->config('gpx_track_elevation.settings');
    
    $form['general_configuration'] = array(
      '#type' => 'fieldset',
      '#title' => t('General configuration'),
      '#collapsible' => FALSE,
    );
    $form['general_configuration']['bilink'] = array(
      '#type' => 'checkbox',
      '#default_value' => $config->get('gpx_track_elevation.bilink'), //?:$config->get('gpx_track_elevation.bilink')0
      '#title' => t('Enable bidirectional link'),
      '#required' => FALSE,
      '#description' => t('Use with caution: enabling bidirectional link between map and elevation profile uses a lot of browser resources and highly reduces site performances.'),
    );

    $form['general_configuration']['trcolor'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('gpx_track_elevation.trcolor'),
      '#title' => t('Track color'),
      '#required' => TRUE,
      '#description' => t('Select the track color.'),
    );

    $form['general_configuration']['epcolor'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('gpx_track_elevation.epcolor'), //'#006CAB'
      '#title' => t('Elevation profile color'),
      '#required' => TRUE,
      '#description' => t('Select the track color.'),
    );

    $form['general_configuration']['trstroke'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('gpx_track_elevation.trstroke'), // 2),
      '#title' => t('Track stroke'),
      '#required' => TRUE,
      '#description' => t('Select the track stroke weight.'),
    );

    $form['general_configuration']['maptype'] = array(
      '#type' => 'select',
      '#default_value' => $config->get('gpx_track_elevation.maptype'), //'TERRAIN'),
      '#title' => t('Map Type'),
      '#required' => TRUE,
      '#options' => array(
        'TERRAIN' => t('Terrain'),
        'ROAD' => t('Road'),
        'HYBRID' => t('Hybrid'),
        'SATELLITE' => t('Satellite'),
      ),
      '#description' => t('Select the map default type'),
    );

      $form['general_configuration']['google_map_key'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('gpx_track_elevation.google_map_key'),
      '#title' => t('Google Map API Key'),
      '#required' => FALSE,
      '#description' => t('Insert the Google API Key to use.'),
    );

    $form['general_configuration']['http'] = array(
      '#type' => 'select',
      '#default_value' => $config->get('gpx_track_elevation.http'), //'https'),
      '#title' => t('HTTP or HTTPS'),
      '#required' => TRUE,
      '#options' => array(
        'https' => 'https',
        'http' => 'http',
      ),
      '#description' => t('Select protocol to be used with Google Maps API'),
    );  

    $form['node_types_configuration'] = array(
      '#type' => 'fieldset',
      '#title' => t('Configuration for entity types'),
      '#collapsible' => FALSE,
    );

    foreach (\Drupal::entityTypeManager()->getDefinitions() as $entityTypeId => $entityType) {
      
      foreach ($this->getValidBundles($entityType) as $bundlename => $bundleEntities) {
        $form['node_types_configuration'][$bundlename] = array(
          '#type' => 'fieldset',
          '#title' => $bundleEntities[0],
          '#collapsible' => FALSE,
        );
        
        foreach ($bundleEntities[1] as $bundleentity => $bundlabel) {
            $form['node_types_configuration'][$bundlename][$bundlename.$bundleentity] = array(
              '#type' => 'select',
              '#options' => array('0'=>$this->t('No'), '1'=>$this->t('Yes')),
              '#default_value' => $config->get("gpx_track_elevation.$bundlename.$bundleentity"),
              '#title' => $bundlabel,
              '#required' => FALSE,
              '#description' => t('Select field to get the GPX from'),
            );
        }
      }
    }   

    
    return $form;
    
  }
  
  /**
   * {@inheritdoc}.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if (!$this->validate_color($form_state->getValue('trcolor'))) {
        $form_state->setErrorByName('trcolor', t('The color must be a valid color code in the form #xxyyzz or #xyz'));
    }

    if (!$this->validate_color($form_state->getValue('trcolor'))) {
        $form_state->setErrorByName('epcolor', t('The color must be a valid color code in the form #xxyyzz or #xyz'));
    }
    $trstroke = is_numeric($form_state->getValue('trstroke'))?floatval($form_state->getValue('trstroke')) == intval($form_state->getValue('trstroke'))?
      intval($form_state->getValue('trstroke')):0:0;

    if ($trstroke < 1) {
      $form_state->setErrorByName('trstroke', t('Track stroke has to be a positive integer'));
    }
    

  }

  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $config = $this->config('gpx_track_elevation.settings');
  
    $config->set('gpx_track_elevation.bilink', $form_state->getValue('bilink'));
    $config->set('gpx_track_elevation.trcolor', $form_state->getValue('trcolor'));
    $config->set('gpx_track_elevation.epcolor', $form_state->getValue('epcolor'));
    $config->set('gpx_track_elevation.trstroke', $form_state->getValue('trstroke'));
    $config->set('gpx_track_elevation.maptype', $form_state->getValue('maptype'));
    $config->set('gpx_track_elevation.google_map_key', $form_state->getValue('google_map_key'));
    $config->set('gpx_track_elevation.http', $form_state->getValue('http'));

    
    foreach (\Drupal::entityTypeManager()->getDefinitions() as $entityTypeId => $entityType) {
      foreach ($this->getValidBundles($entityType) as $bundlename => $bundleEntities) {
        foreach ($bundleEntities[1] as $bundleentity => $bundlabel) {
          $config->set("gpx_track_elevation.$bundlename.$bundleentity", $form_state->getValue($bundlename.$bundleentity));
        }
      }
    }
    
    $config->save();
    
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  protected function getEditableConfigNames() {
    return [
      'gpx_track_elevation.settings',
    ];
  }
  
  private function validate_color($color) {
    if (preg_match("/^#([a-f0-9]{3}|[a-f0-9]{6})$/i", $color)) {
      return true;
    }
    return false;
  }

  private function gpx_track_elevation_get_valid_field($entity, $bundle, $allow_empty, &$file_gpx = NULL) { //useless
    $good_fields = array();
    if ($allow_empty) {
      $good_fields[''] = t('- Disabled -');
    }
    $myentityManager = \Drupal::service('entity_field.manager');
    $fields = $myentityManager->getFieldDefinitions($entity, $bundle);
    foreach ($fields as $key => $field) {
      if (($field->getType() == 'file') && ($field->getFieldStorageDefinition()->getCardinality() ==1)) {
        $good_fields[$key] = $field->getLabel();//$field['label'];
      }
    }
    return $good_fields;
  }
  
  private function getValidBundles($entityType) {
    $validBundles  = array();
    if ($entityType->getGroup() == 'content') {
      $myBundleName = $entityType->getBundleEntityType();
      $isFieldable = (null!==$entityType->get('field_ui_base_route'))?'Fieldable':'Not fieldable';
      if($isFieldable == 'Fieldable') {
        $validBundles[$entityType->id()][0] = $entityType->getLabel();
        foreach(\Drupal::service('entity_type.bundle.info')->getBundleInfo($entityType->id()) as $indice =>$valore) {
          $validBundles[$entityType->id()][1][$indice] = $valore['label'];
        }
      }
    }
    return $validBundles;
  }
}