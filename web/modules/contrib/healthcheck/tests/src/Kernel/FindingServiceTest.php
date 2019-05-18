<?php

namespace Drupal\Tests\healthcheck\Kernel;

use Drupal\healthcheck\Finding\FindingStatus;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the FindingService
 *
 * @group healthcheck
 */
class FindingServiceTest extends KernelTestBase {

  /**
   * The Finding service.
   *
   * @var \Drupal\healthcheck\FindingServiceInterface
   */
  protected $findingService;

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  public static $modules = [
    'healthcheck',
    'healthcheck_findings_test',
    'healthcheck_findingsrv_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('checkconfig');
    $this->installConfig(['healthcheck']);

    $this->findingService = \Drupal::service('healthcheck.finding');
  }

  /**
   * Tests if no YAML description of the finding is found.
   */
  public function testYamlNotFound() {
    $label = $this->findingService->getLabel('all_findings.finding_critical', FindingStatus::CRITICAL);
    $this->assertFalse($label);

    $message = $this->findingService->getMessage('all_findings.finding_critical', FindingStatus::CRITICAL);
    $this->assertFalse($message);
  }

  /**
   * Tests loading a Critical finding's YAML description.
   */
  public function testYamlCritical() {
    $label = $this->findingService->getLabel('all_yaml_findings.finding_critical', FindingStatus::CRITICAL, [
      'status' => FindingStatus::CRITICAL,
    ]);
    $this->assertTrue($label == 'YAML finding status critical', $label);

    $message = $this->findingService->getMessage('all_yaml_findings.finding_critical', FindingStatus::CRITICAL, [
      'status' => FindingStatus::CRITICAL,
    ]);
    $this->assertTrue($message == 'The status was 20 expected 20' . PHP_EOL, $message);
  }

  /**
   * Tests loading an Action Requested finding's YAML description.
   */
  public function testYamlActionRequested() {
    $label = $this->findingService->getLabel('all_yaml_findings.finding_action_requested', FindingStatus::ACTION_REQUESTED, [
      'status' => FindingStatus::ACTION_REQUESTED,
    ]);
    $this->assertTrue($label == 'YAML finding status action requested', $label);

    $message = $this->findingService->getMessage('all_yaml_findings.finding_action_requested', FindingStatus::ACTION_REQUESTED, [
      'status' => FindingStatus::ACTION_REQUESTED,
    ]);
    $this->assertTrue($message == 'The status was 15 expected 15' . PHP_EOL, $message);
  }

  /**
   * Tests loading a Needs Review finding's YAML description.
   */
  public function testYamlNeedsReview() {
    $label = $this->findingService->getLabel('all_yaml_findings.finding_needs_review', FindingStatus::NEEDS_REVIEW, [
      'status' => FindingStatus::NEEDS_REVIEW,
    ]);
    $this->assertTrue($label == 'YAML finding status needs review', $label);

    $message = $this->findingService->getMessage('all_yaml_findings.finding_needs_review', FindingStatus::NEEDS_REVIEW, [
      'status' => FindingStatus::NEEDS_REVIEW,
    ]);
    $this->assertTrue($message == 'The status was 10 expected 10' . PHP_EOL, $message);
  }

  /**
   * Tests loading a No Action Required finding's YAML description.
   */
  public function testYamlNoActionRequired() {
    $label = $this->findingService->getLabel('all_yaml_findings.finding_no_action_required', FindingStatus::NO_ACTION_REQUIRED, [
      'status' => FindingStatus::NO_ACTION_REQUIRED,
    ]);
    $this->assertTrue($label == 'YAML finding status no action required', $label);

    $message = $this->findingService->getMessage('all_yaml_findings.finding_no_action_required', FindingStatus::NO_ACTION_REQUIRED, [
      'status' => FindingStatus::NO_ACTION_REQUIRED,
    ]);
    $this->assertTrue($message == 'The status was 5 expected 5' . PHP_EOL, $message);
  }

  /**
   * Tests loading a Not Performed finding's YAML description.
   */
  public function testYamlNotPerformed() {
    $label = $this->findingService->getLabel('all_yaml_findings.finding_not_performed', FindingStatus::NOT_PERFORMED, [
      'status' => FindingStatus::NOT_PERFORMED,
    ]);
    $this->assertTrue($label == 'YAML finding status not performed', $label);

    $message = $this->findingService->getMessage('all_yaml_findings.finding_not_performed', FindingStatus::NOT_PERFORMED, [
      'status' => FindingStatus::NOT_PERFORMED,
    ]);
    $this->assertTrue($message == 'The status was 0 expected 0' . PHP_EOL, $message);
  }
}
