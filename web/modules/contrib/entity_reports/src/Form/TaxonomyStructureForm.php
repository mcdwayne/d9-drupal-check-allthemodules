<?php

namespace Drupal\entity_reports\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Builds the taxonomy structure form.
 */
class TaxonomyStructureForm extends FormBase {

  protected $entityTypeManager;
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_structure_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['info'] = [
      '#markup' => t(
        'Open each content type below to see details about its field. You can also download as @json.',
        [ '@json' => Link::createFromRoute('JSON', 'entity_reports.taxonomy_structure_json')->toString() ]
      ),
    ];
    $entity_reports_generator = \Drupal::service('entity_reports.generator');
    $structure = $entity_reports_generator->generateTaxonomyReport();
    foreach ($structure as $machine_name => $data) {
      $form[$machine_name . '_table'] = [
        '#type' => 'details',
        '#title' => $data['label'],
      ];
      // Fields table
      $fields = [];
      foreach($data['fields'] as $field) {
        $fields[] = [
          'label' => $field['label'],
          'machine_name' => $field['machine_name'],
          'description' => $field['description'],
          'type' => $field['type'],
          'required' => $field['required_human'],
          'translatable' => $field['translatable_human'],
          'target' => $field['target'],
          'cardinality' => $field['cardinality_human'],
        ];
      }
      $form[$machine_name . '_table']['fields'] = [
        '#type' => 'table',
        '#caption' => t('List of fields'),
        '#rows' => $fields,
        '#header' => [
          $this->t('Field name'),
          $this->t('Machine name'),
          $this->t('Description'),
          $this->t('Data type'),
          $this->t('Required'),
          $this->t('Translatable'),
          $this->t('Target'),
          $this->t('Cardinality'),
        ],
        '#empty' => $this->t('No fields found'),
        '#attributes' => [
          'class' => ['table table-responsive struct-report-table'],
        ],
      ];

      // Terms table
      $terms = [];
      foreach ($data['terms'] as $tid => $term) {
        $terms[] = [
          'name' => Link::createFromRoute($term['name'], 'entity.taxonomy_term.canonical', ['taxonomy_term' => $tid]),
          'description' => $term['description'],
        ];
      }
      $form[$machine_name . '_table']['terms'] = [
        '#type' => 'table',
        '#caption' => t('List of terms in this vocabulary'),
        '#rows' => $terms,
        '#header' => [
          $this->t('Term'),
          $this->t('Description'),
        ],
        '#empty' => $this->t('No terms found'),
        '#attributes' => [
          'class' => ['table table-responsive struct-report-table'],
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
