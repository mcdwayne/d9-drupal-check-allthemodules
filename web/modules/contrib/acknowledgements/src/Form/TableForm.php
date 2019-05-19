<?php

namespace Drupal\sign_for_acknowledgement\Form;

use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;



/**
 * Form builder for the sign_for_acknowledgement tableselect form.
 */
class TableForm extends FormBase {

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   *
   */
  public function __construct() {
    $this->config = \Drupal::config('sign_for_acknowledgement.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sign_for_acknowledgement_table_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $node = NULL) {
    $session_name = FilterForm::sessionName($node);
    $session = isset($_SESSION[$session_name]) ? $_SESSION[$session_name] : array();
    $dbman = \Drupal::service('sign_for_acknowledgement.db_manager');
    $fieldman = \Drupal::service('sign_for_acknowledgement.field_manager');
    $timestamp = $fieldman->expirationDate(TRUE, $node->id(), $node);
    if ($timestamp) {
      $formatted = \Drupal::service('date.formatter')->format(
          $timestamp, 'medium');
    }
    else {
      $formatted = '---';
    }
    $header_cells = array();
    $rows = array();
    $dbman->outdata($node, $timestamp, $session_name, $header_cells, $rows, FALSE);

    $form = array();
    $form['date'] = array(
      '#type' => 'markup',
      '#markup' => '<em>' . t('To be signed within: ') . $formatted . '</em>',
    );
    $form['myselector'] = array (
      '#type' => 'tableselect',
      '#header' => $header_cells,
      '#options' => $rows,
      '#attributes' => array(),
      '#empty' => t('No content available.'),
    );
    $form['node'] = array(
      '#type' => 'value',
      '#name' => 'nodeid',
      '#value' => $node->id(),
    );
    $timestamp = $fieldman->expirationDate(TRUE, $node->id(), $node);
    $is_expired = ($timestamp && $this->config->get('block_expired') && time() > $timestamp);
    if (!$is_expired && \Drupal::currentUser()->hasPermission('delete acknowledgements')) {
      $form['unsign_submit'] = array(
        '#type' => 'submit',
        '#value' => t('remove acknowledgement'),
        '#prefix' => '<div style="float:left">' . t('...if selected ') . '&nbsp;</div>',
      );
      if (!($node->alternate_form->value) && !($node->alternate_form_multiselect->value)) {
        $form['sign_submit'] = array(
          '#type' => 'submit',
          '#value' => t('add acknowledgement'),
          '#submit' => array('Drupal\sign_for_acknowledgement\Form\TableForm::sign_submitForm'),
        );
      }
    }
    $form['pager'] = ['#type' => 'pager'];

    return $form;
  }
  
public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  $dbman = \Drupal::service('sign_for_acknowledgement.db_manager');
  $items = $form_state->getValue('myselector');
  $nodeid = $form_state->getValue('node');
  foreach ($items as $key => $value) {
    if (!$value) {
      continue;
    }
    $dbman->unsignDocument($key, $nodeid);
  }
  $dbman->clearRenderCache();
 }
 
public static function sign_submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  $dbman = \Drupal::service('sign_for_acknowledgement.db_manager');
  $items = $form_state->getValue('myselector');
  $nodeid = $form_state->getValue('node');
  foreach ($items as $key => $value) {
    if (!$value) {
      continue;
    }
    $dbman->signDocument($key, $nodeid);
  }
  $dbman->clearRenderCache();
 }
}
