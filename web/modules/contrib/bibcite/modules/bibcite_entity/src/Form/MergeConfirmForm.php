<?php

namespace Drupal\bibcite_entity\Form;

use Drupal\bibcite_entity\Entity\ReferenceInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirm merge of bibliographic entities.
 */
class MergeConfirmForm extends ConfirmFormBase {

  /**
   * This entity will be merged to target.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $source;

  /**
   * Source entity will be merged to this one.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $target;

  /**
   * The field name for filtering.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('current_route_match'));
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(RouteMatchInterface $route_match) {
    $parameter_name = $route_match->getRouteObject()->getOption('_bibcite_entity_type_id');

    $this->source = $route_match->getParameter($parameter_name);
    $this->target = $route_match->getParameter("{$parameter_name}_target");
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bibcite_entity_merge_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to merge @source to @target?', [
      '@source' => $this->source->label(),
      '@target' => $this->target->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->source->toUrl('bibcite-merge-form');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $field_name = NULL) {
    $this->fieldName = $field_name;

    $statistic = $this->getAuthoredReferencesStatistic();

    $form['references'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('This operation will cause changes in these references'),
      'items' => [
        '#markup' => $this->t('No one reference will be changed.'),
      ],
    ];

    if (count($statistic['entities']) > 0) {
      $items = array_map(function (ReferenceInterface $reference) {
        return $reference->label();
      }, $statistic['entities']);

      $form['references']['items'] = [
        '#theme' => 'item_list',
        '#items' => $items,
      ];
    }

    if ($statistic['count'] > 0) {
      $form['references']['count'] = [
        '#markup' => $this->t('and @count more', ['@count' => $statistic['count']]),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch = [
      'title' => t('Merging'),
      'operations' => [
        [
          'bibcite_entity_merge_entity', [
            $this->source->id(),
            $this->target->id(),
            $this->source->getEntityTypeId(),
            $this->fieldName,
          ],
        ],
        [
          'bibcite_entity_merge_entity_delete', [
            $this->source->id(),
            $this->source->getEntityTypeId(),
            $this->fieldName,
          ],
        ],
      ],
      'finished' => 'bibcite_entity_merge_entity_finished',
      'file' => drupal_get_path('module', 'bibcite_entity') . '/bibcite_entity.batch.inc',
    ];

    batch_set($batch);
  }

  /**
   * Find references and get statistic data.
   *
   * @return array
   *   Statistic data with first 10 objects and count of another references.
   */
  private function getAuthoredReferencesStatistic() {
    $storage = \Drupal::entityTypeManager()->getStorage('bibcite_reference');

    $range = 10;

    $query = $storage->getQuery();
    $query->condition($this->fieldName, $this->source->id());
    $query->range(0, $range);

    $entities = $storage->loadMultiple($query->execute());
    $count = $query->range()->count()->execute();

    return [
      'entities' => $entities,
      'count' => ($count > $range) ? $count - $range : 0,
    ];
  }

}
