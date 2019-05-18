<?php

/**
 * @file
 * Contains \Drupal\robotagger\Form\RoboTaggerAdminForm.
 */

namespace Drupal\robotagger\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\field\Field;

/**
 * Configure file system settings for this site.
 */
class RoboTaggerAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'robotagger_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config_robotaggerapi = $this->configFactory->get('robotagger_api.server');
    $apikey = $config_robotaggerapi->get('api_key');
    if (empty($apikey)) {
      drupal_set_message(t('Before you can continue, set the api key in the !link settings.', array('!link' => l('RoboTagger-Api', 'admin/config/robotagger/robotagger_api'))), 'warning');
      return $form;
    }
    $sendrequest_description = t('Automatic - After submitting the content, the module requests the RoboTagger web service. <br />Manual - You must send your content manually to the web service (on a seperated page).');
    $confirmtags_description = t('Automatic - The module saves the suggestions as taxonomy term immediately.<br />Manual - Before the suggestions are saved, you can confirm them (on a seperated page).');
    $form['#attached']['js'] = array(drupal_get_path('module', 'robotagger') . '/js/robotagger.js');
    $form['robotagger_global_wrapper'] = array(
      '#type' => 'fieldset',
      '#title' => t('Global settings'),
      '#description' => t('Please note. You need to create all field instances for each checked vocabulary in each checked content types manually. You may as well enable the checkbox %checkboxlabel in this form.', array('%checkboxlabel' => t('Create/delete field instances automatically?'))),
      '#collapsed' => TRUE,
      '#collapsible' => TRUE,
    );
    // $form['robotagger_global_wrapper']['global_sendrequest'] = array(
      // '#type' => 'select',
      // '#title' => t('Send request'),
      // '#options' => array('disabled' => t('Disabled'), 'auto' => t('Automatic'), 'manual' => t('Manual')),
      // '#default_value' => _robotagger_get_config('global_sendrequest'),
      // '#description' => $sendrequest_description,
    // );
    // $form['robotagger_global_wrapper']['global_confirmtags'] = array(
      // '#type' => 'select',
      // '#title' => t('Confirm returned suggestions'),
      // '#options' => array('auto' => t('Automatic'), 'manual' => t('Manual')),
      // '#default_value' => _robotagger_get_config('global_confirmtags'),
      // '#description' => $confirmtags_description,
    // );
    $form['robotagger_global_wrapper']['global_caching'] = array(
      '#type' => 'select',
      '#title' => t('Caching returned data'),
      '#options' => array(TRUE => t('yes'), FALSE => t('no')),
      '#default_value' => _robotagger_get_config('global_caching'),
      '#description' => t('When enabled, the RoboTagger-API caches the returned data by the RoboTagger web service.'),
    );
    $form['robotagger_global_wrapper']['global_logging'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable logging'),
      '#default_value' => _robotagger_get_config('global_logging'),
      '#description' => t('When checked, the RoboTagger module logs all requests to the RoboTagger web service.'),
    );
    $nodetypes = node_type_get_names();
    $form['robotagger_global_wrapper']['global_nodetypes'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Supported content types'),
      '#options' => $nodetypes,
      '#default_value' => _robotagger_get_config('global_nodetypes'),
      '#description' => t('Choose the content types for the RoboTagger web service.'),
    );
    $form['robotagger_global_wrapper']['global_use_title'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use the title of the content'),
      '#default_value' => _robotagger_get_config('global_use_title'),
      '#description' => t('When enabled, the title will also be used for the RoboTagger web service. But it will be only used if at least one another text field is checked.'),
    );

    $options = $options_by_ct = $labels = array();
    foreach ($nodetypes as $mname => $uiname) {
      $instances = Field::fieldInfo()->getBundleInstances('node', $mname);
      $allowed_uuids = array();
      foreach (Field::fieldInfo()->getFields() as $uuid => $field) {
        if ($field->entity_type == 'node' && $field->module == 'text' && !$field->deleted && $field->name != 'robotagger_topic') {
          $allowed_uuids[$uuid] = $field;
        }
      }
      foreach ($instances as $name => $instance) {
        if (isset($allowed_uuids[$instance->field_uuid])) {
          $field = $allowed_uuids[$instance->field_uuid];
          if (!isset($labels[$field->name]) || !in_array($instance->label, $labels[$field->name])) {
            $labels[$field->name][] = $instance->label;
          }
          $label = implode('/ ', $labels[$field->name]);
          $options[$field->name] = $label . ' (' . $field->name . ')';
          $options_by_ct[$mname][$field->name] = $label . ' (' . $field->name . ')';
        }
      }
    }
    $form['robotagger_global_wrapper']['global_supported_fields'] = array(
      '#type' => empty($options) ? 'item' : 'checkboxes',
      '#title' => t('Fields'),
      '#options' => $options,
      '#default_value' => _robotagger_get_config('global_supported_fields'),
//      '#markup' => t('No supported fields from the text module are available.'),
      '#description' => t('Which fields should be used for the RoboTagger web service?'),
      '#states' => array(
        'visible' => array("input[name^='global_nodetypes']" => array(
          'checked' => TRUE,
        )),
      ),
    );
    $form['robotagger_global_wrapper']['fields_instances'] = array(
      '#type' => 'checkbox',
      '#title' => t('Create/Delete field instances automatically?'),
      '#description' => t('After submitting the form, the module creates the field instances for the checked vocabularies in the checked content types. And deletes all field instances (see listed vocabularies below) from the unchecked content types.'),
    );
    $options = _robotagger_get_vocabularies('db');
    $values = _robotagger_get_config('global_vocs');
    $form['robotagger_global_wrapper']['global_vocs'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Vocabularies'),
      '#options' => $options,
      '#default_value' => $values,
      '#description' => t('Which vocabularies should be used for the RoboTagger web service?')
    );

    $text = array_filter($values) ? t('Deselect all') : t('Select all');
    $form['robotagger_global_wrapper']['js'] = array(
      '#type' => 'item',
      '#markup' => '<a style="cursor: pointer;" class="robotagger-backend-js-select-all">' . $text . '</a>',
    );


    foreach ($nodetypes as $mname => $uiname) {
      $form['robotagger_nodetype_wrapper'][$mname] = array(
        '#type' => 'details',
        '#title' => $uiname,
        /**
         * TODO Replace #collapsed with #open, when changes in core be commited: https://drupal.org/node/1892182
         * '#open' => FALSE,
         */
        '#collapsed' => TRUE,
        '#states' => array(
          'visible' => array("input[name='global_nodetypes[$mname]']" => array(
            'checked' => TRUE,
           )),
        ),
      );
      $form['robotagger_nodetype_wrapper'][$mname][$mname . '_use_global'] = array(
        '#type' => 'checkbox',
        '#title' => t('Use default settings'),
        '#description' => t('When checked all made settings below will be ignored and the global settings will be used instead.'),
        '#default_value' => _robotagger_get_config($mname . '_use_global', 1),
      );
      // $form['robotagger_nodetype_wrapper'][$mname][$mname . '_sendrequest'] = array(
        // '#type' => 'select',
        // '#title' => t('Send request'),
        // '#options' => array('auto' => t('Automatic'), 'manual' => t('Manual')),
        // '#default_value' => _robotagger_get_config($mname . '_sendrequest', 'auto'),
        // '#description' => $sendrequest_description,
      // );
      // $form['robotagger_nodetype_wrapper'][$mname][$mname . '_confirmtags'] = array(
        // '#type' => 'select',
        // '#title' => t('Confirm returned suggestions'),
        // '#options' => array('auto' => t('Automatic'), 'manual' => t('Manual')),
        // '#default_value' => _robotagger_get_config($mname . '_confirmtags', 'auto'),
        // '#description' => $confirmtags_description,
      // );
      $form['robotagger_nodetype_wrapper'][$mname][$mname . '_caching'] = array(
        '#type' => 'select',
        '#title' => t('Caching returned data'),
        '#options' => array(TRUE => t('yes'), FALSE => t('no')),
        '#default_value' => _robotagger_get_config($mname . '_caching', TRUE),
      );
      $form['robotagger_nodetype_wrapper'][$mname][$mname . '_use_title'] = array(
        '#type' => 'checkbox',
        '#title' => t('Use the title of the content'),
        '#default_value' => _robotagger_get_config($mname . '_use_title', 1),
        '#description' => t('When enabled, the title will be also used for the RoboTagger web service.'),
      );
      $form['robotagger_nodetype_wrapper'][$mname][$mname . '_supported_fields'] = array(
        '#type' => empty($options_by_ct[$mname]) ? 'item' : 'checkboxes',
        '#title' => t('Field instances'),
        '#options' => empty($options_by_ct[$mname]) ? NULL : $options_by_ct[$mname],
        '#markup' => t('No supported field instances from the text module are available.'),
        '#description' => t('Which field instances should be used for the RoboTagger web service?'),
        '#default_value' => _robotagger_get_config($mname . '_supported_fields', array('body')),
      );
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
   public function validateForm(array &$form, array &$form_state) {
     parent::validateForm($form, $form_state);
   }

  /**
   * {@inheritdoc}
   */
    public function submitForm(array &$form, array &$form_state) {
      $config = $this->configFactory->get('robotagger.settings');
      $config->set('global_sendrequest', 'auto')
        ->set('global_confirmtags', 'auto')
        // ->set('global_sendrequest', $form_state['values']['global_sendrequest'])
        // ->set('global_confirmtags', $form_state['values']['global_confirmtags'])
        ->set('global_caching', $form_state['values']['global_caching'])
        ->set('global_logging', $form_state['values']['global_logging'])
        ->set('global_nodetypes', $form_state['values']['global_nodetypes'])
        ->set('global_use_title', $form_state['values']['global_use_title'])
        ->set('global_supported_fields', $form_state['values']['global_supported_fields'])
        ->set('global_vocs', $form_state['values']['global_vocs']);
      $nodetypes = node_type_get_names();
      foreach ($nodetypes as $key => $nodetype) {
        $config->set($key . '_use_global', $form_state['values'][$key . '_use_global']);
        if (!$form_state['values'][$key . '_use_global']) {
          $config->set($key . '_sendrequest', 'auto')
            ->set($key . '_confirmtags', 'auto')
            // ->set($key . '_sendrequest', $form_state['values'][$key . '_sendrequest'])
            // ->set($key . '_confirmtags', $form_state['values'][$key . '_confirmtags'])
            ->set($key . '_caching', $form_state['values'][$key . '_caching'])
            ->set($key . '_use_title', $form_state['values'][$key . '_use_title'])
            ->set($key . '_supported_fields', $form_state['values'][$key . '_supported_fields']);
            // ->set($key . '_vocs', $form_state['values'][$key . '_vocs']);
        }
      }
      $config->save();

      $nodetypes = $form_state['values']['global_nodetypes'];
      if ($form_state['values']['fields_instances']) {
        $allowed_vocs = $form_state['values']['global_vocs'];
        // $vocabularies = taxonomy_get_vocabularies();
        foreach ($nodetypes as $nodetype => $allowed) {
          //When checked than created else remove all field instanced.
          if ($nodetype === $allowed) {
            _robotagger_create_instances($nodetype, $allowed_vocs);
          }
          else{
            _robotagger_remove_instances($nodetype);
          }
        }
      }
      parent::submitForm($form, $form_state);
   }
}