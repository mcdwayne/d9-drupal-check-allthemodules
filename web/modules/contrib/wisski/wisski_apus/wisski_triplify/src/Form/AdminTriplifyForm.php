<?php
/**
 * @file
 * Contains \Drupal\wisski_triplify\Form\AdminTriplifyForm.
 */

namespace Drupal\wisski_triplify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use Drupal\wisski_core\Entity\WisskiBundle;
use Drupal\wisski_salz\Entity\Adapter;
use Drupal\wisski_salz\Plugin\wisski_salz\Engine\Sparql11Engine;

use Symfony\Component\Yaml\Yaml;

class AdminTriplifyForm extends ConfigFormBase {

  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wisski_triplify_admin_settings_form';
  }


  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array(
      'wisski_triplify.triplify_fields',
    );
  }
  

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $fields_by_type = \Drupal::config('wisski_triplify.triplify_fields')->get('by_type');
    $fields_by_id = \Drupal::config('wisski_triplify.triplify_fields')->get('by_id');
    
    $form = array(
      '#tree' => TRUE,  // TODO: is this still needed in D8?
    );
    $form['tabs'] = array(
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-by-type-wrapper',
    );

    
    $form['by_id_wrapper'] = array(
      '#type' => 'details',
      '#title' => $this->t('Triplify by field'),
      '#group' => 'tabs',
    );
    
    $pipes = \Drupal::service('wisski_pipe.pipe')->loadByTags('triplify');
    $pipe_options = array('<none>' => $this->t('- Disabled -'));
    foreach ($pipes as $pid => $pipe) {
      $pipe_options[$pid] = $pipe->label();
    }
    
    // we will only allow adapters that are writable and SparQL-based
    $adapters = Adapter::loadMultiple();
    $adapter_options = array('<auto>' => $this->t('- Automatically -'));
    foreach ($adapters as $aid => $adapter) {
      if ($adapter->isWritable()) {
        $engine = $adapter->getEngine();
        if ($engine instanceof Sparql11Engine) {
          $adapter_options[$aid] = $adapter->label();
        }
      }
    }


    $bundles = WisskiBundle::loadMultiple();
    foreach ($bundles as $bundle) {
      ;
    }
    
    
    $form['by_type_wrapper'] = array(
      '#type' => 'details',
      '#title' => $this->t('Triplify by field type'),
      '#group' => 'tabs',
    );
    $form['by_type_wrapper']['by_type'] = array(
      '#type' => 'table',
      '#caption' => $this->t('Triplify by field type'),
      '#header' => array(
        'field_type' => $this->t('Field type'),
        'pipe' => $this->t('Pipe'),
        'adapter' => $this->t('Adapter'),
        'text' => $this->t('Text property'),
        'constraints' => $this->t('Constraints'),
      ),
    );

    $field_types = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
    $field_type_options = array();
    foreach ($field_types as $ftid => $field_type) {
      if (!isset($fields_by_type[$ftid])) {
        $fields_by_type[$ftid] = array(); // for convenience so that we don't have to check that
      }
      $form['by_type_wrapper']['by_type']["$ftid"] = array(
        'field_type' => array(
          '#type' => 'textfield',
          '#value' => $field_type['label'],
          '#disabled' => TRUE,
          '#size' => 20,
        ),
        'field_type' => array(
          '#plain_text' => $field_type['label'],
        ),
        'pipe' => array(
          '#type' => 'select',
          '#default_value' => isset($fields_by_type[$ftid]['pipe']) ? $fields_by_type[$ftid]['pipe'] : '<none>',
          '#options' => $pipe_options,
        ),
        // adapters should allow multiple but we restrict it to one atm
        'adapters' => array(
          '#type' => 'select',
          '#default_value' => isset($fields_by_type[$ftid]['adapters']) ? $fields_by_type[$ftid]['adapters'][0] : '<auto>',
          '#options' => $adapter_options,
        ),
        'text' => array(
          // TODO: make this a select field. atm gathering the possible properties is too complex
          '#type' => 'textfield',
          '#default_value' => isset($fields_by_type[$ftid]['text']) ? $fields_by_type[$ftid]['text'] : 'value',
          '#size' => 20,
        ),
       'constraints' => array(
          '#type' => 'textarea',
          '#default_value' => isset($fields_by_type[$ftid]['constraints']) ? $this->flattenConstraints($fields_by_type[$ftid]['constraints']) : '',
        ),
      );
    }
    
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $by_type = $form_state->getValue('by_type_wrapper')['by_type'];
    foreach ($by_type as $ftid => $settings) {
      if ($settings['pipe'] == '<none>') {
        unset($by_type[$ftid]);
      }
      else {
        $by_type[$ftid]['field_type'] = $ftid;
        $by_type[$ftid]['constraints'] = $this->parseConstraints($settings['constraints']);
        $by_type[$ftid]['adapters'] = $settings['adapters'] == '<auto>' ? NULL : array($settings['adapters']);
      }
    }
    
    $triplify_fields_config = \Drupal::service('config.factory')->getEditable('wisski_triplify.triplify_fields');
    $triplify_fields_config->set('by_type', $by_type);
    $triplify_fields_config->save();

  }

  
  protected function flattenConstraints($constraints) {
    return Yaml::dump($constraints);
  }


  protected function parseConstraints($constraints) {
    return Yaml::parse($constraints);
  }


}
