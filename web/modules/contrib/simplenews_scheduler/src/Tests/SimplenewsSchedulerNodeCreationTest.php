<?php

/**
 * @file
 * Simplenews scheduler node creation test functions.
 *
 * @ingroup simplenews_scheduler
 */

namespace Drupal\simplenews_scheduler\Tests;
use Drupal\node\Entity\Node;

/**
 * Testing generation of newsletters.
 *
 * @group simplenews_scheduler
 */
class SimplenewsSchedulerNodeCreationTest extends SimplenewsSchedulerWebTestBase {

  public static $modules = ['block'];

  function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
    $admin_user = $this->drupalCreateUser(array(
      'access content',
      'administer nodes',
      'create simplenews_issue content',
      'edit own simplenews_issue content',
      'send newsletter',
      'send scheduled newsletters',
      'overview scheduled newsletters',
    ));
    $this->drupalLogin($admin_user);

    // Subscribe a user to simplenews.
    $this->mail = 'test@example.org';
    \Drupal::service('simplenews.subscription_manager')->subscribe($this->mail, 'default', FALSE, 'test');
  }

  /**
   * Basic simplenews newsletter generation test
   * create a simplenews node,
   */
  function testNewsletterGeneration() {
    $title ="newsletter " . $this->randomMachineName(8);

    $edit = array(
      'title[0][value]' => $title,
      'body[0][value]' => $this->randomMachineName(16),
      'simplenews_issue' => 'default',
    );
    $this->drupalPostForm('node/add/simplenews_issue', $edit, t('Save and publish'));
    $this->assertText($title);

    preg_match('|node/(\d+)$|', $this->getUrl(), $matches);
    $node = Node::load($matches[1]);

    // Make sure that the editions tab is not visible as long as it's not a
    // scheduled newsletter.
    $this->drupalGet("node/{$node->id()}/editions");
    $this->assertResponse(403, t('Editions tab not accessible'));

    // Now create the simplenews schedule configuration.
    $this->drupalGet("node/{$node->id()}/simplenews");
    $this->assertText(t('Enable scheduled newsletter'));

    $edit = array();
    $edit['enable_scheduler'] = TRUE;
    $edit["interval"] = "hour";

    // Specify a start time 30 minutes in the past to be able to have a known
    // edition creation time that can be checked.
    $date = new \DateTime();
    $date->sub(new \DateInterval('PT30M'));

    $edit["start_date[date]"] = $date->format('Y-m-d');
    $edit["start_date[time]"] = $date->format('H:i:s');
    $edit["title"] = "Custom title [node:nid]";

    $this->drupalPostForm("node/{$node->id()}/simplenews", $edit, t('Save scheduler settings'));

    // Check if the saving was successful.
    $this->assertText('Newsletter schedule preferences have been saved.');
    $this->assertFieldByName('start_date[date]', $date->format('Y-m-d'));
    $this->assertFieldByName('start_date[time]', $date->format('H:i:s'));
    $this->assertFieldByName('title', "Custom title [node:nid]");

    // Make sure it knows no editions created yet.
    $this->drupalGet("node/{$node->id()}/editions");
    $this->assertText(t("No scheduled newsletter editions have been sent."));

    // Execute cron.
    \Drupal::service('cron')->run();

    $edition_nids = \Drupal::entityQuery('node')
      ->sort('nid', 'DESC')
      ->execute();
    $edition_node = Node::load(reset($edition_nids));

    // Check that the newsletter has been saved and sent.
    $this->assertTrue($edition_node->simplenews_issue->subscribers > 0);

    // See if it was created.
    $this->drupalGet("node/{$node->id()}/editions");
    $this->assertText("Custom title " . $edition_node->id());
    $this->assertNoText(t("No scheduled newsletter editions have been sent."));

    // Go to node and verify creation time and token for custom title
    $this->clickLink("Custom title " . $edition_node->id());

    $this->assertEqual($edition_node->getCreatedTime(), $date->getTimestamp());

    // Check sent mails.
    $mails = $this->drupalGetMails();
    $this->assertEqual(1, count($mails), t('Newsletter mail has been sent.'));

    $this->clickLink(t('Newsletter'));
    $this->assertText(t('This node is part of a scheduled newsletter configuration.'));
    $this->clickLink(t('here'));
    $url = $node->url('canonical', ['absolute' => TRUE]);

    $this->assertEqual($url, $this->getUrl());

    // Test the tab on a sent newsletter, schedule details should not be shown.
    $title = "newsletter " . $this->randomMachineName(8);

    $edit = array(
      'title[0][value]' => $title,
      'body[0][value]' => $this->randomMachineName(16),
      'simplenews_issue' => 'default',
    );
    $this->drupalPostForm('node/add/simplenews_issue', $edit, t('Save and publish'));
    $this->assertText($title);

    preg_match('|node/(\d+)$|', $this->getUrl(), $matches);
    $node = \Drupal::entityManager()->getStorage('node')->load($matches[1]);

    $edit = array();
    $this->drupalPostForm("node/{$node->id()}/simplenews", $edit, t('Send now'));
    $this->assertNoText(t('Scheduled Newsletter'));

    // Check sent mails.
    $mails = $this->drupalGetMails();
    $this->assertEqual(1, count($mails), t('Newsletter mail has been sent.'));
  }

}
