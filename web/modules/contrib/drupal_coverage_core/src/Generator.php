<?php

namespace Drupal\drupal_coverage_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\drupal_coverage_core\Client\TravisClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a generator which will generate input for TravisClient.
 */
class Generator {

  /**
   * The HTTP client.
   *
   * @var TravisClient
   */
  protected $client;

  /**
   * Prefix used for creating new branches in GitHub.
   *
   * @var string
   */
  protected $branchPrefix = "reports";

  /**
   * The RawGit base URL.
   *
   * @var string
   */
  protected $url = "https://rawgit.com/legovaer/dc-travis/";

  const BUILD_BUILDING = 0;
  const BUILD_SUCCESSFUL = 1;
  const BUILD_FAILED = 2;

  /**
   * Constructs a Generator.
   *
   * @param TravisClient $travis_client
   *   The travis client.
   */
  public function __construct(TravisClient $travis_client) {
    $this->client = $travis_client;
  }

  /**
   * Start the Travis CI build process.
   *
   * @param BuildData $build_data
   *   The data that needs to be sent to Travis CI.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response object coming from the TravisCI Client.
   */
  public function build(BuildData $build_data) {
    return $this->client->build($this->generateBody($build_data));
  }

  /**
   * Creates a prefix for the coverage URL.
   *
   * @param BuildData $build_data
   *   The build data.
   *
   * @return string
   *   The URL prefix where the coverage analysis can be found.
   */
  protected function getCoverageUrlPrefix(BuildData $build_data) {
    $module_name = ModuleManager::cleanModuleName(
      $build_data->getModule()->title->getString()
    );
    $build_number = $build_data->getBuildData()->number;
    return $this->url . $this->branchPrefix . "/$module_name/$build_number/";
  }

  /**
   * Creates the URL where the analysis can be found.
   *
   * @param BuildData $build_data
   *   The build data.
   *
   * @return string
   *   The absolute URL where the analysis can be found.
   */
  public function getCoverageUrl(BuildData $build_data) {
    return $this->getCoverageLink($build_data, "index.html");
  }

  /**
   * Creates the URL where the badge can be found.
   *
   * @param BuildData $build_data
   *   The build data.
   *
   * @return string
   *   The absolute URL where the badge can be found.
   */
  public function getCoverageBadge(BuildData $build_data) {
    return $this->getCoverageLink($build_data, "badge.svg");
  }

  /**
   * Creates the base link where the coverage can be found.
   *
   * @param BuildData $build_data
   *   The build data.
   * @param string $path
   *   The path that should be used for generating the URL.
   *
   * @return string
   *   The base link of where the coverage information can be found.
   */
  protected function getCoverageLink(BuildData $build_data, $path) {
    return $this->getCoverageUrlPrefix($build_data) . $path;
  }

  /**
   * Creates a name of a branch which will be used for storing the analysis.
   *
   * @param EntityInterface $module
   *   The module.
   *
   * @return string
   *   The branch that will be used for this module & build.
   */
  protected function getDestinationBranch(EntityInterface $module) {
    return $this->branchPrefix . "/" . ModuleManager::cleanModuleName($module->title->getString());
  }

  /**
   * Generates the body field which will be sent to Travis CI.
   *
   * @param BuildData $build_data
   *   The build data of of the analysis.
   */
  protected function generateBody(BuildData $build_data) {
    $module = $build_data->getModule();

    $body = [
      "request" => [
        "branch" => "test",
        "config" => [
          "before_install" => [
            'export DRUPAL_TI_MODULE_NAME="' . ModuleManager::cleanModuleName($module->title->getString(), '_') . '"',
            'export DRUPAL_TI_SIMPLETEST_GROUP="' . $module->field_testcase->getString() . '"',
            'export DRUPAL_TI_DESTINATION_BRANCH="' . $this->getDestinationBranch($module) . '"',
            'composer self-update',
            'mkdir -p "$HOME/.composer/vendor/bin"',
            'cd $HOME',
            'git clone --branch travis-add-coverage-support https://github.com/legovaer/drupal_ti',
            'cd drupal_ti/',
            'composer install',
            'ln -sf $HOME/drupal_ti/drupal-ti "$HOME/.composer/vendor/bin"',
          ],
        ],
      ],
    ];

    if ($build_data->getModuleType() == ModuleManager::TYPE_CONTRIB) {
      $body['request']['config']['before_install'][] = 'git clone --branch ' . $build_data->getBranch() . ' https://git.drupal.org/project/$DRUPAL_TI_MODULE_NAME.git $TRAVIS_BUILD_DIR/$DRUPAL_TI_MODULE_NAME';
      $body['request']['config']['before_install'][] = 'export DRUPAL_TI_ANALYSE_CORE=0';
    }
    else {
      $body['request']['config']['before_install'][] = 'export DRUPAL_TI_ANALYSE_CORE=1';
    }

    $body['request']['config']['before_install'][] = 'drupal-ti before_install';

    return $body;
  }

  /**
   * Get the build data from Travis for a given ID.
   *
   * @param string|null $build_id
   *   The id of the build.
   *
   * @return mixed|object
   *   When a $build_id is given, the details of that build. Otherwise
   *   the build details of the last build.
   */
  public function getBuildData($build_id = NULL) {
    if ($build_id) {
      $build = $this->client->getBuild($build_id);
    }
    else {
      $build = $this->client->getLastBuild();
    }
    return $build;
  }

  /**
   * Determines which call-out class should be used.
   *
   * @param int $build_status
   *   The current status of the build.
   *
   * @return string
   *   The CSS classname which should be used.
   */
  public static function getCalloutClass($build_status) {
    switch ($build_status) {
      case Generator::BUILD_BUILDING:
      default:
        $callout_class = "bs-callout-info";
        break;

      case Generator::BUILD_FAILED:
        $callout_class = "bs-callout-failed";
        break;

      case Generator::BUILD_SUCCESSFUL:
        $callout_class = "bs-callout-success";
    }

    return $callout_class;
  }

}
