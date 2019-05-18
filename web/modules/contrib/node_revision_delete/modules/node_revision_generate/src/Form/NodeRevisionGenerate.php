<?php

namespace Drupal\node_revision_generate\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class NodeRevisionGenerate.
 */
class NodeRevisionGenerate extends FormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new NodeRevisionGenerate object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_revision_generate_generate_revisions';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get all Content types.
    $content_type_code = [];
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $type) {
      $content_type_code[$type->id()] = $type->label();
    }

    $form['bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types'),
      '#options' => $content_type_code,
    ];

    $form['revisions_number'] = [
      '#type' => 'number',
      '#title' => $this->t('Revisions number'),
      '#min' => 1,
      '#default_value' => 1,
      '#description' => $this->t('The number of revisions that will be created for each node of the selected content types.'),
    ];

    $form['age'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Revisions age"),
      '#description' => $this->t('The age between each generated revision.'),
    ];

    $form['age']['number'] = [
      '#type' => 'number',
      '#min' => 1,
      '#default_value' => 1,
    ];

    $time_options = [
      '86400' => $this->t('Day'),
      '604800' => $this->t('Week'),
      '2592000' => $this->t('Month'),
    ];

    $form['age']['time'] = [
      '#type' => 'select',
      '#options' => $time_options,
    ];

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('The first revision will be generated starting from the created date of the last node revision and the last one will not have a date in the future. So, depending on this maybe we will not generate the number of revisions you expect.'),
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate revisions'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addMessage('Revisions generated');
  }

}
