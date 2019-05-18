<?php

namespace Drupal\comment_approver;

use Drupal\comment_approver\Plugin\CommentApproverManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\comment\CommentInterface;

/**
 * Class CommentTester.
 */
class CommentTester implements CommentTesterInterface {

  /**
   * Drupal\comment_approver\Plugin\CommentApproverManager definition.
   *
   * @var \Drupal\comment_approver\Plugin\CommentApproverManager
   */
  protected $pluginManagerCommentApprover;
  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * CommentTester configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a new CommentTester object.
   */
  public function __construct(CommentApproverManager $plugin_manager_comment_approver, ConfigFactoryInterface $config_factory) {
    $this->pluginManagerCommentApprover = $plugin_manager_comment_approver;
    $this->configFactory = $config_factory;
  }

  /**
   * Returns all the tests selected by admin for comment approval.
   *
   * @return array
   */
  public function getTests() {
    return $this->getConfig('select_tests_to_perform');
  }

  /**
   * Returns current mode of operation of CommentAprrover.
   */
  public function getMode() {
    return $this->getConfig('mode');
  }

  /**
   * Returns a crested instance for a test.
   */
  public function getTestInstance($testname) {
    $test_config = $this->getConfig($testname);
    if(!$test_config){
      $test_config = [];
    }
    return $this->pluginManagerCommentApprover->createInstance($testname,$test_config);
  }

  /**
   * Returns the selected config.
   */
  public function getConfig(string $configName) {
    if (!isset($this->config)) {
      $this->config = $this->configFactory->get('comment_approver.commentapproversettings');
    }
    return $this->config->get($configName);
  }

  /**
   * Return true if a comment passes all selected tests by admin.
   *
   * @return bool
   */
  public function test(CommentInterface $comment) {
    $tests = $this->getTests();
    $approved = TRUE;
    foreach ($tests as $testname) {
      if ($testname) {
        if (!$this->runTest($testname, $comment)) {
          // If a test fails break the loop.
          $approved = FALSE;
          break;
        }
      }
    }
    return $approved;
  }

  /**
   * Runs an individual test and returns true or false.
   *
   * @return bool
   */
  public function runTest(string $testname, CommentInterface $comment) {
    $test = $this->getTestInstance($testname);
    return $test->isCommentFine($comment);
  }

}
