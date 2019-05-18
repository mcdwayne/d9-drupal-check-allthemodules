<?php

namespace Drupal\tr_rulez\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\rules\Core\RulesEventManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of ReactionRuleConfig entities.
 *
 * @see \Drupal\rules\Entity\ReactionRuleConfig
 * @see \Drupal\views_ui\ViewListBuilder
 */
class RulesReactionListBuilder extends ConfigEntityListBuilder {

  /**
   * The Rules event plugin manager.
   *
   * @var \Drupal\Core\RulesEventManager
   */
  protected $eventManager;

  /**
   * Constructs a new RulesReactionListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\RulesEventManager $eventManager
   *   The Rules event plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RulesEventManager $eventManager) {
    parent::__construct($entity_type, $storage);
    // This list builder uses client-side filters which requires all entities to
    // be listed, disable the pager.
    $this->limit = FALSE;
    $this->eventManager = $eventManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.rules_event')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = [
      'enabled' => [],
      'disabled' => [],
    ];
    foreach (parent::load() as $entity) {
      if ($entity->status()) {
        $entities['enabled'][] = $entity;
      }
      else {
        $entities['disabled'][] = $entity;
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the reaction rules list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['label'] = $this->t('Reaction Rule');
    $header['event'] = $this->t('Event');
    $header['description'] = $this->t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $event_names = $entity->getEventNames();
    // @todo We only grab the first Event. Why is this an array?
    $event_definition = $this->eventManager->getDefinition(array_shift($event_names));

    /** @var \Drupal\rules\Entity\ReactionRuleConfig $entity */
    $details = $this->t('Machine name: @name', ['@name' => $entity->id()]);
    if ($entity->hasTags()) {
      $details = $details . '<br />' . $this->t('Tags: @tags', ['@tags' => implode(', ', $entity->getTags())]);
    }

    $row['label']['data-drupal-selector'] = 'rules-table-filter-text-source';
    $row['label']['data'] = [
      '#plain_text' => $this->getLabel($entity),
      '#suffix' => '<div class="description">' . $details . '</div>',
    ];
    $row['event']['data-drupal-selector'] = 'rules-table-filter-text-source';
    $row['event']['data'] = [
      '#plain_text' => $event_definition['label'],
    ];
    $row['description']['data-drupal-selector'] = 'rules-table-filter-text-source';
    $row['description']['data'] = [
      '#type' => 'processed_text',
      '#text' => $entity->getDescription(),
      '#format' => 'restricted_html',
    ];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    $build = parent::buildOperations($entity);
    $build['#links']['clone'] = [
      'title' => $this->t('Clone'),
      'url' => Url::fromRoute('entity.rules_reaction_rule.clone', ['rules_reaction_rule' => $entity->id()]),
      // Active selection is weight 0, 'edit' is 10, 'delete' is 100.
      'weight' => 20,
    ];

    uasort($build['#links'], 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['description'] = [
      '#prefix' => '<p>',
      // @todo Uncomment when the Rules module removes this text
      // from hook_help().
      // '#markup' => $this->t('Reaction rules, listed below, react on selected events on the site. Each reaction rule may fire any number of <em>actions</em>, and may have any number of <em>conditions</em> that must be met for the actions to be executed. You can also set up <a href=":components">components</a> – stand-alone sets of Rules configuration that can be used in Rules and other parts of your site. See <a href=":documentation">the online documentation</a> for an introduction on how to use Rules.', [
      //   ':components' => Url::fromRoute('entity.rules_component.collection')->toString(),
      //   ':documentation' => 'https://www.drupal.org/node/298480',
      // ]),
      '#suffix' => '</p>',
    ];

    $entities = $this->load();
    $build['#type'] = 'container';
    $build['#attributes']['id'] = 'rules-entity-list';

    $build['#attached']['library'][] = 'core/drupal.ajax';
    $build['#attached']['library'][] = 'tr_rulez/rules_ui.listing';

    $build['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];

    $build['filters']['text'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 60,
      '#placeholder' => $this->t('Filter by rule name, machine name, event, description, or tag'),
      '#attributes' => [
        'class' => ['rules-filter-text'],
        'data-table' => '.rules-listing-table',
        'autocomplete' => 'off',
        'title' => $this->t('Enter a part of the rule name, machine name, event, description, or tag to filter by.'),
      ],
    ];

    $build['enabled']['heading'] = [
      '#prefix' => '<h2>',
      '#markup' => $this->t('Enabled', [], ['context' => 'Plural']),
      '#suffix' => '</h2>',
    ];
    $build['disabled']['heading'] = [
      '#prefix' => '<h2>',
      '#markup' => $this->t('Disabled', [], ['context' => 'Plural']),
      '#suffix' => '</h2>',
    ];

    foreach (['enabled', 'disabled'] as $status) {
      $build[$status]['#type'] = 'container';
      $build[$status]['#attributes'] = ['class' => ['rules-list-section', $status]];
      $build[$status]['table'] = [
        '#type' => 'table',
        '#header' => $this->buildHeader(),
        '#attributes' => ['class' => ['rules-listing-table', $status]],
        '#cache' => [
          'contexts' => $this->entityType->getListCacheContexts(),
          'tags' => $this->entityType->getListCacheTags(),
        ],
      ];
      foreach ($entities[$status] as $entity) {
        $build[$status]['table']['#rows'][$entity->id()] = $this->buildRow($entity);
      }
    }
    $build['enabled']['table']['#empty'] = $this->t('There are no enabled @label.', [
      '@label' => $this->entityType->getPluralLabel(),
    ]);
    $build['disabled']['table']['#empty'] = $this->t('There are no disabled @label.', [
      '@label' => $this->entityType->getPluralLabel(),
    ]);

    return $build;
  }

}
