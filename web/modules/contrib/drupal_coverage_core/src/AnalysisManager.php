<?php

namespace Drupal\drupal_coverage_core;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\drupal_coverage_core\Client\TravisClient;
use Drupal\drupal_coverage_core\Exception\UnableToDetermineBuildStatusException;
use Drupal\node\Entity\Node;

/**
 * @todo Dynamically gather the branches.
 * @todo Introduce queue
 */
class AnalysisManager {
  /**
   * The generator.
   *
   * @var Generator
   */
  protected $generator;

  /**
   * The data formatter.
   *
   * @var DateFormatter
   */
  protected $dateFormatter;

  /**
   * The Travis client.
   *
   * @var TravisClient;
   */
  protected $travisClient;

  /**
   * Constructs an AnalysisManager.
   *
   * @param DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param TravisClient $travis_client
   *   The http client used for interacting with TravisCI.
   * @param Generator $generator
   *   The generator.
   */
  public function __construct(DateFormatterInterface $date_formatter, TravisClient $travis_client, Generator $generator) {
    $this->generator = $generator;
    $this->dateFormatter = $date_formatter;
    $this->travisClient = $travis_client;
  }

  /**
   * Starts the build of an analysis.
   *
   * @param BuildData $build_data
   *   The data from Travis.
   *
   * @throws UnableToDetermineBuildStatusException
   *   In case when it's not possible to retrieve data from Travis.
   */
  public function startBuild(BuildData $build_data) {

    // Initiate the Travis Build.
    $this->generator->build($build_data);

    // Check if the new build data is available in Travis.
    try {
      $this->retrieveBuildData($build_data);
    }
    catch (UnableToDetermineBuildStatusException $e) {
      throw $e;
    }

    // Create an analysis node.
    $this->createAnalysisNode($build_data);

  }

  /**
   * List the analyses that are currently being build.
   *
   * @return array|int
   *   The list of analyses that are currently getting build.
   */
  public function getAnalysisInProgress() {
    return \Drupal::entityQuery('node')
      ->condition('type', 'analysis')
      ->condition('field_build_status', Generator::BUILD_BUILDING)
      ->execute();
  }

  /**
   * Check if a module is currently being build.
   *
   * @param EntityInterface $module
   *   The module that needs to be checked.
   * @param string $branch
   *   The branch that needs to be checked.
   *
   * @return bool
   *   TRUE if the combination of module and branch is getting build, FALSE
   *   if not.
   */
  public function isBeingBuild(EntityInterface $module, $branch) {
    $results = \Drupal::entityQuery('node')
      ->condition('type', 'analysis')
      ->condition('field_build_status', Generator::BUILD_BUILDING)
      ->condition('field_branch', $branch)
      ->condition('field_module', $module->id())
      ->execute();

    return count($results) > 0;
  }

  /**
   * Retrieves the build data from Travis.
   *
   * @param BuildData $build_data
   *   The build data which is coming from Travis.
   *
   * @return BuildData
   *   The updated data from Travis.
   *
   * @throws UnableToDetermineBuildStatusException
   *   In case when it's not possible to retrieve data from Travis.
   */
  private function retrieveBuildData(BuildData &$build_data) {
    $build_data->setBuildData($this->generator->getBuildData());
    $previous_build_nr = $build_data->getBuildData()->number;

    // Keep checking with Travis until our new build request has been added.
    $attempts = 0;
    while ($previous_build_nr == $build_data->getBuildData()->number && $attempts < TravisClient::MAX_ATTEMPTS) {
      $attempts++;
      $build_data->setBuildData($this->generator->getBuildData());
    }

    if ($attempts >= TravisClient::MAX_ATTEMPTS) {
      throw new UnableToDetermineBuildStatusException();
    }

    return $build_data;
  }

  /**
   * Creates a new analysis.
   *
   * @param BuildData $build_data
   *   The data coming from Travis.
   */
  private function createAnalysisNode(BuildData $build_data) {
    $title = t("@module_title @branch (#@build_id)", [
      '@module_title' => $build_data->getModule()->title->getString(),
      '@branch' => $build_data->getBranch(),
      '@build_id' => $build_data->getBuildData()->number,
    ]);

    $node_data = $this->getNodeData($build_data);
    $node_data['field_build_status'] = 0;
    $node_data['type'] = 'analysis';
    $node_data['title'] = $title;
    $node_data['field_module'] = $build_data->getModule()->id();
    Node::create($node_data)->save();
  }

  /**
   * Get a list of populated fields.
   *
   * @param BuildData $build_data
   *   The data coming from Travis.
   *
   * @return array
   *   An array containing populated fields which can be used for
   *   creating/updating an analysis entity.
   */
  public function getNodeData(BuildData $build_data) {
    return [
      'field_branch' => $build_data->getBranch(),
      'field_build_id' => $build_data->getBuildData()->id,
      'field_coverage_analysis' => $this->generator->getCoverageUrl($build_data),
      'field_coverage_badge' => $this->generator->getCoverageBadge($build_data),
      'field_started_at' => REQUEST_TIME,
      'field_finished_at' => $build_data->getBuildData()->finished_at,
      'field_commit_id' => $build_data->getBuildData()->commit,
      'field_commit_message' => $build_data->getBuildData()->message,
      'field_duration' => $build_data->getBuildData()->duration,
      'field_build_number' => $build_data->getBuildData()->number,
    ];
  }

  /**
   * Update an analysis with the new build data.
   *
   * @param EntityInterface $node
   *   The analyis.
   * @param BuildData $build_data
   *   The build data coming from Travis.
   */
  public function updateNode(EntityInterface &$node, BuildData $build_data) {
    $data = $build_data->getBuildData();
    $node->field_build_status = $this->updateBuildStatus($build_data);
    $node->field_build_status = $build_data->getBuildStatus();
    $node->field_started_at = strtotime($data->started_at);
    $node->field_finished_at = strtotime($data->finished_at);
    $node->field_commit_id = $data->commit;
    $node->field_commit_message = $data->message;
    $node->field_duration = $data->duration;
  }

