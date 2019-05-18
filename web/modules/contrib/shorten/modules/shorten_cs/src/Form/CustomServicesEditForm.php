<?php

namespace Drupal\shorten_cs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form.
 */
class CustomServicesEditForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shorten_cs_edit';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'shorten.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $service = NULL) {
    $form = \Drupal::formBuilder()->getForm('Drupal\shorten_cs\Form\CustomServicesAddForm');
    $sid = $service;
    $service = db_select('shorten_cs', 's')
      ->fields('s')
      ->condition('sid', intval($sid))
      ->execute()
      ->fetchAssoc();

    foreach (array('name', 'url', 'type', 'tag') as $key) {
      $form[$key]['#default_value'] = $service[$key];
      unset($form[$key]['#value']);
    }

    $form['sid'] = array(
      '#type' => 'value',
      '#value' => $service['sid'],
    );
    $form['old_name'] = array(
      '#type' => 'value',
      '#value' => $service['name'],
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $v = $form_state->getValues();
    $config_factory = \Drupal::configFactory();
    $record = array();

    foreach (array('name', 'url', 'type', 'tag', 'sid') as $key) {
      $record[$key] = $v[$key];
    }

    \Drupal::database()->merge('shorten_cs')->fields($record)->key(['sid'])->execute();

    if ($v['old_name'] == \Drupal::config('shorten.settings')->get('shorten_service', 'is.gd')) {
      $config_factory->getEditable('shorten.settings')->set('shorten_service', $v['name']);
    }

    if ($v['old_name'] == \Drupal::config('shorten.settings')->get('shorten_service_backup', 'TinyURL')) {
      $config_factory->getEditable('shorten.settings')->set('shorten_service', $v['name']);
    }

    drupal_set_message(t('The changes to service %service have been saved.', array('%service' => $record['name'])));

    $form_state->setRedirect('shorten_cs.theme_shorten_cs_admin');
    return;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $v = $form_state->getValues();

    if (($v['type'] == 'xml' || $v['type'] == 'json') && empty($v['tag'])) {
      $form_state->setErrorByName('type', t('An XML tag or JSON key is required for services with a response type of XML or JSON.'));
    }

    $exists = db_query("SELECT COUNT(sid) FROM {shorten_cs} WHERE name = :name", array(':name' => $v['name']))->fetchField();

    if ($exists > 0) {
      $form_state->setErrorByName('name', t('A service with that name already exists.'));
    }
    else {
      $all_services = \Drupal::moduleHandler()->invokeAll('shorten_service');
      $all_services['none'] = t('None');
      foreach ($all_services as $key => $value) {
        if ($key == $v['name']) {
          $form_state->setErrorByName('name', t('A service with that name already exists.'));
          break;
        }
      }
    }
  }
}
