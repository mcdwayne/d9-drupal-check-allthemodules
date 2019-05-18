<?php

namespace Drupal\Tests\swiftmailer\Functional;

use Drupal;
use Drupal\swiftmailer_test\SwiftMailerDrupalStateLogger;
use Drupal\Tests\BrowserTestBase;

/**
 * @group swiftmailer
 */
class SwiftMailerAlterTest extends BrowserTestBase {

  public static $modules = ['swiftmailer_test', 'swiftmailer', 'mailsystem'];

  /**
   * @var \Drupal\swiftmailer_test\SwiftMailerDrupalStateLogger
   */
  protected $logger = NULL;

  protected function setUp() {
    parent::setUp();
    Drupal::configFactory()
      ->getEditable('mailsystem.settings')
      ->set('modules.swiftmailer_test.none', [
        'formatter' => 'swiftmailer',
        'sender' => 'swiftmailer',
      ])
      ->save();
    Drupal::configFactory()
      ->getEditable('swiftmailer.transport')
      ->set('transport', 'null')
      ->save();
    $this->logger = new SwiftMailerDrupalStateLogger();
  }

  public function testAlter() {
    \Drupal::state()->set('swiftmailer_test_swiftmailer_alter_1', TRUE);
    \Drupal::service('plugin.manager.mail')->mail('swiftmailer_test', 'test_1', 'test@example.com', \Drupal::languageManager()->getDefaultLanguage()->getId());
    $this->assertEquals('Replace text in swiftmailer_test_swiftmailer_alter', $this->logger->dump()[0]['body']);
  }

  public function testTemplatePreprocess() {
    \Drupal::configFactory()
      ->getEditable('swiftmailer.message')
      ->set('respect_format', FALSE)
      ->save();

    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('default', 'swiftmailer_test_theme')
      ->save();

    \Drupal::configFactory()
      ->getEditable('mailsystem.settings')
      ->set('theme', 'default')
      ->save();

    \Drupal::service('theme_installer')->install(['swiftmailer_test_theme']);

    $params = [
      'format' => SWIFTMAILER_FORMAT_HTML,
    ];

    \Drupal::service('plugin.manager.mail')->mail('swiftmailer_test', 'test-2', 'test@example.com', \Drupal::languageManager()->getDefaultLanguage()->getId(), $params);
    $this->assertContains('string_from_template', (string) $this->logger->dump()[0]['body']);
    $this->assertContains('variable_from_preprocess', (string) $this->logger->dump()[0]['body']);
  }

  /**
   * Create plain text version from body.
   */
  public function testGeneratePlainTextVersion() {
    $plugin = Drupal\swiftmailer\Plugin\Mail\SwiftMailer::create(\Drupal::getContainer(), [], NULL, NULL);

    $message = [
      'module' => 'swiftmailer_test',
      'key' => 'swiftmailer_test_1',
      'headers' => [
        'Content-Type' => SWIFTMAILER_FORMAT_HTML,
      ],
      'params' => [
        'convert' => TRUE,
      ],
      'subject' => 'Subject',
      'body' => [
        Drupal\Core\Render\Markup::create('<strong>Hello World</strong>')
      ]
    ];

    $message = $plugin->format($message);
    $this->assertContains('<strong>Hello World</strong>', (string) $message['body']);
    $this->assertContains('HELLO WORLD', $message['plain']);
  }

  /**
   * Preserve original plain text, do not generate it from body.
   */
  public function testKeepOriginalPlainTextVersion() {
    $plugin = Drupal\swiftmailer\Plugin\Mail\SwiftMailer::create(\Drupal::getContainer(), [], NULL, NULL);

    $message = [
      'module' => 'swiftmailer_test',
      'key' => 'swiftmailer_test_1',
      'headers' => [
        'Content-Type' => SWIFTMAILER_FORMAT_HTML,
      ],
      'params' => [
        'convert' => FALSE,
      ],
      'subject' => 'Subject',
      'plain' => 'Original Plain Text Version',
      'body' => [
        Drupal\Core\Render\Markup::create('<strong>Hello World</strong>')
      ]
    ];

    $message = $plugin->format($message);
    $this->assertContains('<strong>Hello World</strong>', (string) $message['body']);
    $this->assertContains('Original Plain Text Version', $message['plain']);
  }

  public function testPlainTextConfigurationSetting() {
    \Drupal::configFactory()
      ->getEditable('swiftmailer.message')
      ->set('convert_mode', TRUE)
      ->save();

    $plugin = Drupal\swiftmailer\Plugin\Mail\SwiftMailer::create(\Drupal::getContainer(), [], NULL, NULL);

    // Empty plain text, generate from html.
    $message = [
      'module' => 'swiftmailer_test',
      'key' => 'swiftmailer_test_1',
      'headers' => [
        'Content-Type' => SWIFTMAILER_FORMAT_HTML,
      ],
      'subject' => 'Subject',
      'body' => [
        Drupal\Core\Render\Markup::create('<strong>Hello World</strong>')
      ]
    ];

    $message = $plugin->format($message);
    $this->assertContains('<strong>Hello World</strong>', (string) $message['body']);
    $this->assertContains('HELLO WORLD', $message['plain']);

    // Keep original plain text version.
    $message = [
      'module' => 'swiftmailer_test',
      'key' => 'swiftmailer_test_1',
      'headers' => [
        'Content-Type' => SWIFTMAILER_FORMAT_HTML,
      ],
      'subject' => 'Subject',
      'plain' => 'Original Plain Text Version',
      'body' => [
        Drupal\Core\Render\Markup::create('<strong>Hello World</strong>')
      ]
    ];

    $message = $plugin->format($message);
    $this->assertContains('<strong>Hello World</strong>', (string) $message['body']);
    $this->assertContains('Original Plain Text Version', $message['plain']);
  }


}
