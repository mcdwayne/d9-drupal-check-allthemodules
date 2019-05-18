<?php

namespace Drupal\business_rules;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Schedule entities.
 *
 * @ingroup business_rules
 */
class ScheduleListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    // TODO: show links for schedules tasks and executed tasks.
    $header['id']             = $this->t('Schedule ID');
    $header['triggered_by']   = $this->t('Triggered by');
    $header['name']           = $this->t('Name');
    $header['scheduled_date'] = $this->t('Scheduled Date');
    $header['executed']       = $this->t('Executed');
    $header['execution_date'] = $this->t('Execution Date');
    $header['filter']         = [
      'data'  => ['#markup' => 'filter'],
      'style' => 'display: none',
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    if (!$entity->id() || !$entity->getTriggeredBy()) {
      return [];
    }
    /* @var $entity \Drupal\business_rules\Entity\Schedule */
    $row['id']             = $entity->id();
    $row['triggered_by']   = Link::createFromRoute($entity->getTriggeredBy()
      ->id(),
      'entity.business_rules_action.edit_form',
      ['business_rules_action' => $entity->getTriggeredBy()->id()]
    );
    $row['name']           = Link::createFromRoute(
      $entity->label(),
      'entity.business_rules_schedule.edit_form',
      ['business_rules_schedule' => $entity->id()]
    );
    $scheduled             = $entity->getScheduled() ? \Drupal::service('date.formatter')
      ->format($entity->getScheduled(), 'medium') : '';
    $executed              = $entity->getExecutedTime() ? \Drupal::service('date.formatter')
      ->format($entity->getExecutedTime(), 'medium') : '';
    $row['scheduled_date'] = $scheduled;
    $row['executed']       = $entity->isExecuted() ? $this->t('Yes') : $this->t('No');
    $row['execution_date'] = $executed;

    $search_string = $entity->id() . ' ' .
      $entity->label() . ' ' .
      $entity->getTriggeredBy()->id() . ' ' .
      $entity->getName() . ' ' .
      $scheduled . ' ' .
      $executed;

    $row['filter'] = [
      'data'  => [['#markup' => '<span class="table-filter-text-source">' . $search_string . '</span>']],
      'style' => ['display: none'],
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    $operations['execute'] = [
      'title'  => $this->t('Execute'),
      'url'    => Url::fromRoute('entity.business_rules_schedule.execute', ['business_rules_schedule' => $entity->id()]),
      'weight' => 20,
    ];
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {

    $output['#attached']['library'][] = 'system/drupal.system.modules';

    $output['filters'] = [
      '#type'       => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];

    $output['filters']['text'] = [
      '#type'        => 'search',
      '#title'       => $this->t('Search'),
      '#size'        => 30,
      '#placeholder' => $this->t('Search for a item'),
      '#attributes'  => [
        'class'        => ['table-filter-text'],
        'data-table'   => '.searchable-list',
        'autocomplete' => 'off',
        'title'        => $this->t('Enter a part of the item to filter by.'),
      ],
    ];

    $output += parent::render();
    if (!isset($output['table']['#attributes']['class'])) {
      $output['table']['#attributes']['class'] = ['searchable-list'];
    }
    else {
      $output['table']['#attributes']['class'][] = ['searchable-list'];
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $view_mode = \Drupal::request()->get('view_mode');

    if ($view_mode == 'not_executed') {
      $entity_ids = \Drupal::entityQuery('business_rules_schedule')
        ->condition('status', 1, '<>')
        ->sort('scheduled', 'ASC')
        ->execute();
    }
    elseif ($view_mode == 'executed') {
      $entity_ids = \Drupal::entityQuery('business_rules_schedule')
        ->condition('status', 1, '=')
        ->sort('executed', 'DESC')
        ->execute();
    }
    else {
      $entity_ids = \Drupal::entityQuery('business_rules_schedule')
        ->sort('id', 'DESC')
        ->execute();
    }

    return $this->storage->loadMultiple($entity_ids);
  }

}
