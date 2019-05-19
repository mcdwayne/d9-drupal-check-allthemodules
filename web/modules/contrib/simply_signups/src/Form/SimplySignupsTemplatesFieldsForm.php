<?php

namespace Drupal\simply_signups\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Implements a signup form.
 */
class SimplySignupsTemplatesFieldsForm extends FormBase {

  protected $database;
  protected $currentPath;

  /**
   * Implements __construct function.
   */
  public function __construct(Connection $database_connection, CurrentPathStack $current_path) {
    $this->database = $database_connection;
    $this->currentPath = $current_path;
  }

  /**
   * Implements create function.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('path.current')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simply_signups_templates_fields_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $path = $this->currentPath->getPath();
    $currentPath = ltrim($path, '/');
    $arg = explode('/', $currentPath);
    $tid = $arg[4];
    $db = $this->database;
    $query = $db->select('simply_signups_templates', 'p');
    $query->fields('p');
    $query->condition('id', $tid, '=');
    $count = $query->countQuery()->execute()->fetchField();
    if ($count == 0) {
      throw new NotFoundHttpException();
    }
    $form['#attached']['library'][] = 'simply_signups/styles';
    $form['#attributes'] = [
      'class' => ['simply-signups-templates-fields-form', 'simply-signups-form'],
    ];
    $query = $db->select('simply_signups_templates_fields', 'p');
    $query->fields('p');
    $query->orderBy('weight');
    $query->condition('tid', $tid, '=');
    $results = $query->execute()->fetchAll();
    $form['table-row'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Title'),
        $this->t('Type'),
        $this->t('Required'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No template fields found.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-sort-weight',
        ],
      ],
    ];
    foreach ($results as $row) {
      $rowCount = $query->countQuery()->execute()->fetchField();
      if ($rowCount > 0) {
        $links['edit'] = [
          'title' => $this->t('edit'),
          'url' => Url::fromRoute('simply_signups.templates.fields.edit', ['tid' => $tid, 'fid' => $row->id]),
        ];
        $links['remove'] = [
          'title' => $this->t('remove'),
          'url' => Url::fromRoute('simply_signups.templates.fields.remove', ['tid' => $tid, 'fid' => $row->id]),
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
    $form['tid'] = [
      '#type' => 'hidden',
      '#value' => $tid,
    ];
    $form['count'] = [
      '#type' => 'hidden',
      '#value' => $count,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => ($count == 0) ? $this->t('Go back') : $this->t('Save changes'),
      '#attributes' => [
        'class' => [
          'button--primary',
        ],
      ],
    ];
    $form['actions']['add'] = [
      '#type' => 'submit',
      '#value'  => 'Add new field',
      '#attributes' => [
        'title' => $this->t('Add a new field to current template'),
        'class' => [
          'button--primary',
          'btn-primary',
        ],
      ],
      '#submit' => ['::add'],
      '#limit_validation_errors' => [['tid']],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value'  => 'Cancel',
      '#attributes' => [
        'title' => $this->t('Return to templates'),
        'class' => [
          'button--danger',
          'btn-link',
        ],
      ],
      '#submit' => ['::cancel'],
      '#limit_validation_errors' => [],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function add(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $tid = $values['tid'];
    $form_state->setRedirect('simply_signups.templates.fields.add', ['tid' => $tid]);
  }

  /**
   * {@inheritdoc}
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('simply_signups.templates');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submission = $form_state->getValue('table-row');
    foreach ($submission as $id => $item) {
      $results = db_select('simply_signups_templates_fields', 'p')
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
      $db->update('simply_signups_templates_fields')
        ->fields([
          'weight' => $item['weight'],
          'field' => $field,
        ])
        ->condition('id', $id, '=')
        ->execute();
    }
    drupal_set_message($this->t('Template fields updated successfully.'));
  }

}
