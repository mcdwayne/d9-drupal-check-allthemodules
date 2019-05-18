<?php

namespace Drupal\rocketship\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use GuzzleHttp\Client;

/**
 * Form controller for Rocketship Feed edit forms.
 *
 * @ingroup rocketship
 */
class FeedForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate project, assigned and tag as single references.
    $this->validateSingleReferenceValue($form_state, 'filter_project');
    $this->validateSingleReferenceValue($form_state, 'filter_assigned');
    $this->validateSingleReferenceValue($form_state, 'filter_tag');

    // Validate node id field.
    $nid = $form_state->getValue('filter_nid')[0]['value'];
    if (!empty($nid)) {
      $valid_nid = FALSE;
      $client = new Client();
      $response = $client->get('https://www.drupal.org/api-d7/node.json?type=project_issue&full=0&nid=' . urlencode($nid));
      if ($response->getStatusCode() == 200 && ($body = (string) $response->getBody())) {
        $data = json_decode($body);
        if (!empty($data->list)) {
          $valid_nid = TRUE;
        }
      }
    }
    if (isset($valid_nid) && !$valid_nid) {
      $form_state->setErrorByName('filter_nid', $this->t('Drupal.org issue @num not found.', ['@num' => $nid]));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * Validate a single remote value with drupal.org.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $key
   *   Form element key.
   */
  protected function validateSingleReferenceValue(FormStateInterface $form_state, $key) {
    $client = new Client();

    switch($key) {
      case 'filter_project':
        $field_name = 'field_rocketship_drupal_org_nid';
        break;
      case 'filter_assigned':
        $field_name = 'field_rocketship_drupal_org_uid';
        break;
      case 'filter_tag':
        $field_name = 'field_rocketship_drupal_org_tid';
        break;
    }

    $name = '';
    $value = $form_state->getValue($key)[0];
    if (isset($value['target_id'])) {
      if (is_numeric($value['target_id'])) {
        $term = Term::load($value['target_id']);
        if (empty($term->$field_name->getValue())) {
          // Existing term but the id is not yet set.
          $name = $term->label();
        }
      }
      if (isset($value['target_id']['entity'])) {
        // New term to be created with this form.
        $term = $value['target_id']['entity'];
        $name = $term->label();
      }
    }
    // Found a value to look up.
    if (!empty($name)) {
      switch($key) {
        case 'filter_project':
          $remote_url = 'https://www.drupal.org/api-d7/node.json?full=0&field_project_machine_name=';
          $message = $this->t('Drupal.org project %name not found, did you specify the machine name?', ['%name' => $name]);
          break;
        case 'filter_assigned':
          $remote_url = 'https://www.drupal.org/api-d7/user.json?full=0&name=';
          $message = $this->t('Drupal.org user %name not found.', ['%name' => $name]);
          break;
        case 'filter_tag':
          $remote_url = 'https://www.drupal.org/api-d7/taxonomy_term.json?vocabulary=9&full=0&name=';
          $message = $this->t('Drupal.org issue tag %name not found.', ['%name' => $name]);
          break;
      }

      $response = $client->get($remote_url . urlencode($name));
      if ($response->getStatusCode() == 200 && ($body = (string) $response->getBody())) {
        $data = json_decode($body);
        if (!empty($data->list)) {
          $term->$field_name = $data->list[0]->id;
          if (!$term->isNew()) {
            // If the term already existed, save it. Otherwise the form will
            // implicitly create it on submission.
            $term->save();
          }
          unset($term);
        }
      }

      // If term is still set, we found a problem.
      if (isset($term)) {
        $form_state->setErrorByName($key, $message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);
    $entity = $this->entity;

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label feed.', [
          '%label' => $entity->toLink($entity->label(), 'edit-form')->toString(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label feed.', [
          '%label' => $entity->toLink($entity->label(), 'edit-form')->toString(),
        ]));
    }
    $form_state->setRedirect('entity.rocketship_feed.collection');
  }

}
