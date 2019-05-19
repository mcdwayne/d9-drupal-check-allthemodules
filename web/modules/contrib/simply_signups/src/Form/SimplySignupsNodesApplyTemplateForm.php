<?php

namespace Drupal\simply_signups\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Implements a signup form.
 */
class SimplySignupsNodesApplyTemplateForm extends FormBase {

  protected $time;
  protected $database;
  protected $currentPath;
  protected $entityTypeManager;

  /**
   * Implements __construct function.
   */
  public function __construct(TimeInterface $time_interface, CurrentPathStack $current_path, Connection $database_connection, EntityTypeManagerInterface $entity_type_manager) {
    $this->time = $time_interface;
    $this->currentPath = $current_path;
    $this->database = $database_connection;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Implements create function.
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
    return 'simply_signups_nodes_apply_template_form';
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
    $nid = $id;
    $db = $this->database;
    $query = $db->select('simply_signups_templates', 'p');
    $query->fields('p');
    $count = $query->countQuery()->execute()->fetchField();
    $form['#attached']['library'][] = 'simply_signups/styles';
    $form['#attributes'] = [
      'class' => ['simply-signups-nodes-apply-template-form', 'simply-signups-form'],
    ];
    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $id,
    ];
    $form['count'] = [
      '#type' => 'hidden',
      '#value' => $count,
    ];
    $results = $query->execute()->fetchAll();
    foreach ($results as $row) {
      $items[$row->id] = $row->title;
    }
    if ($count > 0) {
      $form['templates'] = [
        '#type' => 'select',
        '#title' => $this->t('Choose a template to apply'),
        '#options' => $items,
      ];
    }
    if ($count == 0) {
      $links['view'] = Url::fromRoute('simply_signups.templates');
      $form['not_found_title'] = [
        '#markup' => $this->t('<h3>No templates found</h3>'),
      ];
      $form['not_found_text'] = [
        '#markup' => $this->t('<p>Could not find any templates. If you wish to create one, please visit here to <a href="@link">add a template</a></p>', ['@link' => $links['view']->toString()]),
      ];
    }
    $form['actions'] = [
      '#type' => 'actions',
    ];
    if ($count > 0) {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Apply template'),
      ];
    }
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
      '#limit_validation_errors' => [['nid']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $form_state->setRedirect('simply_signups.nodes.fields', ['node' => $values['nid']]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $nid = $values['nid'];
    $id = $values['templates'];
    $requestTime = $this->time->getCurrentTime();
    $db = $this->database;
    $query = $db->select('simply_signups_templates_fields', 'p');
    $query->fields('p');
    $query->condition('tid', $id, '=');
    $results = $query->execute()->fetchAll();
    $item = [];
    foreach ($results as $row) {
      $item['nid'] = $nid;
      $item['name'] = $row->name;
      $item['field'] = $row->field;
      $item['weight'] = $row->weight;
      $item['created'] = $requestTime;
      $item['updated'] = $requestTime;
      $db->insert('simply_signups_fields')
        ->fields($item)
        ->execute();
    }
    $form_state->setRedirect('simply_signups.nodes.fields', ['node' => $nid]);
    drupal_set_message($this->t('Successfully applied template.'));
  }

}
