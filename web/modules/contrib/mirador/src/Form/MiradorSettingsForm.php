<?php

/**
 * @file
 * Administrative class form for the mirador module.
 *
 * Contains \Drupal\mirador\Form\MiradorSettingsForm.
 */

namespace Drupal\mirador\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contribute form.
 */
class MiradorSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mirador_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mirador.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mirador.settings');
    // IIIF image server settings.
    $form['image_server_settings'] = array(
      '#type' => 'details',
      '#title' => t('Image Server Settings'),
      '#open' => TRUE,
    );
    $form['image_server_settings']['iiif_server'] = array(
      '#type' => 'textfield',
      '#title' => t('IIIF Image server location'),
      '#default_value' => $config->get('iiif_server'),
      '#required' => TRUE,
      '#description' => t('Please enter the image server location with trailing slash. eg:  http://www.example.org/iiif/.'),
    );
    $default_endpoint = $config->get('endpoint');
    $disable_rest_endpoint = TRUE;
    $disable_rest_endpoint_help_text = t(' Please enable the modules RESTfull Web Services
      and Mirador Annotations for using "Rest Service" Annotation Endpoint.');
    if (\Drupal::moduleHandler()->moduleExists('rest') &&
      \Drupal::moduleHandler()->moduleExists('mirador_annotation')) {
      $disable_rest_endpoint = FALSE;
      $disable_rest_endpoint_help_text = NULL;
    }
    if (empty($config->get('endpoint'))) {
      $default_endpoint = 'custom_endpoint';
    }
    $form['endpoint'] = array(
      '#type' => 'radios',
      'rest_endpoint' => array(
        '#type' => 'radio',
        '#title' => t('Rest Service'),
        '#return_value' => 'rest_endpoint',
        '#default_value' => $default_endpoint,
        '#parents' => array('endpoint'),
        '#spawned' => TRUE,
        '#disabled' => $disable_rest_endpoint,
        '#description' => $disable_rest_endpoint_help_text,
      ),
      'custom_endpoint' => array(
        '#type' => 'radio',
        '#title' => t('Custom End Point'),
        '#return_value' => 'custom_endpoint',
        '#default_value' => $default_endpoint,
        '#parents' => array('endpoint'),
        '#spawned' => TRUE,
      ),
      '#title' => t('Annotation Endpoint'),
      '#default_value' => $default_endpoint,
      '#description' => t('Select the annotation endpoint method.'),
    );
    $form['annotation_settings'] = array(
      '#type' => 'details',
      '#title' => t('Annotation Settings'),
      '#open' => FALSE,
      '#states' => array(
        'visible' => array(
          ':input[name="endpoint"]' => array('value' => 'rest_endpoint'),
        ),
      ),
    );
    $form['annotation_settings']['annotation_entity'] = array(
      '#type' => 'textfield',
      '#title' => t('Entity'),
      '#default_value' => $config->get('annotation_entity'),
      '#size' => 30,
      '#description' => t('The entity to which the annotations should be stored.'),
      '#states' => array(
        'visible' => array(
          ':input[name="endpoint"]' => array('value' => 'rest_endpoint'),
        ),
      ),
    );
    $form['annotation_settings']['annotation_bundle'] = array(
      '#type' => 'textfield',
      '#title' => t('Bundle'),
      '#default_value' => $config->get('annotation_bundle'),
      '#size' => 30,
      '#description' => t('The bundle of the entity to which the annotations should be stored.'),
      '#states' => array(
        'visible' => array(
          ':input[name="endpoint"]' => array('value' => 'rest_endpoint'),
        ),
      ),
    );
    // Annotation field mapping settings.
    $form['annotation_settings']['annotation_field_mappings'] = array(
      '#type' => 'details',
      '#title' => t('Annotation Field Mapping'),
      '#states' => array(
        'visible' => array(
          ':input[name="endpoint"]' => array('value' => 'rest_endpoint'),
        ),
      ),
      '#open' => FALSE,
    );
    $form['annotation_settings']['annotation_field_mappings']['annotation_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Annotation Text'),
      '#default_value' => $config->get('annotation_text'),
      '#size' => 30,
      '#description' => t('The machine name of the field to which the annotation text to be stored.'),
    );
    $form['annotation_settings']['annotation_field_mappings']['annotation_viewport'] = array(
      '#type' => 'textfield',
      '#title' => t('Annotation View Port'),
      '#default_value' => $config->get('annotation_viewport'),
      '#size' => 30,
      '#description' => t('The machine name of the field to which the annotation view port to be stored.'),
    );
    $form['annotation_settings']['annotation_field_mappings']['annotation_image_entity'] = array(
      '#type' => 'textfield',
      '#title' => t('Annotation Image Entity'),
      '#default_value' => $config->get('annotation_image_entity'),
      '#size' => 30,
      '#description' => t('The machine name of the entity reference field to which the resource entity should be stored.'),
    );
    $form['annotation_settings']['annotation_field_mappings']['annotation_resource'] = array(
      '#type' => 'textfield',
      '#title' => t('Annotation Resource'),
      '#default_value' => $config->get('annotation_resource'),
      '#size' => 30,
      '#description' => t('The machine name of the field to store the annotation resource url.'),
    );
    $form['annotation_settings']['annotation_field_mappings']['annotation_data'] = array(
      '#type' => 'textfield',
      '#title' => t('Annotation Data'),
      '#default_value' => $config->get('annotation_data'),
      '#size' => 30,
      '#description' => t('The machine name of the field to store the annotation data.'),
    );

    // Annotation endpoint settings.
    $form['annotation_endpoints'] = array(
      '#type' => 'details',
      '#title' => t('Annotation End points'),
      '#states' => array(
        'visible' => array(
          ':input[name="endpoint"]' => array('value' => 'custom_endpoint'),
        ),
      ),
      '#open' => FALSE,
    );

    // Create endpoint.
    $form['annotation_endpoints']['create'] = array(
      '#type' => 'details',
      '#title' => t('Create End point'),
      '#open' => FALSE,
    );
    $form['annotation_endpoints']['create']['annotation_create'] = array(
      '#type' => 'textfield',
      '#title' => t('Annotation create endpoint'),
      '#default_value' => $config->get('annotation_create'),
      '#size' => 30,
      '#description' => t('The annotation create endpoint'),
    );
    $form['annotation_endpoints']['create']['annotation_create_method'] = array(
      '#type' => 'select',
      '#title' => t('Annotation create method'),
      '#options' => array('POST' => t('POST'), 'GET' => t('GET')),
      '#default_value' => $config->get('annotation_create_method'),
      '#description' => t('The http method used for annotation creation'),
    );

    // Search endpoint.
    $form['annotation_endpoints']['search'] = array(
      '#type' => 'details',
      '#title' => t('Search End point'),
      '#open' => FALSE,
    );
    $form['annotation_endpoints']['search']['annotation_search'] = array(
      '#type' => 'textfield',
      '#title' => t('Annotation search endpoint'),
      '#default_value' => $config->get('annotation_search'),
      '#size' => 30,
      '#description' => t('The annotation search endpoint. please use token {resource_entity_id} for referring resource entity.'),
    );
    $form['annotation_endpoints']['search']['annotation_search_method'] = array(
      '#type' => 'select',
      '#title' => t('Annotation search method'),
      '#options' => array('GET' => t('GET')),
      '#default_value' => $config->get('annotation_create_method'),
      '#description' => t('The http method used for annotation creation'),
    );

    // Update endpoint.
    $form['annotation_endpoints']['update'] = array(
      '#type' => 'details',
      '#title' => t('Update End point'),
      '#open' => FALSE,
    );
    $form['annotation_endpoints']['update']['annotation_update'] = array(
      '#type' => 'textfield',
      '#title' => t('Annotation update endpoint'),
      '#default_value' => $config->get('annotation_update'),
      '#size' => 30,
      '#description' => t('The annotation update endpoint. please use token {annotation_id} for referring annotation entity.'),
    );
    $form['annotation_endpoints']['update']['annotation_update_method'] = array(
      '#type' => 'select',
      '#title' => t('Annotation update method'),
      '#options' => array('PATCH' => t('PATCH'), 'PUT' => t('PUT')),
      '#default_value' => $config->get('annotation_update_method'),
      '#description' => t('The http method used for annotation updation'),
    );

    // Delete Eendpoint.
    $form['annotation_endpoints']['delete'] = array(
      '#type' => 'details',
      '#title' => t('Delete End point'),
      '#open' => FALSE,
    );
    $form['annotation_endpoints']['delete']['annotation_delete'] = array(
      '#type' => 'textfield',
      '#title' => t('Annotation delete endpoint'),
      '#default_value' => $config->get('annotation_delete'),
      '#size' => 30,
      '#description' => t('The annotation delete endpoint. please use token {annotation_id} for referring annotation entity.'),
    );
    $form['annotation_endpoints']['delete']['annotation_delete_method'] = array(
      '#type' => 'select',
      '#title' => t('Annotation delete method'),
      '#options' => array('DELETE' => t('DELETE')),
      '#default_value' => $config->get('annotation_delete_method'),
      '#description' => t('The http method used for annotation deletion'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    global $base_url;
    $config = $this->configFactory->getEditable('mirador.settings');
    if (!empty($form_state->getValue('iiif_server'))) {
      $config->set('iiif_server', $form_state->getValue('iiif_server'));
    }
    if (!empty($form_state->getValue('endpoint'))) {
      $config->set('endpoint', $form_state->getValue('endpoint'));
    }
    if (!empty($form_state->getValue('annotation_entity'))) {
      $config->set('annotation_entity', $form_state->getValue('annotation_entity'));
    }
    if (!empty($form_state->getValue('annotation_bundle'))) {
      $config->set('annotation_bundle', $form_state->getValue('annotation_bundle'));
    }
    if ($form_state->getValue('endpoint') == "rest_endpoint") {
      if (!empty($form_state->getValue('annotation_text'))) {
        $config->set('annotation_text', $form_state->getValue('annotation_text'));
      }
      if (!empty($form_state->getValue('annotation_viewport'))) {
        $config->set('annotation_viewport', $form_state->getValue('annotation_viewport'));
      }
      if (!empty($form_state->getValue('annotation_image_entity'))) {
        $config->set('annotation_image_entity', $form_state->getValue('annotation_image_entity'));
      }
      if (!empty($form_state->getValue('annotation_resource'))) {
        $config->set('annotation_resource', $form_state->getValue('annotation_resource'));
      }
      if (!empty($form_state->getValue('annotation_data'))) {
        $config->set('annotation_data', $form_state->getValue('annotation_data'));
      }
      // Set default value for annotation endpoints, If none specified.
      $config->set('annotation_create', $base_url . '/entity/' . $form_state->getValue('annotation_entity'));
      $config->set('annotation_create_method', 'POST');
      $config->set('annotation_search', $base_url . '/annotation/search/{resource_entity_id}');
      $config->set('annotation_search_method', 'GET');
      $config->set('annotation_update', $base_url . '/' . $form_state->getValue('annotation_entity') . '/{annotation_id}');
      $config->set('annotation_update_method', 'PATCH');
      $config->set('annotation_delete', $base_url . '/' . $form_state->getValue('annotation_entity') . '/{annotation_id}');
      $config->set('annotation_delete_method', 'DELETE');
    }
    else {
      if (!empty($form_state->getValue('annotation_create'))) {
        $config->set('annotation_create', $form_state->getValue('annotation_create'));
      }
      if (!empty($form_state->getValue('annotation_create_method'))) {
        $config->set('annotation_create_method', $form_state->getValue('annotation_create_method'));
      }
      if (!empty($form_state->getValue('annotation_search'))) {
        $config->set('annotation_search', $form_state->getValue('annotation_search'));
      }
      if (!empty($form_state->getValue('annotation_search_method'))) {
        $config->set('annotation_search_method', $form_state->getValue('annotation_search_method'));
      }
      if (!empty($form_state->getValue('annotation_update'))) {
        $config->set('annotation_update', $form_state->getValue('annotation_update'));
      }
      if (!empty($form_state->getValue('annotation_update_method'))) {
        $config->set('annotation_update_method', $form_state->getValue('annotation_update_method'));
      }
      if (!empty($form_state->getValue('annotation_delete'))) {
        $config->set('annotation_delete', $form_state->getValue('annotation_delete'));
      }
      if (!empty($form_state->getValue('annotation_delete_method'))) {
        $config->set('annotation_delete_method', $form_state->getValue('annotation_delete_method'));
      }
    }
    $config->save();
    parent::submitForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

}
