<?php
/**
 * Created by PhpStorm.
 * User: bappasarkar
 * Date: 4/21/17
 * Time: 2:23 PM
 */
/**
 * @file
 * Contains \Drupal\phpconfig\Form\PhpConfigForm.
 */

namespace Drupal\phpconfig\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
class PhpConfigForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'phpconfig_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $configid=NULL) {
    $edit_flag = FALSE;
    if (isset($configid)) {
      $config_object = phpconfig_get_config($configid);
      if (empty($config_object)) {
        drupal_set_message(t('The configuration id !id is not valid', array('!id' => $configid)));
        return;
      }
      else {
        $edit_flag = TRUE;
        $form['configid'] = array(
          '#type' => 'hidden',
          '#value' => $config_object->configid,
        );
      }
    }
    $form['phpconfig'] = array(
      '#type' => 'fieldset',
      '#title' => t('PHP Config'),
    );
    $form['phpconfig']['item'] = array(
      '#type' => 'textfield',
      '#title' => t('Item'),
      '#description' => t('Type PHP configuration item. E.g. memory_limit'),
      '#default_value' => ($edit_flag) ? $config_object->item : '',
      '#required' => TRUE,
    );
    $form['phpconfig']['value'] = array(
      '#type' => 'textfield',
      '#title' => t('Value'),
      '#description' => t('Type value of the above configuration item.'),
      '#default_value' => ($edit_flag) ? $config_object->value : '',
      '#required' => TRUE,
    );
    if ($edit_flag) {
      $form['phpconfig']['status'] = array(
        '#type' => 'checkbox',
        '#title' => t('Enabled'),
        '#default_value' => $config_object->status,
      );
    }
    $form['phpconfig']['actions']['#type'] = 'actions';
    $form['phpconfig']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#button_type' => 'primary',
    );
    if ($edit_flag) {
      $form['phpconfig']['actions']['delete'] = array(
        '#type' => 'submit',
        '#value' => t('Delete'),
      );
    }
    $form['#attached']['library'][] = 'phpconfig/phpconfig_test';
    $form['#attached']['drupalSettings']['phpconfig_test'] = array(
      'ajaxUrl' => '/admin/config/development/phpconfig/test',
      'msg' => t('Your current configuration may result into a WSOD!'),
      'phpconfig_tok' => \Drupal::csrfToken()->get(),
    );
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if ($values['op'] != t('Delete')) {
      $item = trim($values['item']);
      $available_config = phpconfig_get_config_by_item($item);
      // Check are we adding new item at the time of updating.
      if ($values['configid'] !== NULL && !empty($available_config) && $available_config->configid != $values['configid']) {
        $form_state->setErrorByName('item', t('The item !item is already in the DB.', array('!item' => $item)));
      }
      // Check if we have any existing item already.
      elseif ($values['configid'] === NULL && !empty($available_config)) {
        $form_state->setErrorByName('item', t('The item !item is already in the DB.', array('!item' => $item)));
      }
      $configs = ini_get_all();
      if (!isset($configs[$item])) {
        $form_state->setErrorByName('item', t('!item is not a valid item or not available in your current PHP version.', array('!item' => $item)));
      }
      elseif ($configs[$item]['access'] == 2 || $configs[$item]['access'] == 6) {
        $form_state->setErrorByName('item', t('The item !item can only be set in php.ini, .htaccess or httpd.conf file.', array('!item' => $item)));
      }
      elseif ($configs[$item]['access'] == 4) {
        $form_state->setErrorByName('item', t('The item !item can only be set in php.ini or httpd.conf file.', array('!item' => $item)));
      }
    }
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if ((string)$values['op'] == t('Delete')) {
      db_query("DELETE FROM {phpconfig_items} WHERE configid = :configid", array(':configid' => $form_state->getValue('configid')));
    }
    else {
      $account = \Drupal::currentUser();
      $item = trim($form_state->getValue('item'));
      $value = trim($form_state->getValue('value'));
      if ($values['configid'] !== NULL) {
        $status = $form_state->getValue('status');
        $configid = $form_state->getValue('configid');
        db_update('phpconfig_items')
          ->fields(array(
            'item' => $item,
            'value' => $value,
            'status' => $status,
          ))
          ->condition('configid', $configid, '=')
          ->execute();
      }
      elseif ((string)$values['op'] == t('Save')) {
        db_insert('phpconfig_items')
          ->fields(array('item', 'value', 'status'))
          ->values(array(
            'item' => $item,
            'value' => $value,
            'status' => 1,
          ))
          ->execute();
      }
    }
    $form_state->setRedirect('phpconfig.index');
  }
}
