<?php

namespace Drupal\entity_counter\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\entity_counter\CounterTransactionLogListBuilder;
use Drupal\entity_counter\Entity\EntityCounterInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for entity counter entity routes.
 */
class EntityCounterController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * EntityCounterController constructor.
   *
   * @param \Drupal\Core\Entity\EntityFormBuilder $form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityFormBuilder $form_builder, DateFormatter $date_formatter) {
    $this->dateFormatter = $date_formatter;
    $this->entityFormBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.form_builder'),
      $container->get('date.formatter')
    );
  }

  /**
   * Provides a page to render a single entity counter.
   *
   * @param \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter
   *   The entity counter to be rendered.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function viewEntityCounter(EntityCounterInterface $entity_counter) {
    /** @var \Drupal\entity_counter\Entity\EntityCounterInterface $entity */
    $page = [];

    // @TODO: Add a summary views with a progress bar or chart by transaction type.
    $page['#entity_type'] = $entity_counter->getEntityTypeId();
    $page['#' . $page['#entity_type']] = $entity_counter;

    $page['sources'] = $this->entityFormBuilder->getForm($entity_counter, 'sources');

    return $page;
  }

  /**
   * Provides a page to display a single entity counter log.
   *
   * @param \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter
   *   The entity counter.
   *
   * @return array
   *   A render array as expected by drupal_render().
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function viewEntityCounterLog(EntityCounterInterface $entity_counter) {
    /** @var \Drupal\entity_counter\Entity\EntityCounterInterface $entity */
    $page = [];

    $page['#entity_type'] = $entity_counter->getEntityTypeId();
    $page['#' . $page['#entity_type']] = $entity_counter;

    /** @var \Drupal\entity_counter\CounterTransactionLogListBuilder $list */
    $list = new CounterTransactionLogListBuilder($this->entityTypeManager()->getDefinition('entity_counter_transaction'), $this->entityTypeManager()->getStorage('entity_counter_transaction'), $this->dateFormatter, $entity_counter);
    $page['list'] = $list->render();

    return $page;
  }

  /**
   * Calls a method on an entity counter and reloads the listing page.
   *
   * @param \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter
   *   The entity counter being acted upon.
   * @param string $op
   *   The operation to perform, e.g., 'enable' or 'disable'.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the collection page.
   */
  public function performOperation(EntityCounterInterface $entity_counter, $op) {
    $entity_counter->$op()->save();
    drupal_set_message($this->t('The entity counter settings has been updated.'));

    return $this->redirect('entity.entity_counter.collection');
  }

  /**
   * Route entity counter value callback.
   *
   * @param \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter
   *   An entity counter.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   An HTTP response in JSON format.
   */
  public function getCounterValue(EntityCounterInterface $entity_counter = NULL) {
    if (empty($entity_counter)) {
      throw new NotFoundHttpException();
    }
    elseif (!$entity_counter->access('view')) {
      throw new AccessDeniedHttpException();
    }

    return (new CacheableJsonResponse($entity_counter->getValue(TRUE)))->addCacheableDependency($entity_counter);
  }

  /**
   * Route entity counter title callback.
   *
   * @param \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter
   *   An entity counter.
   *
   * @return string
   *   The entity counter label as a render array.
   */
  public function getCounterTitle(EntityCounterInterface $entity_counter = NULL) {
    return ($entity_counter) ? $entity_counter->label() : $this->t('Entity counter');
  }

}
