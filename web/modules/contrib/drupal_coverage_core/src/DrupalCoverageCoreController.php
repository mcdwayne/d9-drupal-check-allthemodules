<?php

namespace Drupal\drupal_coverage_core;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for drupal coverage.
 */
class DrupalCoverageCoreController extends ControllerBase {

  /**
   * The analysis manager.
   *
   * @var AnalysisManager
   */
  protected $analysisManager;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a controller for Drupal Coverage Core.
   *
   * @param \Drupal\drupal_coverage_core\AnalysisManager $analysis_manager
   *   The AnalysisManager object.
   */
  public function __construct(AnalysisManager $analysis_manager) {
    $this->analysisManager = $analysis_manager;
    $this->logger = \Drupal::logger('drupal_coverage_core');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('drupal_coverage_core.analysis_manager')
    );
  }

  /**
   * Checks with Travis if any of the builds has been completed.
   *
   * @return array
   *   The rendered list of page elements.
   */
  public function check() {
    $generator = new Generator(\Drupal::service('drupal_coverage_core.travis_client'));
    $rows = [];
    // @todo Use AnalysisManager & AnalysisManagerStorage ?
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'analysis')
      ->condition('field_build_status', Generator::BUILD_BUILDING)
      ->execute();

    foreach ($nids as $nid) {
      $analysis = Node::load($nid);
      $build_data = new BuildData();

      try {
        $build_data->setBuildData($generator->getBuildData($analysis->field_build_id->getString()));
      }
      catch (\Exception $e) {
        drupal_set_message(t("An error occured. Please try again later."), 'error');
        watchdog_exception('drupal_coverage_core', $e);
      }

      if ($build_data->getBuildStatus() != $analysis->field_build_status->getString()) {
        $this->analysisManager->updateNode($analysis, $build_data);
        $analysis->save();
        $rows[]['data'] = [
          $this->analysisManager->getTitle($analysis),
          $this->t('Build status changed to %build_status' , array('%build_status' => $build_data->getBuildStatus())),
        ];

        $this->logger->notice('Changed build status from analysis %nid to %build_status', [
          '%nid' => $analysis->id(),
          '%build_status' => $build_data->getBuildStatus(),
        ]);
      }
    }

    if (empty($rows)) {
      $this->logger->notice('No build status changes detected.');
    }

    return [
      '#type' => 'table',
      '#rows' => $rows,
      '#header' => [$this->t('Analysis'), $this->t('Status')],
      '#empty' => t('No build status changes detected.'),
    ];
  }

}
