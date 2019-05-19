<?php

/**
 * @file
 * Contains \Drupal\smartling\Tests\PushCallbackControllerTest.
 */

namespace Drupal\smartling\Tests;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\smartling\Entity\SmartlingSubmission;

/**
 * Class PushCallbackControllerTest
 * @package Drupal\smartling\Tests
 * @group smartling
 */
class PushCallbackControllerTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('smartling', 'node', 'field', 'dblog', 'block', 'content_translation');

  /**
   * A user with some relevant administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user without any permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * @var \Drupal\smartling\Entity\SmartlingSubmission
   */
  protected $submission;

  /**
   * @var string
   */
  protected $cron_key;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create users with specific permissions.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
      'access site reports',
      'administer users',
      'administer smartling',
    ]);
    $this->webUser = $this->drupalCreateUser([]);
    $this->node = \Drupal::entityTypeManager()->getStorage('node')
      ->create([
        'title' => $this->randomString(),
        'body' => [
          'value' => $this->randomString(),
        ],
        'type' => 'page',
      ]);

    $this->submission = SmartlingSubmission::getFromDrupalEntity($this->node, 'ru');
    $this->submission->save();
    $this->cron_key = Crypt::randomBytesBase64(55);
    \Drupal::state()->set('system.cron_key', $this->cron_key);
  }

  public function testCallbackController() {
    $curl_options = [
      CURLOPT_HTTPGET => TRUE,
      CURLOPT_POST => FALSE,
      CURLOPT_URL => Url::fromRoute('smartling.push_callback', ['cron_key' => $this->randomString()])->setAbsolute()->toString(),
      CURLOPT_NOBODY => FALSE,
    ];

    $this->curlExec($curl_options);
    $this->assertResponse(403);

    $curl_options[CURLOPT_URL] = Url::fromRoute('smartling.push_callback', ['cron_key' => $this->cron_key], ['query' => ['fileUri' => 'file-uri', 'locale' => 'ru']])->setAbsolute()->toString();
    $this->curlExec($curl_options);
    $this->assertResponse(404, 'No data');

    $curl_options[CURLOPT_URL] = Url::fromRoute('smartling.push_callback', ['cron_key' => $this->cron_key], ['query' => ['fileUri' => 'file-uri', 'locale' => 'ru']])->setAbsolute()->toString();
    $this->curlExec($curl_options);
    $this->assertResponse(404, 'No locale mapping');
    // @todo finish the test with positive result.
  }

  /**
   * Gets the cURL options to create an entity with POST.
   *
   * @return array
   *   The array of cURL options.
   */
  protected function getCurlOptions() {
    return array(
      CURLOPT_HTTPGET => TRUE,
      CURLOPT_POST => FALSE,
      CURLOPT_URL => Url::fromRoute('smartling.push_callback', [], ['query' => ['fileUri' => 'file-uri', 'locale' => 'ru']])->setAbsolute()->toString(),
      CURLOPT_NOBODY => FALSE,
    );
  }

}
