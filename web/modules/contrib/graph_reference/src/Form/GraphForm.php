<?php

namespace Drupal\graph_reference\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GraphForm.
 *
 * @package Drupal\graph_reference\Form
 */
class GraphForm extends EntityForm {

  /**
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  public function __construct(EntityTypeRepositoryInterface $entity_type_repository) {
    $this->entityTypeRepository = $entity_type_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.repository')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $graph = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $graph->label(),
      '#description' => $this->t("Label for the Graph."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $graph->id(),
      '#machine_name' => [
        'exists' => '\Drupal\graph_reference\Entity\Graph::load',
      ],
      '#disabled' => !$graph->isNew(),
    ];

    $form['vertex_set'] = $this->vertexSetForm($form_state);

    return $form;
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  protected function vertexSetForm(FormStateInterface $form_state) {
    $vertex_set = $this->getEntity()->get('vertex_set');

    $form = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    $form['plugin_id'] = [
      '#type' => 'value',
      '#value' => 'entity_type'
    ];

    $entity_type = isset($vertex_set['options']['entity_type']) ? $vertex_set['options']['entity_type'] : NULL;

    $entity_type_labels = $this->entityTypeRepository->getEntityTypeLabels(TRUE);

    $form['options'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      'entity_type' => [
        '#type' => 'select',
        '#title' => $this->t('Entity type'),
        '#options' => current($entity_type_labels),
        '#required' => TRUE,
        '#empty_title' => $this->t('- Select -'),
        '#default_value' => $entity_type
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $graph = $this->entity;
    $status = $graph->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Graph.', [
          '%label' => $graph->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Graph.', [
          '%label' => $graph->label(),
        ]));
    }
    $form_state->setRedirectUrl($graph->urlInfo('collection'));
  }

  /**
   * @return \Drupal\graph_reference\Entity\GraphInterface
   */
  public function getEntity() {
    return parent::getEntity();
  }
}
