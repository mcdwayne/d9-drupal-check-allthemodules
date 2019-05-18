<?php

namespace Drupal\entity_reports\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Builds the content types structure form.
 */
class NodeTypeStructureForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_types_structure_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_reports_generator = \Drupal::service('entity_reports.generator');
    $structure = $entity_reports_generator->generateContentTypesReport();
    foreach ($structure as $machine_name => $data) {
      $rows = [];
      foreach($data['fields'] as $field) {
        $rows[] = [
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
      $form['info'] = [
        '#markup' => t(
          'Open each content type below to see details about its field. You can also download as @json.',
          [ '@json' => Link::createFromRoute('JSON', 'entity_reports.content_types_structure_json')->toString() ]
        ),
      ];
      $form[$machine_name . '_wrapper'] = [
        '#title' => $data['label'],
        '#type' => 'details',
        '#open' => FALSE,
        $machine_name = [
          '#type' => 'table',
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
          '#attributes' => [
            'class' => ['table table-responsive struct-report-table'],
          ],
          '#rows' => $rows,
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
