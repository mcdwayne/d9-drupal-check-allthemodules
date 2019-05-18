<?php

namespace Drupal\job\Tests;

use Drupal\job\Entity\Job;
use Drupal\simpletest\WebTestBase;

/**
 * Provides helper functions.
 */
abstract class JobTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['job'];

  /**
   * A job.
   *
   * @var \Drupal\job\JobInterface
   */
  protected $job;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->job = $this->createJob();
  }

  /**
   * Creates a job based on default settings.
   */
  protected function createJob(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'title' => $this->randomMachineName(8),
      'number' => $this->randomMachineName(8),
    ];
    $entity = Job::create($settings);
    $entity->save();

    return $entity;
  }

}
