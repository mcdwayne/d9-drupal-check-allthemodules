<?php

namespace Drupal\personal_digest\Tests;

use Drupal\views\Views;

/**
 * Tests for the email_example module.
 *
 * @group personal_digest
 */
class PersonalDigestSendEmailTest extends PersonalDigestTestBase {

  /**
   * Send a digest.
   */
  public function testSendEmail() {

    // Create two nodes.
    $node1 = $this->drupalCreateNode();
    $node2 = $this->drupalCreateNode();

    $view = Views::getView('personal_digest_test');
    $view->setArguments([date('Y-m-d', strtotime('yesterday'))]);
    $view->setDisplay('default');
    $result = $view->preview();

    \Drupal::service('user.data')->set(
      'personal_digest',
      $this->adminUser->id(),
      'digest',
      [
        'displays' => ['personal_digest_test:default' => '0'],
        'daysoftheweek' => [
          0 => 0,
          1 => 1,
          2 => 2,
          3 => 3,
          4 => 4,
          5 => 5,
          6 => 6,
        ],
        'weeks_interval' => 1,
      ]
    );

    $this->cronRun();
    $mails = \Drupal::state()->get('system.test_mail_collector', []);
    $mail = $mails[0]['body'];
    // Check if contain the created nodes.
    $this->assertTrue(strstr($mail, $node1->getTitle()), 'The first node was sent');
    $this->assertTrue(strstr($mail, $node2->getTitle()), 'The second node was sent');
  }

}