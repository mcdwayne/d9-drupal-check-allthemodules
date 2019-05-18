<?php

namespace Drupal\carerix_form\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SortArray;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Carerix form add and edit forms.
 */
class CarerixFormEditForm extends EntityForm {

  /**
   * Carerix form fields.
   *
   * @var mixed
   */
  protected $carerixFormFields;

  /**
   * Constructs a CarerixForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param mixed $carerixFormFields
   *   The carerix form fields.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, $carerixFormFields) {
    $this->entityTypeManager = $entityTypeManager;
    $this->carerixFormFields = $carerixFormFields;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('carerix.form_fields.open')
    );
  }

  /**
   * Check whether a Carerix form configuration entity exists.
   *
   * @param int $id
   *   Carerix form entity id.
   *
   * @return bool
   *   TRUE if found.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('carerix_form')->load($id);
    return (bool) $entity;
  }

  /**
   * Builds the main "Form fields" portion of the form.
   *
   * @return array
   *   An array of form fields.
   */
  protected function buildFormFieldsForm() {
    // Vars.
    $form = [];
    $tree = $this->carerixFormFields->getAll();
    $defaultSettings = $this->carerixFormFields->getDefaultSettings();
    /** @var \Drupal\carerix_form\Entity\CarerixForm $carerixForm */
    $carerixForm = $this->entity;
    $settings = $carerixForm->getSettings();

    // Form fields within each group.
    foreach ($tree as $id => $fieldGroup) {

      $form[$id] = [
        '#type' => 'table',
        '#header' => [
          [
            'data' => $fieldGroup['label'],
            'style' => ['width:25%'],
          ],
          [
            'data' => $this->t('Weight'),
            'style' => ['width:15%'],
          ],
          [
            'data' => $this->t('Mapping'),
            'style' => ['width:35%'],
          ],
          [
            'data' => $this->t('Enabled'),
            'style' => ['width:15%'],
          ],
          [
            'data' => $this->t('Mandatory'),
            'style' => ['width:15%'],
          ],
        ],
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'carerix-fields-order-weight-' . $id,
          ],
        ],
        '#empty' => $this->t('No form fields available.'),
      ];

      // Isolate array without objects for sorting with 'SortArray::class'.
      $rows = [];

      foreach ($fieldGroup['mappings'] as $fieldId => $field) {
        // TableDrag: Mark the table row as draggable.
        $rows[$fieldId]['#attributes']['class'][] = 'draggable';
        // TableDrag: Sort the table row according to its configured weight.
        $rows[$fieldId]['#weight'] = isset($settings[$id][$fieldId]['weight']) ? (int) $settings[$id][$fieldId]['weight'] : 0;
        // Some table columns containing raw markup.
        $rows[$fieldId]['label'] = ['#plain_text' => $field['label']];
        // TableDrag: Weight column element.
        $rows[$fieldId]['weight'] = [
          '#type' => 'weight',
          '#title' => t('Weight for @title', ['@title' => $id]),
          '#title_display' => 'invisible',
          '#default_value' => $rows[$fieldId]['#weight'],
          // Classify the weight element for #tabledrag.
          '#attributes' => ['class' => ['carerix-fields-order-weight-' . $id]],
        ];
        // Checkboxes.
        $rows[$fieldId]['mapping'] = [];
        $rows[$fieldId]['enabled'] = ['#type' => 'checkbox'];
        $rows[$fieldId]['mandatory'] = ['#type' => 'checkbox'];
        // Check for mapping field to data node type.
        if (isset($field['mappings']['data_node_type'])) {
          // Get data node type value.
          $dataNodeType = $field['mappings']['data_node_type'];
          // Check if data node type variable is set.
          if (!isset(${$dataNodeType})) {
            // Get stored data nodes for type.
            ${$dataNodeType} = \Drupal::database()->select('carerix_data_nodes', 'c')
              ->fields('c', ['data_node_id', 'data_node_value'])
              ->condition('c.data_node_type', $dataNodeType, '=')
              ->execute()
              ->fetchAllKeyed();
            // Urge user to sync data nodes.
            if (empty(${$dataNodeType})) {
              drupal_set_message(\Drupal::translation()->translate(
                'Please sync data nodes of type "@type" first.', ['@type' => $dataNodeType]
              ), 'warning');
            }
          }
          // Build mapping render array.
          $rows[$fieldId]['mapping']['#type'] = 'select';
          $rows[$fieldId]['mapping']['#options'] = ${$dataNodeType};
          $rows[$fieldId]['mapping']['#required'] = TRUE;
          $rows[$fieldId]['mapping']['#default_value'] = isset($settings[$id][$fieldId]['mapping']) ? $settings[$id][$fieldId]['mapping'] : NULL;
        }
        // Check for locked field defaults.
        if (isset($defaultSettings[$fieldId]) && in_array('mandatory', $defaultSettings[$fieldId])) {
          $rows[$fieldId]['enabled']['#default_value'] = TRUE;
          $rows[$fieldId]['enabled']['#disabled'] = TRUE;
          $rows[$fieldId]['mandatory']['#default_value'] = TRUE;
          $rows[$fieldId]['mandatory']['#disabled'] = TRUE;
        }
        else {
          $rows[$fieldId]['enabled']['#default_value'] = isset($settings[$id][$fieldId]['enabled']) ? $settings[$id][$fieldId]['enabled'] == 1 : FALSE;
          $rows[$fieldId]['mandatory']['#default_value'] = isset($settings[$id][$fieldId]['mandatory']) ? $settings[$id][$fieldId]['mandatory'] == 1 : FALSE;
        }
      }

      // Sort by weight & add rows to tree.
      uasort($rows, [SortArray::class, 'sortByWeightElement']);
      $form[$id] += $rows;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\carerix_form\Entity\CarerixForm $carerixForm */
    $carerixForm = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $carerixForm->label(),
      '#description' => $this->t("Label for the Carerix form."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $carerixForm->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$carerixForm->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#default_value' => $carerixForm->getDescription(),
    ];

    // Build the form tree of form fields.
    $form['form_fields'] = $this->buildFormFieldsForm();

    // Preserve tree structure.
    $form['#tree'] = TRUE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    // Vars.
    $values = $form_state->getValues();

    /** @var \Drupal\carerix_form\Entity\CarerixForm $carerixForm */
    $carerixForm = $this->entity;
    $carerixForm->setSettings($values['form_fields']);
    $status = $carerixForm->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label Carerix form.', [
        '%label' => $carerixForm->label(),
      ]));
    }
    else {
      drupal_set_message($this->t('The %label Carerix form was not saved.', [
        '%label' => $carerixForm->label(),
      ]));
    }

    $form_state->setRedirect('entity.carerix_form.collection');
  }

}
