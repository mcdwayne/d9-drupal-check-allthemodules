<?php

namespace Drupal\entity_counter\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_counter\Plugin\EntityCounterSourceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to manage entity counter sources.
 */
class EntityCounterSourcesForm extends EntityForm {

  /**
   * The entity counter.
   *
   * @var \Drupal\entity_counter\Entity\EntityCounterInterface
   */
  protected $entity;

  /**
   * Entity counter source manager.
   *
   * @var \Drupal\entity_counter\Plugin\EntityCounterSourceManagerInterface
   */
  protected $sourceManager;

  /**
   * Constructs an EntityCounterSourcesForm.
   *
   * @param \Drupal\entity_counter\Plugin\EntityCounterSourceManagerInterface $source_manager
   *   The entity counter source manager.
   */
  public function __construct(EntityCounterSourceManagerInterface $source_manager) {
    $this->sourceManager = $source_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_counter.source')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter */
    $entity_counter = $this->getEntity();

    $user_input = $form_state->getUserInput();

    // Build table header.
    $header = [
      ['data' => $this->t('Title / Description')],
      ['data' => $this->t('ID'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      ['data' => $this->t('Summary'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      ['data' => $this->t('Status'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      ['data' => $this->t('Weight'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      ['data' => $this->t('Operations')],
    ];

    // Build table rows for sources.
    $sources = $entity_counter->getSources();
    $rows = [];
    foreach ($sources as $source_id => $source) {
      $row['#attributes']['class'][] = 'draggable';
      $row['#attributes']['data-source-key'] = $source_id;

      $row['#weight'] = (isset($user_input['sources']) && isset($user_input['sources'][$source_id])) ? $user_input['sources'][$source_id]['weight'] : NULL;

      $row['handler'] = [
        '#tree' => FALSE,
        'data' => [
          'label' => [
            '#type' => 'link',
            '#title' => $source->label(),
            '#url' => Url::fromRoute('entity.entity_counter.source.edit_form', [
              'entity_counter' => $entity_counter->id(),
              'entity_counter_source' => $source_id,
            ]),
            '#attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'height' => 'auto',
                'width' => 'auto',
              ]),
            ],
          ],
          'description' => [
            '#prefix' => '<br/>',
            '#markup' => $source->description(),
          ],
        ],
      ];

      $row['id'] = [
        'data' => ['#markup' => $source->getSourceId()],
      ];

      $row['summary'] = $source->getSummary();

      $row['status'] = [
        'data' => [
          '#markup' => ($source->isEnabled()) ? $this->t('Enabled') : $this->t('Disabled'),
        ],
      ];

      $row['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $source->label()]),
        '#title_display' => 'invisible',
        '#delta' => 50,
        '#default_value' => $source->getWeight(),
        '#attributes' => [
          'class' => ['entity-counter-source-order-weight'],
        ],
      ];

      // @TODO: Disable edit operations if the entity counter has transactions of this type.
      // @TODO: Add operations for remove and requeue transactions.
      $operations = [];
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('entity.entity_counter.source.edit_form', [
          'entity_counter' => $entity_counter->id(),
          'entity_counter_source' => $source_id,
        ]),
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'height' => 'auto',
            'width' => 'auto',
          ]),
        ],
      ];
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('entity.entity_counter.source.delete_form', [
          'entity_counter' => $entity_counter->id(),
          'entity_counter_source' => $source_id,
        ]),
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'height' => 'auto',
            'width' => 'auto',
          ]),
        ],
      ];
      $row['operations'] = [
        '#type' => 'operations',
        '#links' => $operations,
      ];

      $rows[$source_id] = $row;
    }

    // Add sources link action.
    $form['sources_add_form'] = [
      '#theme' => 'menu_local_action',
      '#link' => [
        'title' => $this->t('Add source'),
        'url' => Url::fromRoute('entity.entity_counter.source.add_page', ['entity_counter' => $entity_counter->id()]),
        'localized_options' => [
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'height' => 'auto',
              'width' => 'auto',
            ]),
          ],
        ],
      ],
      '#attributes' => ['class' => ['action-links']],
      '#cache' => [
        'contexts' => ['user.permissions'],
      ],
    ];

    // Build the list of existing entity counter sources for this entity
    // counter.
    $form['sources'] = [
      '#type' => 'table',
      '#header' => $header,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'entity-counter-source-order-weight',
        ],
      ],
      '#attributes' => [
        'id' => 'entity-counter-sources',
      ],
      '#empty' => $this->t('There are currently no sources setup for this entity counter.'),
    ] + $rows;

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $form = parent::actionsElement($form, $form_state);

    $form['submit']['#value'] = $this->t('Save sources');
    unset($form['delete']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update entity counter source weights.
    if (!$form_state->isValueEmpty('sources')) {
      $this->updateSourceWeights($form_state->getValue('sources'));
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter */
    $entity_counter = $this->getEntity();
    $entity_counter->save();

    $context = [
      '@label' => $entity_counter->label(),
      'link' => $entity_counter->toLink($this->t('Edit'))->toString(),
    ];
    $this->logger('entity_counter')->notice('Entity counter @label source saved.', $context);

    drupal_set_message($this->t('Entity counter %label source saved.', ['%label' => $entity_counter->label()]));
  }

  /**
   * Updates entity counter source weights.
   *
   * @param array $sources
   *   Associative array with sources having handler ids as keys and array with
   *   source data as values.
   */
  protected function updateSourceWeights(array $sources) {
    foreach ($sources as $source_id => $handler_data) {
      if ($this->entity->getsources()->has($source_id)) {
        $this->entity->getsource($source_id)->setWeight($handler_data['weight']);
      }
    }
  }

}
