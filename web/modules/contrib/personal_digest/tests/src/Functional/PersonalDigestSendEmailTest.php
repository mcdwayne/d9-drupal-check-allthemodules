<?php

namespace Drupal\Tests\personal_digest\Functional;

use DateTime;
use DateInterval;

/**
 * Tests for the email_example module.
 *
 * @group personal_digest
 */
class PersonalDigestSendEmailTest extends PersonalDigestTestBase {

  /**
   * Tests Nodes.
   * @var array
   */
  protected $testsNodes = [];

  /**
   * User Settings.
   */
  protected $settings = [];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->settings = [
      'displays' => ['personal_digest_test:default' => '0'],
      'daysoftheweek' => date('l', strtotime('today')),
      'weeks_interval' => 1,
    ];
    $this->setSettings($this->settings);
  }

  /**
   * Send a digest.
   */
  public function testSendEmail() {
    // Default content.
    $this->testsNodes[] = $this->drupalCreateNode();
    $this->testsNodes[] = $this->drupalCreateNode();

    // Correct Day and correct hour, The digest should be send.
    \Drupal::state()->set('personal_digest_time', mktime(9, 0, 0));
    $this->cronRun();
    $mails = \Drupal::state()->get('system.test_mail_collector', []);
    $mail = $mails[0]['body'];

    // Check if the digest contain the created nodes.
    foreach ($this->testsNodes as $node) {
      $this->assertTrue(strstr($mail, $node->getTitle()), 'The ' . $node->getTitle() . ' node was sent');
    }
  }

  /**
   * Send a digest.
   */
  public function testNoContent() {
    // No content in this test.
    // Correct Day and correct hour, but no content, the mail should't be send
    \Drupal::state()->set('personal_digest_time', mktime(9, 0, 0));
    $this->cronRun();
    $mails = \Drupal::state()->get('system.test_mail_collector', []);
    // Check to no mail was sent.
    $this->assertTrue(empty($mails), 'No mails were sent because there was no content');
  }


  /**
   * No mail sent when is not after the configured hour.
   */
  public function testNoHour() {
    // Default content.
    $this->testsNodes[] = $this->drupalCreateNode();
    // Correct day, wrong  hour.
    \Drupal::state()->set('personal_digest_time', mktime(7, 0, 0));
    $this->cronRun();

    $mails = \Drupal::state()->get('system.test_mail_collector', []);
    $this->assertTrue(empty($mails), 'No mails were sent because it was\'t the correct hour');
  }

  /**
   * No mail sent when is not the day of the week.
   */
  public function testNoDayOfTheWeek() {
    $settings = $this->settings;
    $settings['daysoftheweek'] = date('l', strtotime('tomorrow'));
    $this->setSettings($settings);
    // Default content.
    $this->testsNodes[] = $this->drupalCreateNode();
    // Correct hour, wrong day.
    \Drupal::state()->set('personal_digest_time', mktime(9, 0, 0));
    $this->cronRun();

    $mails = \Drupal::state()->get('system.test_mail_collector', []);
    $this->assertTrue(empty($mails), 'No mails were sent because today wasn\'t the correct day to send the digest');
  }

  /**
   * Mail sent on dially basis.
   */
  public function testSendDialy() {
    $settings = $this->settings;
    $settings['weeks_interval'] = -1;
    $this->setSettings($settings);

    // Default content.
    $this->testsNodes[] = $this->drupalCreateNode();
    $this->testsNodes[] = $this->drupalCreateNode();
    $this->testsNodes[] = $this->drupalCreateNode();
    $this->testsNodes[] = $this->drupalCreateNode();
    $this->testsNodes[] = $this->drupalCreateNode();
    $this->testsNodes[] = $this->drupalCreateNode();
    $this->testsNodes[] = $this->drupalCreateNode();

    $now = \Drupal::service('datetime.time')->getRequestTime();
    // Change the node dates to get one per day.
    $daysago = 1;
    foreach ($this->testsNodes as $key => $node) {
      /** @var DateTime $date */
      $date = new DateTime();
      $date->setTimestamp($now);
      $date->setTime(6, 0);
      $date->sub(new DateInterval("P{$daysago}D"));

      $created_time = $date->getTimestamp();
      /** @var \Drupal\node\Entity\Node $node */
      $node->setCreatedTime($created_time);
      $node->save();
      $daysago += 1;
    }

    foreach (array_reverse(range(1, 7)) as $daysago) {
      $date = new DateTime();
      $date->setTimestamp($now);
      $date->sub(new DateInterval("P{$daysago}D"));
      $date->setTime(9, 0);
      \Drupal::state()->set('personal_digest_time', $date->getTimestamp());
      $this->cronRun();
      $mails_sent = count(\Drupal::state()->get('system.test_mail_collector', []));
      $this->assertTrue($mails_sent > 0, "{$mails_sent} were sent");
      \Drupal::state()->set('system.test_mail_collector', []);
    }
  }

  protected function setSettings($settings) {
    // Subscribing the adminUser to get the digest in the next Cron.
    \Drupal::service('user.data')->set(
      'personal_digest',
      $this->adminUser->id(),
      'digest',
      $settings
    );
  }

}
