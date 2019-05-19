<?php

/**
 * @file
 * Contains \Drupal\smartling\Controller\SmartlingController.
 */

namespace Drupal\smartling\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\smartling\Entity\SmartlingSubmission;
use Drupal\smartling\SmartlingSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the smartling entities.
 *
 * @see \Drupal\comment\Entity\Comment.
 */
class SmartlingController extends ControllerBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityManager;

  /**
   * Constructs a SmartlingController object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * Returns entity from request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity to translate.
   */
  protected function getEntityFromRequest(Request $request) {
    $entity_type_id = $request->get('entity_type_id');
    return $request->get($entity_type_id);
  }

  /**
   * Displays submissions for entity.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return array
   *   A renderable array.
   *
   * @deprecated Should be removed use \Drupal\smartling\Form\EntitySubmissionsForm
   */
  public function entityOverview(Request $request) {
    $entity = $this->getEntityFromRequest($request);
    // @todo Support string IDs.
    $submissions = SmartlingSubmission::loadMultipleByConditions([
      'entity_id' => $entity->id(),
    ]);
    $items = [];
    /** @var \Drupal\smartling\SmartlingSubmissionInterface $submission */
    foreach ($submissions as $submission) {
      $items[] = [
        '#type' => 'link',
        '#title' => $submission->label(),
        '#url' => $submission->urlInfo(),
        '#attributes' => [
          'class' => ['use-ajax', 'button', 'button--small'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 200,
          ]),
        ],
      ];
    }

    $build['table'] = [
      '#theme' => 'item_list__submissions',
      '#title' => $this->t('List of submissions'),
      '#items' => $items,
      '#empty' => [
        'help' => [
          '#markup' => $this->t('No submissions yet.'),
        ],
        'form' => \Drupal::formBuilder()
          ->getForm('\Drupal\smartling\Form\EntitySubmissionsForm', $entity),
      ],
    ];
    return $build;
  }

  /**
   * The _title_callback for the page that renders the submissions.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return string The page title.
   *   The page title.
   */
  public function submissionsTitle(Request $request) {
    return $this->t('Submissions for @label', [
      '@label' => $this->entityManager()
        ->getTranslationFromContext($this->getEntityFromRequest($request))
        ->label(),
    ]);
  }

  /**
   * The _title_callback for the page that renders the submissions.
   *
   * @param \Drupal\smartling\SmartlingSubmissionInterface $smartling_submission
   *   The submission entity.
   *
   * @return string
   *   The page title.
   *
   */
  public function getTitle(SmartlingSubmissionInterface $smartling_submission) {
    return $smartling_submission->label();
  }

  /**
   * Displays submission information and actions.
   *
   * @param \Drupal\smartling\SmartlingSubmissionInterface $smartling_submission
   *   The submission entity.
   *
   * @return array
   *   Renderable array.
   */
  public function getDisplay(SmartlingSubmissionInterface $smartling_submission) {
    // @todo Implement in view builder.
    return [
      'progress' => [
        '#type' => 'item',
        '#title' => $this->t('Translation progress: @progress', [
          '@progress' => $smartling_submission->progress->value,
        ]),
      ],
      'actions' => ['#markup' => '@todo Operations with submission'],
    ];
  }

}
