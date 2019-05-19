<?php

namespace Drupal\simply_signups\Form\Field;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Implements a signup form.
 */
class SimplySignupsFieldsNumberForm extends FormBase {

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
    return 'simply_signups_fields_number_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $path = $this->currentPath->getPath();
    $currentPath = ltrim($path, '/');
    $arg = explode('/', $currentPath);
    $request = 'invalid';
    $operation = 'invalid';
    if ($arg[0] == 'simply-signups') {
      $request = $arg[0];
      if (($arg[3] == 'add')) {
        $operation = $arg[3];
      }
      if (($arg[4] == 'edit')) {
        $operation = $arg[4];
      }
    }
    if ($arg[3] == 'templates') {
      $request = $arg[3];
      if (($arg[5] == 'add') or ($arg[5] == 'edit')) {
        $operation = $arg[5];
      }
    }
    $db = $this->database;
    if ($request == 'templates') {
      $tid = $arg[4];
      $query = $db->select('simply_signups_templates', 'p');
      $query->fields('p');
      $query->condition('id', $tid, '=');
      $count = $query->countQuery()->execute()->fetchField();
      if ($count == 0) {
        throw new NotFoundHttpException();
      }
      if ($operation == 'edit') {
        $fid = $arg[6];
        $query = $db->select('simply_signups_templates_fields', 'p');
        $query->fields('p');
        $query->condition('id', $fid, '=');
        $count = $query->countQuery()->execute()->fetchField();
        if ($count == 0) {
          throw new NotFoundHttpException();
        }
        $results = $query->execute()->fetchAll();
        foreach ($results as $row) {
          $field = unserialize($row->field);
        }
        $form['fid'] = [
          '#type' => 'hidden',
          '#value' => $fid,
        ];
      }
      $form['tid'] = [
        '#type' => 'hidden',
        '#value' => $tid,
      ];
    }
    if ($request == 'simply-signups') {
      $nid = $arg[1];
      $node_storage = $this->entityTypeManager->getStorage('node');
      $node = $node_storage->load($nid);
      $isValidNode = (isset($node)) ? TRUE : FALSE;
      if (!$isValidNode) {
        throw new NotFoundHttpException();
      }
      $id = $node->id();
      if ($operation == 'edit') {
        $fid = $arg[3];
        $query = $db->select('simply_signups_fields', 'p');
        $query->fields('p');
        $query->condition('id', $fid, '=');
        $count = $query->countQuery()->execute()->fetchField();
        if ($count == 0) {
          throw new NotFoundHttpException();
        }
        $results = $query->execute()->fetchAll();
        foreach ($results as $row) {
          $field = unserialize($row->field);
        }
        $form['fid'] = [
          '#type' => 'hidden',
          '#value' => $fid,
        ];
      }
    }
    if ($request == 'simply-signups') {
      $form['nid'] = [
        '#type' => 'hidden',
        '#value' => $id,
      ];
    }
    $form['#attached']['library'][] = 'simply_signups/styles';
    $form['#attributes'] = [
      'class' => ['simply-signups-fields-number-form', 'simply-signups-form'],
    ];
    $form['request'] = [
      '#type' => 'hidden',
      '#value' => $request,
    ];
    $form['operation'] = [
      '#type' => 'hidden',
      '#value' => $operation,
    ];
    $form['weight'] = [
      '#type' => 'hidden',
      '#value' => (isset($row->weight)) ? $row->weight : 100,
    ];
    $form['type'] = [
      '#type' => 'hidden',
      '#value' => 'number',
    ];
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#required' => TRUE,
      '#default_value' => (isset($row->name)) ? $row->name : '',
    ];
    $form['machine_name'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'source' => [
          'title',
        ],
      ],
      '#default_value' => (isset($field['#title'])) ? $field['#title'] : FALSE,
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Help Text'),
      '#description' => $this->t('Instructions to present to the user below this field on the editing form.<br />Allowed HTML tags: &lt;a&gt; &lt;b&gt; &lt;big&gt; &lt;code&gt; &lt;del&gt; &lt;em&gt; &lt;i&gt; &lt;ins&gt; &lt;pre&gt; &lt;q&gt; &lt;small&gt; &lt;span&gt; &lt;strong&gt; &lt;sub&gt; &lt;sup&gt; &lt;tt&gt; &lt;ol&gt; &lt;ul&gt; &lt;li&gt; &lt;p&gt; &lt;br&gt; &lt;img&gt;'),
      '#default_value' => (isset($field['#description'])) ? $field['#description'] : '',
    ];
    $form['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required field'),
      '#return_value' => 1,
      '#default_value' => (isset($field['#required'])) ? $field['#required'] : 0,
    ];
    $form['default_value_input'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Default Value'),
      '#description' => $this->t('The default value for this field, used when creating new content.'),
    ];
    $form['default_value_input']['default_value'] = [
      '#type' => 'number',
      '#title' => $this->t('Default number'),
      '#step' => 1,
      '#default_value' => (isset($field['#default_value'])) ? $field['#default_value'] : '',
    ];
    $form['min'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum'),
      '#step' => 1,
      '#description' => $this->t('The minimum value that should be allowed in this field. Leave blank for no minimum.'),
      '#default_value' => (isset($field['#min'])) ? $field['#min'] : '',
    ];
    $form['max'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum'),
      '#step' => 1,
      '#description' => $this->t('The maximum value that should be allowed in this field. Leave blank for no maximum.'),
      '#default_value' => (isset($field['#max'])) ? $field['#max'] : '',
    ];
    $form['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#description' => $this->t("Define a string that should be prefixed to the value, like '$' or '€'. Leave blank for none. Separate singular and plural values with a pipe ('pound|pounds')."),
      '#default_value' => (isset($field['#prefix'])) ? $field['#prefix'] : '',
    ];
    $form['suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix'),
      '#description' => $this->t("Define a string that should be suffixed to the value, like 'm', 'kb/s'. Leave blank for none. Separate singular and plural values with a pipe ('pound|pounds')."),
      '#default_value' => (isset($field['#suffix'])) ? $field['#suffix'] : '',
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#attributes' => [
        'class' => [
          'button--primary',
          'btn-success',
        ],
      ],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value'  => 'Cancel',
      '#attributes' => [
        'title' => $this->t('Cancel this action'),
        'class' => [
          'button--danger',
          'btn-link',
        ],
      ],
      '#submit' => ['::cancel'],
      '#limit_validation_errors' => [['tid'], ['nid']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if ($values['nid']) {
      $form_state->setRedirect('simply_signups.nodes.fields', ['node' => $values['nid']]);
    }
    if ($values['tid']) {
      $form_state->setRedirect('simply_signups.templates.fields', ['tid' => $values['tid']]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $requestTime = $this->time->getCurrentTime();
    $field = [
      '#type' => $values['type'],
      '#title' => $values['machine_name'],
      '#required' => ($values['required'] == 1) ? 1 : 0,
      '#min' => (!empty($values['min'])) ? $values['min'] : '',
      '#max' => (!empty($values['max'])) ? $values['max'] : '',
      '#prefix' => (!empty($values['prefix'])) ? $values['prefix'] : '',
      '#suffix' => (!empty($values['suffix'])) ? $values['suffix'] : '',
      '#description' => (!empty($values['description'])) ? $values['description'] : '',
      '#default_value' => (!empty($values['default_value'])) ? $values['default_value'] : '',
    ];
    $db = $this->database;
    if ($values['request'] == 'templates') {
      if ($values['operation'] == 'add') {
        $row = [
          'tid' => $values['tid'],
          'name' => $values['title'],
          'field' => serialize($field),
          'weight' => $values['weight'],
          'updated' => $requestTime,
          'created' => $requestTime,
        ];
        $db->insert('simply_signups_templates_fields')
          ->fields($row)
          ->execute();
        $form_state->setRedirect('simply_signups.templates.fields', ['tid' => $values['tid']]);
        drupal_set_message($this->t('Successfully added template field <em>@title</em>.', ['@title' => $values['title']]));
      }
      if ($values['operation'] == 'edit') {
        $row = [
          'tid' => $values['tid'],
          'name' => $values['title'],
          'field' => serialize($field),
          'weight' => $values['weight'],
          'updated' => $requestTime,
        ];
        $db->update('simply_signups_templates_fields')
          ->fields($row)
          ->condition('id', $values['fid'], '=')
          ->condition('tid', $values['tid'], '=')
          ->execute();
        $form_state->setRedirect('simply_signups.templates.fields', ['tid' => $values['tid']]);
        drupal_set_message($this->t('Successfully edited template field <em>@title</em>.', ['@title' => $values['title']]));
      }
    }
    if ($values['request'] == 'simply-signups') {
      if ($values['operation'] == 'add') {
        $row = [
          'nid' => $values['nid'],
          'name' => $values['title'],
          'field' => serialize($field),
          'weight' => $values['weight'],
          'updated' => $requestTime,
          'created' => $requestTime,
        ];
        $db->insert('simply_signups_fields')
          ->fields($row)
          ->execute();
        $form_state->setRedirect('simply_signups.nodes.fields', ['node' => $values['nid']]);
        drupal_set_message($this->t('Successfully added field <em>@title</em>.', ['@title' => $values['title']]));
      }
      if ($values['operation'] == 'edit') {
        $row = [
          'nid' => $values['nid'],
          'name' => $values['title'],
          'field' => serialize($field),
          'weight' => $values['weight'],
          'updated' => $requestTime,
        ];
        $db->update('simply_signups_fields')
          ->fields($row)
          ->condition('id', $values['fid'], '=')
          ->condition('nid', $values['nid'], '=')
          ->execute();
        $form_state->setRedirect('simply_signups.nodes.fields', ['node' => $values['nid']]);
        drupal_set_message($this->t('Successfully edited field <em>@title</em>.', ['@title' => $values['title']]));
      }
    }
  }

}
