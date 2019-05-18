<?php

namespace Drupal\Tests\sendwithus\Functional;

use Drupal\sendwithus\Entity\Template;
use Drupal\Tests\BrowserTestBase;
use sendwithus\API;

/**
 * Tests SettingsForm.
 *
 * @coversDefaultClass \Drupal\sendwithus\Plugin\Mail\SendwithusMail
 * @group sendwithus
 */
class MailTest extends BrowserTestBase {

  public static $modules = ['sendwithus', 'key', 'simpletest'];

  /**
   * @covers ::__construct
   * @covers ::create
   * @covers ::format
   * @covers ::mail
   */
  public function testMail() {
    // Override the default adapter class.
    $adapter = new class () extends API {

      /**
       * {@inheritdoc}
       */
      public function __construct($api_key = '', array $options = []) {
        $options = [
          'API_HOST' => 'localhost',
        ];
        parent::__construct('1234', $options);
      }

      /**
       * {@inheritdoc}
       */
      public function api_request($endpoint, $request = "POST", $payload = NULL, $params = NULL) {
        // Always return success.
        return (object) [
          'success' => TRUE,
        ];
      }

    };

    /** @var \Drupal\Core\Mail\MailManagerInterface $mailManager */
    $mailManager = \Drupal::service('plugin.manager.mail');
    $this->config('system.mail')->set('interface.default', 'sendwithus_mail')->save();

    $response = $mailManager->mail('simpletest', 'from_test', 'from_test@example.com', 'en', []);

    // This should fail for missing template.
    $this->assertFalse($response['result']);

    $entity = Template::create([
      'id' => 'test_template',
      'key' => 'from_test',
      'module' => 'simpletest',
    ]);
    $entity->save();

    $recipients = implode(',', [
      'Test Mail <test@example.com>',
      'Test2 Mail <test2@example.com>',
      'test3@example.com',
      'test4@example.com',
    ]);
    $response = $mailManager->mail('simpletest', 'from_test', $recipients, 'en', [
      'sendwithus' => ['options' => ['adapter' => $adapter]],
    ]);

    $result = $response['result'];

    $this->assertEquals((object) ['success' => TRUE], $result['response']);

    // Make sure additional addresses are added as bcc.
    $expected_mails = [
      ['address' => 'test2@example.com', 'name' => 'Test2 Mail'],
      ['address' => 'test3@example.com'],
      ['address' => 'test4@example.com'],
    ];
    $this->assertEquals($expected_mails, $result['template']->getVariable('bcc'));
  }

}
