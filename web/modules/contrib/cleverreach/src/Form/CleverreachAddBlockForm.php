<?php

namespace Drupal\cleverreach\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implements an example form.
 */
class CleverreachAddBlockForm extends FormBase {
  
  protected $database;
  
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cleverreach_add_block';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $options = array();
    $results = $this->database->query('SELECT * FROM {cleverreach_groups} g');

    foreach ($results as $result) {
      $options[$result->crgid] = $result->name;
    }

    $form['cr_grp_wrapper'] = array(
      '#type' => 'fieldset',
      '#attributes' => ['id' => 'cr-group-attribute-wrapper'],
    );
    $form['cr_grp_wrapper']['cr_block_grp'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => t('Select a group'),
      '#description' => t('Select the cleverreach group'),
      '#required' => TRUE,
      '#ajax' => array(
        'callback' => '::ajaxCallback',
        'wrapper' => 'cr-group-attribute-wrapper',
        'method' => 'replace',
        'effect' => 'fade',
        'event' => 'change',
      ),
    );
    
    $gid = is_numeric($form_state->getValue(['cr_grp_wrapper', 'cr_block_grp'])) ? $form_state->getValue(['cr_grp_wrapper', 'cr_block_grp']) : NULL;
    
    if ($gid != NULL) {
      $attributes = $this->database->query('SELECT grps.attributes FROM {cleverreach_groups} grps WHERE grps.crgid = :crgid', array(':crgid' => $gid))->fetchField();

      $headers = array(
        '',
        t('Name'),
        t('Label'),
        t('Type'),
        t('Variable'),
        t('Required'),
        t('Display'),
      );
      
      $form['cr_grp_wrapper']['table'] = array(
        '#type' => 'table',
        '#caption' => t('Select fields'),
        '#header' => $headers,
      );
      
      foreach (unserialize($attributes) as $value) {

        if ($value["type"] == "text") {
          $display_select = array(
            'textfield' => t('Textfield'),
            'select' => t('Select box'),
          );
        }

        elseif ($value["type"] == "number") {
          $display_select = array(
            'textfield' => t('Textfield'),
          );
        }

        elseif ($value["type"] == "gender") {
          $display_select = array(
            'select' => t('Select box'),
          );
        }

        elseif ($value["type"] == "date") {
          $display_select = array(
            'date' => t('Date select box'),
          );
        }

        $form['cr_grp_wrapper']['table'][$value["key"]]['select'] = array(
          '#type' => 'checkbox',
        );
        $form['cr_grp_wrapper']['table'][$value["key"]]['name']['show'] = array(
          '#type' => 'item',
          '#markup' => $value["key"],
        );
        $form['cr_grp_wrapper']['table'][$value["key"]]['name']['val'] = array(
          '#type' => 'value',
          '#value' => $value["key"],
        );
        $form['cr_grp_wrapper']['table'][$value["key"]]['label'] = array(
          '#type' => 'textfield',
          '#default_value' => $value["key"],
        );
        $form['cr_grp_wrapper']['table'][$value["key"]]['type']['show'] = array(
          '#type' => 'item',
          '#markup' => $value["type"],
        );
        $form['cr_grp_wrapper']['table'][$value["key"]]['type']['val'] = array(
          '#type' => 'value',
          '#value' => $value["type"],
        );
        $form['cr_grp_wrapper']['table'][$value["key"]]['var']['show'] = array(
          '#type' => 'item',
          '#markup' => $value["variable"],
        );
        $form['cr_grp_wrapper']['table'][$value["key"]]['var']['val'] = array(
          '#type' => 'value',
          '#value' => $value["variable"],
        );
        $form['cr_grp_wrapper']['table'][$value["key"]]['required'] = array(
          '#type' => 'checkbox',
        );
        $form['cr_grp_wrapper']['table'][$value["key"]]['display']['select'] = array(
          '#type' => 'select',
          '#options' => $display_select,
          '#default_value' => key($display_select),
          '#ajax' => array(
            'callback' => '::ajaxCallback',
            'wrapper' => 'cr-group-attribute-wrapper',
            'method' => 'replace',
            'effect' => 'fade',
            'event' => 'change',
          ),
        );

        $display = $form_state->getValue(['cr_grp_wrapper', 'table', $value["key"], 'display', 'select']);
        
        if (($form['cr_grp_wrapper']['table'][$value["key"]]['display']['select']['#default_value'] == 'select') || null !== $form_state->getValue('cr_grp_wrapper') && $display == 'select') {
          $form['cr_grp_wrapper']['table'][$value["key"]]['display']['display_options'] = array(
            '#type' => 'textarea',
            '#description' => t('Select options. One per line. key|value Example: men|Men'),
          );
        }

        elseif (($form['cr_grp_wrapper']['table'][$value["key"]]['display']['select']['#default_value'] == 'textfield') || null !== $form_state->getValue('cr_grp_wrapper') && $display == 'textfield') {
          $form['cr_grp_wrapper']['table'][$value["key"]]['display']['display_options'] = array(
            '#type' => 'textfield',
            '#description' => t('A default field value (optional)'),
          );
        }

        elseif (($form['cr_grp_wrapper']['table'][$value["key"]]['display']['select']['#default_value'] == 'date')|| null !== $form_state->getValue('cr_grp_wrapper') && $display == 'date') {
          $form['cr_grp_wrapper']['table'][$value["key"]]['display']['display_options'] = array(
            '#type' => 'textfield',
            '#default_value' => 'Y-m-d',
            '#disabled' => TRUE,
            '#description' => t('Date format, default: Y-m-d'),
          );
        } 
        
      }
      
    }

    $form['cr_block_status'] = array(
      '#type' => 'radios',
      '#title' => t('Active Block'),
      '#default_value' => 1,
      '#description' => t('Should this block be active and usable on the system block page?'),
      '#options' => array(0 => t('No'), 1 => t('Yes')),
      '#weight' => 2,
    );
    $form['cr_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#weight' => 3,
    );
    return $form;
  }
  
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['cr_grp_wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $fields = array();

    foreach ($values['cr_grp_wrapper']['table'] as $key => $value) {
      $fields[] = array(
        'name' => $key,
        'required' => $value["required"],
        'display' => $value["display"]["select"],
        'display_options' => $value["display"]["display_options"],
        'active' => $value["select"],
        'label' => $value["label"],
      );
    }

    $query = $this->database->insert('cleverreach_block_forms')->fields(array(
      'listid',
      'fields',
      'active',
    ));
    $query->values(array(
      $values["cr_grp_wrapper"]["cr_block_grp"],
      serialize($fields),
      $values["cr_block_status"],
    ));
    $query->execute();
    drupal_set_message(t('Block added successfully.'));
    $response = new RedirectResponse(\Drupal::url('cleverreach.adminOverview'));
    $response->send();
  }

}