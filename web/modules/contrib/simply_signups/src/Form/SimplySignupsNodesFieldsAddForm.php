<?php

namespace Drupal\simply_signups\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Implements a signup form.
 */
class SimplySignupsNodesFieldsAddForm extends FormBase {

  protected $currentPath;
  protected $entityTypeManager;

  /**
   * Implements __construct function.
   */
  public function __construct(CurrentPathStack $current_path, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentPath = $current_path;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Implements create function.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.current'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simply_signups_nodes_fields_add_form';
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
    $form['#attached']['library'][] = 'simply_signups/styles';
    $form['#attributes'] = [
      'class' => ['simply-signups-nodes-fields-add-form', 'simply-signups-form'],
    ];
    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $id,
    ];
    $form['field_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Field Type'),
      '#options' => [
        'checkboxes' => $this->t('Checkboxes'),
        'checkbox' => $this->t('Checkbox'),
        'email' => $this->t('Email'),
        'hidden' => $this->t('Hidden'),
        'number' => $this->t('Number'),
        'radio' => $this->t('Radio'),
        'radios' => $this->t('Radios'),
        'select' => $this->t('Select'),
        'tel' => $this->t('Telephone'),
        'textarea' => $this->t('Textarea'),
        'textfield' => $this->t('Textfield'),
      ],
      '#description' => $this->t('Select the type of field that you wish to add.'),
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Select field type'),
      '#attributes' => [
        'class' => [
          'button--primary',
          'btn-primary',
        ],
      ],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value'  => 'Cancel',
      '#attributes' => [
        'title' => $this->t('Return to template fields'),
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
    $nid = $values['nid'];
    $form_state->setRedirect('simply_signups.nodes.fields', ['node' => $nid]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $nid = $values['nid'];
    $form_state->setRedirect('simply_signups.nodes.fields.' . $values['field_type'], ['node' => $nid]);
  }

}
