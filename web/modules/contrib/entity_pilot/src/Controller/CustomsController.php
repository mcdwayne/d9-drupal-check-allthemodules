<?php

namespace Drupal\entity_pilot\Controller;

use Drupal\Component\Diff\Diff;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Diff\DiffFormatter;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\entity_pilot\ArrivalInterface;
use Drupal\entity_pilot\CustomsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines a controller for handling diff and previews.
 */
class CustomsController extends ControllerBase {

  /**
   * Customs interface.
   *
   * @var \Drupal\entity_pilot\CustomsInterface
   */
  protected $customs;

  /**
   * Diff formatter.
   *
   * @var \Drupal\Core\Diff\DiffFormatter
   */
  protected $diffFormatter;

  /**
   * Entity Manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new CustomsController.
   *
   * @param \Drupal\entity_pilot\CustomsInterface $customs
   *   Customs service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   Diff formatter service.
   * @param \Drupal\Core\Diff\DiffFormatter $diff_formatter
   *   Entity Manager service.
   */
  public function __construct(CustomsInterface $customs, EntityManagerInterface $entity_manager, DiffFormatter $diff_formatter) {
    $this->customs = $customs;
    $this->entityManager = $entity_manager;
    $this->diffFormatter = $diff_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_pilot.customs'),
      $container->get('entity.manager'),
      $container->get('diff.formatter')
    );
  }

  /**
   * Defines a diff callback for screening a passenger in an arrival.
   *
   * @param \Drupal\entity_pilot\ArrivalInterface $ep_arrival
   *   Arrival being screened.
   * @param string $passenger_id
   *   Id of passenger to diff.
   *
   * @return array
   *   Built content.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   When passenger id is invalid or no existing entity to diff.
   */
  public function diff(ArrivalInterface $ep_arrival, $passenger_id) {
    $passenger = $ep_arrival->getPassengers($passenger_id);
    if (!$passenger) {
      throw new NotFoundHttpException();
    }
    $this->customs->screen($ep_arrival);
    $unsaved_passenger = $this->customs->previewPassenger($passenger_id);
    $exists = $this->customs->exists($unsaved_passenger);
    if (!$exists) {
      throw new NotFoundHttpException();
    }
    // We encode them to YAML because the diff is easier to read than JSON.
    $diff = new Diff(explode("\n", Yaml::encode($exists->toArray())), explode("\n", Yaml::encode($unsaved_passenger->toArray())));
    $this->diffFormatter->show_header = FALSE;

    $build = [];

    $build['#title'] = $this->t('View changes');
    // Add the CSS for the inline diff.
    $build['#attached']['library'][] = 'system/diff';

    $build['diff'] = [
      '#type' => 'table',
      '#header' => [
        ['data' => $this->t('Existing'), 'colspan' => '2'],
        ['data' => $this->t('Incoming'), 'colspan' => '2'],
      ],
      '#rows' => $this->diffFormatter->format($diff),
    ];

    $build['back'] = [
      '#type' => 'link',
      '#attributes' => [
        'class' => [
          'dialog-cancel',
        ],
      ],
      '#title' => "Back to approval form.",
      '#route_name' => 'entity_pilot.arrival_approve',
      '#route_parameters' => [
        'ep_arrival' => $ep_arrival->id(),
      ],
    ];

    return $build;
  }

  /**
   * Defines a preview callback for screening a passenger in an arrival.
   *
   * @param \Drupal\entity_pilot\ArrivalInterface $ep_arrival
   *   Arrival being screened.
   * @param string $passenger_id
   *   Id of passenger to preview.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *
   * @return array
   *   Rendered view of entity.
   */
  public function preview(ArrivalInterface $ep_arrival, $passenger_id) {
    $this->customs->screen($ep_arrival, FALSE);
    try {
      $unsaved_passenger = $this->customs->previewPassenger($passenger_id);
    }
    catch (\InvalidArgumentException $e) {
      throw new NotFoundHttpException();
    }
    $unsaved_passenger->in_preview = TRUE;
    try {
      $build = $this->entityManager->getViewBuilder($unsaved_passenger->getEntityTypeId())->view($unsaved_passenger, 'entity_pilot_preview');
    }
    catch (EntityMalformedException $e) {
      $build = [
        '#type' => 'markup',
        '#markup' => $this->t('This entity cannot be previewed without being saved first.'),
      ];
    }
    $build['#title'] = $this->t('Preview Item');
    return $build;
  }

  /**
   * Wraps drupal_get_path().
   */
  protected function getPath($type, $name) {
    return drupal_get_path($type, $name);
  }

}
