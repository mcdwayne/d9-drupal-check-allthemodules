<?php

namespace Drupal\taxonomy_replace\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class TaxonomyReplaceForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_replace_term_replace_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $term = $this->getEntity();
    $old_tid = $term->id();
    $vid = $term->getVocabularyId();

    $form['old_term'] = [
      '#title' => $this->t('Current taxonomy term'),
      '#type' => 'textfield',
      '#value' => $term->label() . " ($old_tid)",
      '#disabled' => TRUE,
    ];

    $form['old_tid'] = [
      '#type' => 'value',
      '#value' => $old_tid,
    ];

    $nodes = $this->get_nids_by_tid($old_tid);
    $node_list = [];
    foreach ($nodes as $row) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($row->nid);
      $node_list[] = $node->toLink();
    }

    $node_list_output = [
      '#theme' => 'item_list',
      '#items' => $node_list,
      '#title' => $this->t('Nodes that will be updated'),
    ];

    $form['nodes'] = [
      '#markup' => render($node_list_output),
    ];


    $form['new_tid'] = [
      '#title' => $this->t('Taxonomy term to use instead'),
      '#type' => 'entity_autocomplete',
      '#required' => TRUE,
      '#target_type' => 'taxonomy_term',
      // Limit the selection to the same vocabulary.
      '#selection_settings' => [
        'target_bundles' => [
          $vid => $vid,
        ],
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $old_tid = $form_state->getValue('old_tid');
    $new_tid = $form_state->getValue('new_tid');

    $old_term = $this->getEntity();
    $new_term = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->load($new_tid);

    // Get all references to the current term.
    $nodes = $this->get_nids_by_tid($old_tid);
    foreach ($nodes as $row) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($row->nid);
      $field_name = $this->getTermReferenceField($node, $old_term);

      // Add a reference to the new term, unless there is one already.
      if (!taxonomy_replace_has_reference($node, $field_name, $new_tid)) {
        $node->{$field_name}->appendItem($new_term);
      }

      // Remove the old term reference.
      $node_term_list = $node->{$field_name};
      $terms_on_node = $node_term_list->referencedEntities();
      foreach ($terms_on_node as $key => $term) {
        if ($term->id() == $old_tid) {
          $node->{$field_name}->removeItem($key);
        }
      }

      $node->save();
    }

    $message = 'References to %old_term have been replaced by references to %new_term';
    $tokens = [
      '%old_term' => $this->entity->label(),
      '%new_term' => $new_term->label(),
    ];
    drupal_set_message($this->t($message, $tokens));

    $this->logger($this->getEntity()->getEntityType()->getProvider())
      ->notice($message, $tokens);

    // Delete the old term.
    parent::submitForm($form, $form_state);

    // Redirect to the new taxonomy term.
    $form_state->setRedirectUrl(new Url('entity.taxonomy_term.canonical', [
      'taxonomy_term' => $new_tid,
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to replace and delete the @entity-type %label?', [
      '@entity-type' => $this->getEntity()
        ->getEntityType()
        ->getLowercaseLabel(),
      '%label' => $this->getEntity()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.taxonomy_vocabulary.collection');
  }

  /**
   * Get all nodes with a term ID.
   *
   * @param int $term_id
   *   The term ID to search for.
   *
   * @return mixed
   *   Array of nid and tid
   */
  protected function get_nids_by_tid($term_id) {
    $query = \Drupal::database()->select('taxonomy_index', 'ti');
    $query->fields('ti', ['nid', 'tid']);
    $query->condition('ti.tid', $term_id);
    $result = $query->execute()->fetchAll();

    return $result;
  }

  /**
   * Get the name of the term reference field relevant to this term ID.
   *
   * @param Node $node
   *   The node holding the term reference field.
   * @param \Drupal\taxonomy\Entity\Term $term
   *   The term being referenced.
   *
   * @return string
   *   The field name.
   */
  protected function getTermReferenceField($node, $term) {
    $field_name = '';

    $defs = $node->getFieldDefinitions();

    foreach ($defs as $field) {
      if ($field->getType() == 'entity_reference') {
        $settings = $field->getSettings();
        if ($settings['target_type'] == 'taxonomy_term') {
          $field_name = $field->getName();
          $referenced_terms = $node->{$field_name}->referencedEntities();
          if (in_array($term, $referenced_terms)) {
            return $field_name;
          }
        }
      }
    }

    return $field_name;
  }
}
