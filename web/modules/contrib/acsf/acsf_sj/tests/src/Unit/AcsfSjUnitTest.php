<?php

namespace Drupal\Tests\acsf_sj\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Tests\acsf_sj\Unit\TestSJApiClient
 *
 * @group acsf_sj
 */
class TestAcsfSjUnitTest extends UnitTestCase {

  /**
   * The client under test.
   *
   * @var \Drupal\Tests\acsf_sj\Unit\TestSjApiClient
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->client = new TestSjApiClient();
  }

  /**
   * @covers ::addJob
   * @dataProvider providerTestAddJob
   */
  public function testAddJob($command, $reason, $timestamp, $domain, $timeout, $drush_executable, $drush_options) {
    $this->client->addJob($command, $reason, $timestamp, $domain, $timeout, $drush_options);

    $command_test = "sjadd --reason=$reason $timestamp $domain $command $drush_executable $drush_options";
    $this->assertSame($this->client->execTest, $command_test);
  }

  /**
   * Provide test cases for ::testAddJob().
   */
  public function providerTestAddJob() {
    return [
      ['cron', 'testrun', 0, 'test.com', 0, 'drush9', '-y'],
      ['cron', 'testrun', 1540934898, 'test.com', NULL, NULL, NULL],
    ];
  }

}
