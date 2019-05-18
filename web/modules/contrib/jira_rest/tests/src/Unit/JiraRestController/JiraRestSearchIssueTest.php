<?php

namespace Drupal\Tests\jira_rest\Unit\JiraRestController;

use Drupal\jira_rest\Controller\JiraRestController;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\jira_rest\Controller\JiraRestController
 * @group JiraRest
 */
class JiraRestSearchIssueTest extends UnitTestCase {

  /**
   * Jira rest API Controller.
   *
   * @var \Drupal\jira_rest\Controller\JiraRestController
   */
  protected $jiraRestController;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->jiraRestController = $this->getMockBuilder('Drupal\jira_rest\Controller\JiraRestController')
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * @covers ::jira_rest_searchissue
   */
  public function testSearchIssue() {

    $jr_service = \Drupal::service('jira_rest_wrapper_service');
    $result = $jr_service->getDemoValue();
    $this->assertEquals($result, 'just for testing');
  }

}
