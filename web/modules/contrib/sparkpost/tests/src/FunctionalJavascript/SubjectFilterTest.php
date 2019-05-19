<?php

namespace Drupal\Tests\sparkpost\FunctionalJavascript;

use Drupal\Core\Language\LanguageInterface;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests that we are not double escaping the subject.
 *
 * @group sparkpost
 */
class SubjectFilterTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['sparkpost'];

  /**
   * Test the filtering of the subject.
   */
  public function testSubject() {
    // Send it async, so we can inspect it.
    $config = \Drupal::configFactory()
      ->getEditable('sparkpost.settings');

    $config->set('async', TRUE);
    $config->save();

    // Then send an email. We use the test email, since we define it ourselves.
    $to = 'user@example.com';
    $test_message = [
      'subject' => '<h1>This is markup</h1> and this is not',
      'body' => '<h1>This is also markup</h1> and this is not.',
      'include_attachment' => FALSE,
    ];
    \Drupal::service('plugin.manager.mail')
      ->mail('sparkpost', 'test_mail_form', $to, LanguageInterface::LANGCODE_NOT_SPECIFIED, $test_message);
    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = \Drupal::queue('sparkpost_send');
    self::assertEquals(1, $queue->numberOfItems(), 'Queue holds the correct amount of items.');
    $item = $queue->claimItem();
    /** @var \Drupal\sparkpost\MessageWrapper $msg */
    $msg = $item->data;
    $drupal_message = $msg->getDrupalMessage();
    $sparkpost_message = $msg->getSparkpostMessage();
    // The messages should be the same.
    $this->assertEquals($drupal_message['subject'], $sparkpost_message['content']['subject']);
    // And they should not hold markup.
    $this->assertNotEquals($test_message['subject'], $drupal_message['subject']);
    $this->assertEquals('This is markup and this is not', $drupal_message['subject']);
  }

}