  /**
   * Update the build status of the analysis.
   *
   * @param BuildData $build_data
   *   The build data coming from Travis.
   *
   * @return int|null
   *   0 in case when the build is stull running, and 1 if the build was
   *   successful. 2 when the build has failed.
   */
  public function updateBuildStatus(BuildData $build_data) {
    if ($build_data->getBuildStatus() == Generator::BUILD_SUCCESSFUL) {
      if ($build_data->getBuildData()->result === NULL) {
        return Generator::BUILD_FAILED;
      }
      if ($build_data->getBuildData()->result === 0) {
        return Generator::BUILD_SUCCESSFUL;
      }
    }
    else {
      return Generator::BUILD_BUILDING;
    }
  }

  /**
   * Get a formatted date of when the build of the analysis started.
   *
   * @param EntityInterface $node
   *   The analysis.
   *
   * @return \Drupal\Core\Datetime\FormattedDateDiff|\Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The formatted date of when the build of the analysis started.
   */
  public function getStartedAt(EntityInterface $node) {
    return $this->getDiff($node->field_started_at->getValue()[0]['value']);
  }

  /**
   * Get the formatted date of when the analysis was finished.
   *
   * @param EntityInterface $node
   *   The analysis.
   *
   * @return \Drupal\Core\Datetime\FormattedDateDiff|\Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The formatted date of when the analysis was finished.
   */
  public function getFinishedAt(EntityInterface $node) {
    if (count($node->field_finished_at->getValue()) > 0) {
      return $this->getDiff($node->field_finished_at->getValue()[0]['value']);
    }
  }

  /**
   * Get the duration of the build.
   *
   * @param EntityInterface $node
   *   The analysis.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The formatted duration of the build of the analysis.
   */
  public function getDuration(EntityInterface $node) {
    return $this->dateFormatter->formatInterval(
      $node->field_duration->getString()
    );
  }

  /**
   * Get the build number.
   *
   * @param EntityInterface $node
   *   The analysis.
   *
   * @return string
   *   The number of the build.
   */
  public function getNumber(EntityInterface $node) {
    return $node->field_build_number->getString();
  }

  /**
   * Get the difference in time between now and a timestamp.
   *
   * @param int $timestamp
   *   The timestamp.
   *
   * @return \Drupal\Core\Datetime\FormattedDateDiff|\Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   A formatted difference in time between now and the given timestamp.
   */
  protected function getDiff($timestamp) {
    return $this->dateFormatter->formatDiff(
      $timestamp,
      REQUEST_TIME,
      [
        'granularity' => 1,
        'return_as_object' => TRUE,
      ]
    );
  }

  /**
   * Get the current build status of an analysis.
   *
   * @param EntityInterface $node
   *   The analysis.
   *
   * @return string
   *   The build status of the analysis.
   */
  public function getBuildStatus(EntityInterface $node) {
    return $node->field_build_status->getString();
  }

  /**
   * Get the URL of the coverage analysis.
   *
   * @param EntityInterface $node
   *   The analysis.
   *
   * @return string
   *   The absolute URL to the coverage analysis.
   */
  public function getCoverageAnalysis(EntityInterface $node) {
    if ($this->getBuildStatus($node) == Generator::BUILD_SUCCESSFUL) {
      return $node->field_coverage_analysis->getString();
    }
    else {
      return "#";
    }
  }

  /**
   * Get the URL of the badge of the analysis.
   *
   * @param EntityInterface $node
   *   The analysis.
   *
   * @return mixed
   *   The absolute URL to the badge (image).
   */
  public function getCoverageBadge(EntityInterface $node) {
    if ($this->getBuildStatus($node) == Generator::BUILD_SUCCESSFUL) {
      return $node->field_coverage_badge->getString();
    }
  }

  /**
   * Get the branch which was used during the analysis.
   *
   * @param EntityInterface $node
   *   The analysis.
   *
   * @return static|string
   *   If the analysis has been finished, a link to the analysis otherwise just
   *   the title of the analysis in a string format.
   */
  public function getBranch(EntityInterface $node) {
    $branch = $node->field_branch->getString();

    if ($this->getBuildStatus($node) == Generator::BUILD_SUCCESSFUL) {
      return Link::fromTextAndUrl(
        $branch,
        Url::fromUri(
          $this->getCoverageAnalysis($node),
          ['absolute' => TRUE, 'attributes' => ['target' => 'blank']]
        )
      );
    }
    else {
      return $branch;
    }

  }

  /**
   * Get the title of an analysis.
   *
   * @param EntityInterface $node
   *   The analysis.
   *
   * @return static|string
   *   When the analysis has finished a link to the analysis, othwerise just the
   *   title of the analysis as a plain string.
   */
  public function getTitle(EntityInterface $node) {
    if ($this->getBuildStatus($node) == Generator::BUILD_SUCCESSFUL) {
      return Link::fromTextAndUrl(
        $node->title->getString(),
        Url::fromUri(
          $this->getCoverageAnalysis($node),
          ['absolute' => TRUE, 'attributes' => ['target' => 'blank']]
        )
      );
    }
    else {
      return $node->title->getString();
    }
  }

  /**
   * Checks if a given analysis is finished.
   *
   * @param EntityInterface $node
   *   The analysis.
   *
   * @return bool
   *   True if the build has completed, false if not.
   */
  public function isFinished(EntityInterface $node) {
    return !$this->getBuildStatus($node) == Generator::BUILD_BUILDING;
  }

}
