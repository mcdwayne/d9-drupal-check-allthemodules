<?php

namespace Drupal\Tests\whitelabel\Kernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\whitelabel\Traits\WhiteLabelCreationTrait;
use Drupal\whitelabel\PathProcessor\WhiteLabelPathProcessor;

/**
 * Tests to see if outbound urls contain the right token in the right place.
 *
 * @group whitelabel
 *
 * @todo: Should assert what happens if the user does not have sufficient permissions. (Mitigated by inbound processor detecting it).
 */
class WhiteLabelOutboundPathProcessingTest extends KernelTestBase {

  use WhiteLabelCreationTrait {
    createWhiteLabel as drupalCreateWhiteLabel;
  }
  use UserCreationTrait {
    createUser as drupalCreateUser;
  }

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'text',
    'options',
    'user',
    'file',
    'image',
    'whitelabel',
    'language',
  ];

  /**
   * Holds the generated white label throughout the different tests.
   *
   * @var \Drupal\whitelabel\WhiteLabelInterface
   */
  private $whiteLabel;

  /**
   * Holds the randomly generated token.
   *
   * @var string
   */
  private $token;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installConfig(['language', 'whitelabel']);
    $this->installEntitySchema('whitelabel');
    $this->installEntitySchema('user');

    ConfigurableLanguage::create(['id' => 'es'])->save();
    // This is somehow required...
    drupal_flush_all_caches();

    $user = $this->drupalCreateUser(['serve white label pages']);
    $this->setCurrentUser($user);

    $this->token = $this->randomMachineName(16);
    $this->whiteLabel = $this->createWhiteLabel(['token' => $this->token]);

    $this->setCurrentWhiteLabel($this->whiteLabel);
  }

  /**
   * Test white label URL creation.
   */
  public function testOutboundUrls() {
    // Obtain white label modes and test them.
    $modes = \Drupal::service('whitelabel.path_processor')->getModes();

    $expected_patterns = [
      WhiteLabelPathProcessor::CONFIG_QUERY_PARAMETER => "http://localhost/?whitelabel={$this->token}",
      WhiteLabelPathProcessor::CONFIG_PATH_PREFIX => "http://localhost/{$this->token}",
      WhiteLabelPathProcessor::CONFIG_DOMAIN => "http://{$this->token}.localhost/",
    ];

    foreach ($modes as $mode => $description) {
      // Configure the white label mode.
      \Drupal::configFactory()->getEditable('whitelabel.settings')
        ->set('mode', $mode)
        ->save();
      // Build the URL.
      $url = Url::fromRoute('<front>')->setAbsolute();
      // Check if the white label is in the URL in the expected pattern.
      $this->assertContains($expected_patterns[$mode], $url->toString());
    }
  }

  /**
   * Test white label URL creation with the language module enabled.
   */
  public function testOutboundUrlsLanguage() {
    $this->config('language.negotiation')
      ->set('url.prefixes', ['en' => 'en', 'es' => 'es'])
      ->save();

    // Obtain white label modes and test them.
    $modes = \Drupal::service('whitelabel.path_processor')->getModes();

    $expected_patterns = [
      WhiteLabelPathProcessor::CONFIG_QUERY_PARAMETER => "http://localhost/en?whitelabel={$this->token}",
      WhiteLabelPathProcessor::CONFIG_PATH_PREFIX => "http://localhost/{$this->token}/en",
      WhiteLabelPathProcessor::CONFIG_DOMAIN => "http://{$this->token}.localhost/en",
    ];

    foreach ($modes as $mode => $description) {
      // Configure the white label mode.
      \Drupal::configFactory()->getEditable('whitelabel.settings')
        ->set('mode', $mode)
        ->save();
      // Build the URL.
      $url = Url::fromRoute('<front>')->setAbsolute();
      // Check if the white label is in the URL in the expected pattern.
      $this->assertEquals($expected_patterns[$mode], $url->toString());
    }
  }

}
