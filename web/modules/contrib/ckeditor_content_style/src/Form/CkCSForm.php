<?php

namespace Drupal\ckeditor_content_style\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

/**
 * Class CkCSForm.
 *
 * @package Drupal\ckeditor_content_style\Form
 */
class CkCSForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ckcs_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $conn = Database::getConnection();
    $record = [];
    if (isset($_GET['num'])) {
      $query = $conn->select('contentstyle', 'cs')
        ->condition('id', $_GET['num'])
        ->fields('cs');
      $record = $query->execute()->fetchAssoc();
    }
    if (isset($record['sugested'])) {
      $sugested = unserialize($record['sugested']);
      $sugested = implode("", $sugested);
    }
    $form['entity'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity:'),
      '#required' => TRUE,
      '#default_value' => (isset($record['entity']) && $_GET['num']) ? $record['entity'] : '',
    ];
    $form['sugested'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Suspected Words:'),
      '#description' => $this->t('Enter one word per line'),
      '#default_value' => (isset($record['sugested']) && $_GET['num']) ? $sugested : '',
    ];
    $form['suggestion'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Sugestion:'),
      '#required' => TRUE,
      '#default_value' => (isset($record['suggestion']) && $_GET['num']) ? $record['suggestion'] : '',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $field = $form_state->getValues();
    $entity = $field['entity'];
    $sugested = $field['sugested'];
    $sugested = explode("\n", $sugested);
    $sugested = array_filter($sugested, 'trim');
    $sugested = serialize($sugested);
    $suggestion = $field['suggestion'];

    if (isset($_GET['num'])) {
      $field = [
        'entity' => $entity,
        'sugested' => $sugested,
        'suggestion' => $suggestion,
      ];
      $query = Database::getConnection();
      $query->update('contentstyle')
        ->fields($field)
        ->condition('id', $_GET['num'])
        ->execute();
      drupal_set_message($this->t("succesfully updated"));
      $form_state->setRedirect('ckcs.ckcs_display');
    }
    else {
      $field = [
        'entity' => $entity,
        'sugested' => $sugested,
        'suggestion' => $suggestion,
      ];
      $query = Database::getConnection();
      $query->insert('contentstyle')
        ->fields($field)
        ->execute();
      drupal_set_message($this->t("succesfully saved"));
      $form_state->setRedirect('ckcs.ckcs_display');
    }
  }

}
