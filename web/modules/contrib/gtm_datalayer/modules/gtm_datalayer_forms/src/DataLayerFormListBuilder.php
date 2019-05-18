<?php

namespace Drupal\gtm_datalayer_forms;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of GTM dataLayer Form entities.
 *
 * @see \Drupal\gtm_datalayer_forms\Entity\DataLayerForm
 */
class DataLayerFormListBuilder extends ConfigEntityListBuilder implements FormInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  protected $limit = FALSE;

  /**
   * Constructs a new DataLayerListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, FormBuilderInterface $form_builder) {
    parent::__construct($entity_type, $storage);

    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The GTM dataLayer list as a renderable array.
   */
  public function render() {
    return $this->formBuilder->getForm($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gtm_datalayer_form_form_admin_display_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'core/drupal.tableheader';
    $form['#attributes']['class'][] = 'clearfix';

    // Build the form tree.
    $form['gtm_datalayer_forms'] = $this->buildDataLayersForm();

    $form['actions'] = [
      '#tree' => FALSE,
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save dataLayers'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Builds the main "dataLayers" portion of the form.
   *
   * @return array
   */
  protected function buildDataLayersForm() {
    // Build GTM dataLayer Forms first.
    $data_layers = [];
    $entities = $this->load();
    /** @var \Drupal\gtm_datalayer_forms\Entity\DataLayerFormInterface[] $entities */
    foreach ($entities as $entity_id => $entity) {
      $data_layers[$entity_id] = [
        'label' => $entity->label(),
        'entity_id' => $entity_id,
        'plugin' => $entity->getPlugin(),
        'weight' => $entity->getWeight(),
        'status' => $entity->status(),
        'entity' => $entity,
      ];
    }

    $form = [
      '#type' => 'table',
      '#header' => [
        $this->t('dataLayer'),
        $this->t('Machine name'),
        $this->t('Plugin'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No dataLayer available. <a href=":link">Add dataLayer</a>.', [
        ':link' => Url::fromRoute('entity.gtm_datalayer_form.add_form')->toString()
      ]),
      '#attributes' => [
        'id' => 'datalayers',
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'data-layer-form-weight',
        ],
      ],
    ];

    foreach ($data_layers as $entity_id => $data_layer) {
      $form[$entity_id] = [
        '#attributes' => [
          'class' => [
            'draggable',
            $data_layer['status'] ? 'data-layer-form-enabled' : 'data-layer-form-disabled',
          ],
        ],
      ];
      $form[$entity_id]['info'] = [
        '#plain_text' => $data_layer['status'] ? $data_layer['label'] : $this->t('@label (disabled)', ['@label' => $data_layer['label']]),
        '#wrapper_attributes' => [
          'class' => ['data-layer-form-label'],
        ],
      ];
      $form[$entity_id]['entity_id'] = [
        '#plain_text' => $data_layer['entity_id'],
        '#wrapper_attributes' => [
          'class' => ['data-layer-form-id'],
        ],
      ];
      $form[$entity_id]['plugin'] = [
        '#plain_text' => $data_layer['plugin'],
        '#wrapper_attributes' => [
          'class' => ['data-layer-form-plugin'],
        ],
      ];
      $form[$entity_id]['weight'] = [
        '#type' => 'weight',
        '#default_value' => $data_layer['weight'],
        '#title' => $this->t('Weight for @data_layer_form dataLayer Form', ['@data_layer_form' => $data_layer['label']]),
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' => ['data-layer-form-weight'],
        ],
      ];
      $form[$entity_id]['operations'] = $this->buildOperations($data_layer['entity']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = $this->t('Configure');
    }
    if (isset($operations['delete'])) {
      $operations['delete']['title'] = $this->t('Remove');
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entities = $this->getStorage()->loadMultiple(array_keys($form_state->getValue('gtm_datalayer_forms')));
    /** @var \Drupal\gtm_datalayer_forms\Entity\DataLayerFormInterface[] $entities */
    foreach ($entities as $entity_id => $entity) {
      $entity_values = $form_state->getValue(['gtm_datalayer_forms', $entity_id]);
      $entity->setWeight($entity_values['weight']);
      $entity->save();
    }
    drupal_set_message($this->t('The dataLayers settings have been updated.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    return $this->getStorage()->getQuery()
      ->sort('weight')
      ->execute();
  }

}
