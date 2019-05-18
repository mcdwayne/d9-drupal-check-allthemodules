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
class CleverreachEditBlockForm extends FormBase {
  
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
    return 'cleverreach_edit_block';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $bid = NULL) {
    $bid = (is_numeric($bid) && isset($bid)) ? $bid : 0;
    $form_state->setStorage(array('bid' => $bid));
    $data = $this->database->query('SELECT * FROM {cleverreach_block_forms} bf WHERE bf.bid = :bid', array(':bid' => $bid))->fetchAssoc();
    $form = array();
    $form['#tree'] = TRUE;
    $options = array();
    $results = $this->database->query('SELECT * FROM {cleverreach_groups} g');
    $fields = unserialize($data["fields"]);
    $display_option_values = array();

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
      '#default_value' => $data["listid"],
      '#required' => TRUE,
      '#ajax' => array(
        'callback' => '::ajaxCallback',
        'wrapper' => 'cr-group-attribute-wrapper',
        'method' => 'replace',
        'effect' => 'fade',
        'event' => 'change',
      ),
    );

    $cr_block_grp = $form_state->getValue(['cr_grp_wrapper', 'cr_block_grp']);
    if (isset($cr_block_grp) || isset($data["listid"])) {
      $gid = (isset($cr_block_grp) && is_numeric($cr_block_grp)) ? $cr_block_grp : $data["listid"];

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
          $display_options = '';
          $selected = 0;
          $required = 0;

          foreach ($fields as $valued) {

            if ($value["key"] == $valued["name"]) {
              
              $label = $valued["label"];

              if ($valued["active"] == 1) {
                $selected = 1;
              }

              if ($valued["required"] == 1) {
                $required = 1;
              }

              if ($valued["display"] == 'select') {
                $display_options = 'select';
              }

              elseif ($valued["display"] == 'textfield') {
                $display_options = 'textfield';
              }

              elseif ($valued["display"] == 'date') {
                $display_options = 'date';
              }

              else {
                $display_options = 'textfield';
              }

            }

            $display_option_values[$valued["name"]] = $valued["display_options"];

          }

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
            '#default_value' => $selected,
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
            '#default_value' => $label,
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
            '#default_value' => $required,
          );
          $form['cr_grp_wrapper']['table'][$value["key"]]['display']['select'] = array(
            '#type' => 'select',
            '#options' => $display_select,
            '#default_value' => (isset($display_options) && !empty($display_options)) ? $display_options : key($display_select),
            '#ajax' => array(
              'callback' => '::ajaxCallback',
              'wrapper' => 'cr-group-attribute-wrapper',
              'method' => 'replace',
              'effect' => 'fade',
              'event' => 'change',
            ),
          );

          $display_style = $form_state->getValue(['cr_grp_wrapper', 'table', $value["key"], 'display', 'select']);
          $display = isset($display_style) ? $display_style : $display_options;

          if (empty($display)) {
            $display = key($display_select);
          }

          if ($display == 'select') {
            $form['cr_grp_wrapper']['table'][$value["key"]]['display']['display_options'] = array(
              '#type' => 'textarea',
              '#description' => t('Select options. One per line. key|value Example: men|Men'),
              '#default_value' => isset($display_option_values[$value["key"]]) ? $display_option_values[$value["key"]] : '',
            );
          }

          elseif ($display == 'textfield') {
            $form['cr_grp_wrapper']['table'][$value["key"]]['display']['display_options'] = array(
              '#type' => 'textfield',
              '#description' => t('A default field value (optional)'),
              '#default_value' => isset($display_option_values[$value["key"]]) ? $display_option_values[$value["key"]] : '',
            );
          }

          elseif ($display == 'date') {
            $form['cr_grp_wrapper']['table'][$value["key"]]['display']['display_options'] = array(
              '#type' => 'textfield',
              '#default_value' => isset($display_option_values[$value["key"]]) ? $display_option_values[$value["key"]] : 'Y-m-d',
              '#disabled' => TRUE,
              '#description' => t('Date format, default: Y-m-d'),
            );
          }

        }

      }

    }

    $form['cr_block_status'] = array(
      '#type' => 'radios',
      '#title' => t('Active Block'),
      '#default_value' => $data["active"],
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
    $bid = $form_state->getStorage('bid');
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

    $this->database->update('cleverreach_block_forms')
      ->fields(array(
        'listid' => $values["cr_grp_wrapper"]["cr_block_grp"],
        'fields' => serialize($fields),
        'active' => $values["cr_block_status"],
      ))
      ->condition("bid", $bid)
      ->execute();
    drupal_set_message(t('Block updated successfully.'));
    $response = new RedirectResponse(\Drupal::url('cleverreach.adminOverview'));
    $response->send();
    exit();
  }

}