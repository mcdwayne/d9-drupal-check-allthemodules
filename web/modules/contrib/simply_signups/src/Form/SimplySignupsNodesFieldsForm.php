<?php

namespace Drupal\simply_signups\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simply_signups\Utility\SimplySignupsUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Implements a signup form.
 */
class SimplySignupsNodesFieldsForm extends FormBase {

  protected $time;
  protected $database;
  protected $currentPath;
  protected $entityTypeManager;

  /**
   * Implements __construct().
   */
  public function __construct(TimeInterface $time_interface, CurrentPathStack $current_path, Connection $database_connection, EntityTypeManagerInterface $entity_type_manager) {
    $this->time = $time_interface;
    $this->currentPath = $current_path;
    $this->database = $database_connection;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Implements create().
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('datetime.time'),
      $container->get('path.current'),
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simply_signups_nodes_fields_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $path = $this->currentPath->getPath();
    $currentPath = ltrim($path, '/');
    $arg = explode('/', $currentPath);
    $nid = $arg[1];
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load($nid);
    $isValidNode = (isset($node)) ? TRUE : FALSE;
    if (!$isValidNode) {
      throw new NotFoundHttpException();
    }
    $id = $node->id();
    $numberOfSignups = SimplySignupsUtility::getNumberOfAttending($id);
    if ($numberOfSignups > 0) {
      drupal_set_message($this->t('This form cannot be edited while there are signups for this event.'), 'warning');
    }
    $form['#attached']['library'][] = 'simply_signups/styles';
    $form['#attributes'] = [
      'class' => ['simply-signups-templates-fields-form', 'simply-signups-form'],
    ];
    if ($numberOfSignups > 0) {
      $form['table-row'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Title'),
          $this->t('Type'),
          $this->t('Required'),
        ],
        '#empty' => $this->t('No fields found.'),
      ];
    }
    else {
      $form['table-row'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Title'),
          $this->t('Type'),
          $this->t('Required'),
          $this->t('Weight'),
          $this->t('Operations'),
        ],
        '#empty' => $this->t('No fields found.'),
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'table-sort-weight',
          ],
        ],
      ];
    }
    $db = $this->database;
    $query = $db->select('simply_signups_fields', 'p');
    $query->fields('p');
    $query->orderBy('weight');
    $query->condition('nid', $id, '=');
    $count = $query->countQuery()->execute()->fetchField();
    $results = $query->execute()->fetchAll();
    foreach ($results as $row) {
      $links['edit'] = [
        'title' => $this->t('edit'),
        'url' => Url::fromRoute('simply_signups.nodes.fields.edit', ['node' => $nid, 'fid' => $row->id]),
      ];
      $links['remove'] = [
        'title' => $this->t('remove'),
        'url' => Url::fromRoute('simply_signups.nodes.fields.remove', ['node' => $nid, 'fid' => $row->id]),
      ];
      $form['table-row'][$row->id]['#attributes']['class'][] = 'draggable';
      $form['table-row'][$row->id]['#weight'] = $row->weight;
      $field = unserialize($row->field);
      $form['table-row'][$row->id]['title'] = [
        '#markup' => $row->name . ' <small>(' . $field['#title'] . ')</small>',
      ];
      $form['table-row'][$row->id]['field_type'] = [
        '#markup' => $field['#type'],
      ];
      $form['table-row'][$row->id]['required'] = [
        '#type' => 'checkbox',
        '#default_value' => $field['#required'],
      ];
      if ($numberOfSignups == 0) {
        $form['table-row'][$row->id]['weight'] = [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @title', ['@title' => $field['#title']]),
          '#title_display' => 'invisible',
          '#default_value' => $row->weight,
          '#attributes' => ['class' => ['table-sort-weight']],
        ];
        $form['table-row'][$row->id]['operations'] = [
          'data' => [
            '#type' => 'dropbutton',
            '#links' => $links,
          ],
        ];
      }
    }
    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $id,
    ];
    if ($numberOfSignups == 0) {
      $form['actions'] = [
        '#type' => 'actions',
      ];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save Changes'),
        '#attributes' => [
          'class' => [
            'button--primary',
            'btn-primary',
          ],
        ],
      ];
      $form['actions']['add_field'] = [
        '#type' => 'submit',
        '#value'  => 'Add field',
        '#attributes' => [
          'title' => $this->t('Return to TableDrag Overview'),
        ],
        '#submit' => ['::addField'],
        '#limit_validation_errors' => [['nid']],
      ];
    }
    if ($count == 0) {
      $form['actions']['apply_template'] = [
        '#type' => 'submit',
        '#value'  => 'Apply template',
        '#attributes' => [
          'title' => $this->t('Return to TableDrag Overview'),
        ],
        '#submit' => ['::applyTemplate'],
        '#limit_validation_errors' => [['nid']],
      ];
    }
    if ($numberOfSignups == 0) {
      $form['actions']['remove'] = [
        '#type' => 'submit',
        '#value'  => 'Remove fields',
        '#attributes' => [
          'title' => $this->t('Return to TableDrag Overview'),
          'class' => [
            'btn-danger',
          ],
        ],
        '#submit' => ['::removeFields'],
        '#limit_validation_errors' => [['nid']],
      ];
      $form['actions']['cancel'] = [
        '#type' => 'submit',
        '#value'  => 'Cancel',
        '#attributes' => [
          'title' => $this->t('Return to TableDrag Overview'),
          'class' => [
            'btn-link',
          ],
        ],
        '#submit' => ['::cancel'],
        '#limit_validation_errors' => [],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function removeFields(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $form_state->setRedirect('simply_signups.nodes.fields.remove.all', ['node' => $values['nid']]);
  }

  /**
   * {@inheritdoc}
   */
  public function addField(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $form_state->setRedirect('simply_signups.nodes.fields.add', ['node' => $values['nid']]);
  }

  /**
   * {@inheritdoc}
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('simply_signups.nodes.fields');
  }

  /**
   * {@inheritdoc}
   */
  public function applyTemplate(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $form_state->setRedirect('simply_signups.nodes.fields.apply_template', ['node' => $values['nid']]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submission = $form_state->getValue('table-row');
    foreach ($submission as $id => $item) {
      $results = db_select('simply_signups_fields', 'p')
        ->fields('p')
        ->orderBy('weight')
        ->condition('id', $id, '=')
        ->execute()
        ->fetchAll();
      foreach ($results as $row) {
        $element = unserialize($row->field);
      }
      $element['#required'] = $item['required'];
      $field = serialize($element);
      $db = $this->database;
      $db->update('simply_signups_fields')
        ->fields([
          'weight' => $item['weight'],
          'field' => $field,
        ])
        ->condition('id', $id, '=')
        ->execute();
    }
    drupal_set_message($this->t('Successfully updated field(s).'));
  }

}
