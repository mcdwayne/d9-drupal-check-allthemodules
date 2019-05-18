<?php

namespace Drupal\monster_menus\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\MMImportExportException;

class ExportTreeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mm_export_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    if (mm_module_exists('node_export')) {
      $form['include_nodes'] = array(
        '#type' => 'checkbox',
        '#title' => t('Include page contents (nodes)'),
        '#default_value' => isset($form_state['values']['include_nodes']) ? $form_state['values']['include_nodes'] : FALSE,
      );
    }
    else {
      $form['include_nodes'] = array(
        '#markup' => t('<p>To export page contents (nodes), the <a href=":link">node_export</a> module is required. Only pages will be exported.</p>', array(':link' => Url::fromUri('https://drupal.org/project/node_export')->toString())),
      );
    }

    $form['mmtid'] = array(
      '#type' => 'mm_catlist',
      '#mm_list_min' => 1,
      '#mm_list_max' => 1,
      '#mm_list_selectable' => Constants::MM_PERMS_READ,
      '#title' => t('Start at:'),
      '#required' => TRUE,
      '#description' => t('Export the tree starting at this location.'),
      '#default_value' => $form_state->getValue('mmtid'),
    );

    $form['actions'] = array(
      '#type' => 'actions',
      '#weight' => 1,
      'submit' => array(
        '#type' => 'submit',
        '#value' => t('Export'),
        '#button_type' => 'primary',
      )
    );

    if (!empty($form_state->getStorage()['mm_export_result'])) {
      $lines = min(substr_count($form_state->getStorage()['mm_export_result'], "\n"), 100);
      $form['code'] = array(
        '#type' => 'textarea',
        '#weight' => 2,
        '#title' => t('Export'),
        '#default_value' => $form_state->getStorage()['mm_export_result'],
        '#rows' => $lines,
      );
    }

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!mm_content_user_can($form_state->getValue('mmtid'), Constants::MM_PERMS_READ)) {
      $form_state->setErrorByName('mmtid', t('You do not have permission to read the starting location.'));
    }
    else {
      module_load_include('inc', 'monster_menus', 'mm_import_export');
      $mmtid = $form_state->getValue('mmtid');
      reset($mmtid);
      try {
        $form_state->setStorage(['mm_export_result' => mm_export(key($mmtid), !empty($form_state->getValue('include_nodes')))]);
      }
      catch (MMImportExportException $e) {
        \Drupal::messenger()->addError(t('An error occurred: @error', array('@error' => $e->getTheMessage())));
      }
      $form_state->setRebuild(TRUE);
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
