<?php

namespace Drupal\ena\Controller;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ena\EntityNoteAuditData;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller to generate form.
 *
 * Class EntityNodeAuditController.
 */
class EntityNoteAuditController extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ena_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $current_path = \Drupal::service('path.current')->getPath();
    $path = explode("/", $current_path);
    $nid = $path[2];

    $values = \Drupal::entityQuery('node')->condition('nid', $nid)->execute();
    $node_exists = !empty($values);

    if (!$node_exists) {
      drupal_set_message(t('No service request exists'), 'error');
      return new RedirectResponse(\Drupal::url('<front>'));
    }

    $existing_notes = EntityNoteAuditData::accessData($nid);
    if (!empty($existing_notes)) {
      $form['existing_notes'] = [
        '#markup' => $existing_notes,
      ];
    }
    else {
      $form['existing_notes'] = [
        '#markup' => t('No private notes exist for this entity'),
      ];
    }
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Private notes'),
      '#required' => TRUE,
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
    $current_path = \Drupal::service('path.current')->getPath();
    $path = explode("/", $current_path);
    $nid = $path[2];

    $uid = \Drupal::currentUser()->id();
    $data = [
      'uid' => $uid,
      'message' => $form_state->getValue('message'),
      'etid' => $etid,
      'created' => time(),
    ];

    EntityNoteAuditData::insertData($data);
    drupal_set_message(t('Note has been added to the log'), 'status');
  }

}
