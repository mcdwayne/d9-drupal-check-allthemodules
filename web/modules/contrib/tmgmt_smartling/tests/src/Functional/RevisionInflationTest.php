<?php

namespace Drupal\Tests\tmgmt_smartling\Functional;

use Drupal\node\Entity\Node;
use Drupal\tmgmt\Entity\Job;

/**
 * Revision inflation tests.
 *
 * @group tmgmt_smartling
 */
class RevisionInflationTest extends SmartlingTestBase {

  /**
   * Test revision inflation.
   */
  public function testRevisionInflation() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $node_1 = Node::create([
        'title' => t('Post 1 title'),
        'type' => 'translatable_node',
        'uid' => '0',
        'sticky' => TRUE,
        'body' => [
          'value' => 'Post 1 body',
          'format' => 'basic_html',
        ],
      ]);
      $node_1->save();

      $node_2 = Node::create([
        'title' => t('Post 2 title'),
        'type' => 'translatable_node',
        'uid' => '0',
        'sticky' => TRUE,
        'body' => [
          'value' => 'Post 2 body',
          'format' => 'basic_html',
        ],
      ]);
      $node_2->save();

      $providerSettings = $this->smartlingPluginProviderSettings;
      $translator = $this->setUpSmartlingProviderSettings($providerSettings);
      $job = $this->requestTranslationForNode([$node_1->id(), $node_2->id()], $this->targetLanguage, $translator);

      $this->drupalGet("/admin/tmgmt/jobs/1");
      $this->assertResponse(200);
      $this->assertNoText("has been accepted as");
      $this->assertNoText("Import of downloaded file was skipped: downloaded and existing translations are equal.");
      $this->assertTrue($job->isActive());

      $job->getTranslatorPlugin()->downloadTranslation($job);
      $job = Job::load($job->id());
      $this->drupalGet("/admin/tmgmt/jobs/1");
      $this->assertResponse(200);
      $this->assertText("The translation for Post 1 title has been accepted");
      $this->assertText("The translation for Post 2 title has been accepted");
      $this->assertNoText("Import of downloaded file was skipped: downloaded and existing translations are equal.");
      $this->assertTrue($job->isFinished());

      foreach ($job->getItems() as $item) {
        $this->assertEqual($item->isAccepted(), TRUE);
      }

      $job->getTranslatorPlugin()->downloadTranslation($job);
      $job = Job::load($job->id());
      $this->drupalGet("/admin/tmgmt/jobs/1");
      $this->assertResponse(200);
      $this->assertText("Import of downloaded file was skipped: downloaded and existing translations are equal.");
      $this->assertTrue($job->isFinished());

      foreach ($job->getItems() as $item) {
        $this->assertEqual($item->isAccepted(), TRUE);
      }

      foreach ($job->getMessages() as $message) {
        $message->delete();
      }

      // Delete translation from one node.
      $node_1 = Node::load($node_1->id());
      $node_1->removeTranslation($this->targetLanguage);
      $node_1->save();

      $this->drupalGet("/admin/tmgmt/jobs/1");
      $this->assertResponse(200);
      $this->assertNoText("has been accepted as");
      $this->assertNoText("Import of downloaded file was skipped: downloaded and existing translations are equal.");
      $this->assertTrue($job->isFinished());

      $job->getTranslatorPlugin()->downloadTranslation($job);
      $job = Job::load($job->id());
      $this->drupalGet("/admin/tmgmt/jobs/1");
      $this->assertResponse(200);
      $this->assertText("The translation for Post 1 title has been accepted");
      $this->assertNoText("The translation for Post 2 title has been accepted");
      $this->assertNoText("Import of downloaded file was skipped: downloaded and existing translations are equal.");
      $this->assertTrue($job->isFinished());

      foreach ($job->getItems() as $item) {
        $this->assertEqual($item->isAccepted(), TRUE);
      }

      $job->getTranslatorPlugin()->downloadTranslation($job);
      $job = Job::load($job->id());
      $this->drupalGet("/admin/tmgmt/jobs/1");
      $this->assertResponse(200);
      $this->assertNoText("The translation for Post 2 title has been accepted");
      $this->assertText("Import of downloaded file was skipped: downloaded and existing translations are equal.");
      $this->assertTrue($job->isFinished());

      foreach ($job->getItems() as $item) {
        $this->assertEqual($item->isAccepted(), TRUE);
      }

      foreach ($job->getMessages() as $message) {
        $message->delete();
      }

      // Delete translation from all the nodes.
      $node_1 = Node::load($node_1->id());
      $node_1->removeTranslation($this->targetLanguage);
      $node_1->save();

      $node_2 = Node::load($node_2->id());
      $node_2->removeTranslation($this->targetLanguage);
      $node_2->save();

      $this->drupalGet("/admin/tmgmt/jobs/1");
      $this->assertResponse(200);
      $this->assertNoText("has been accepted as");
      $this->assertNoText("Import of downloaded file was skipped: downloaded and existing translations are equal.");
      $this->assertTrue($job->isFinished());

      $job->getTranslatorPlugin()->downloadTranslation($job);
      $job = Job::load($job->id());
      $this->drupalGet("/admin/tmgmt/jobs/1");
      $this->assertResponse(200);
      $this->assertText("The translation for Post 1 title has been accepted");
      $this->assertText("The translation for Post 2 title has been accepted");
      $this->assertNoText("Import of downloaded file was skipped: downloaded and existing translations are equal.");
      $this->assertTrue($job->isFinished());

      foreach ($job->getItems() as $item) {
        $this->assertEqual($item->isAccepted(), TRUE);
      }

      $job->getTranslatorPlugin()->downloadTranslation($job);
      $job = Job::load($job->id());
      $this->drupalGet("/admin/tmgmt/jobs/1");
      $this->assertResponse(200);
      $this->assertText("Import of downloaded file was skipped: downloaded and existing translations are equal.");
      $this->assertTrue($job->isFinished());

      foreach ($job->getItems() as $item) {
        $this->assertEqual($item->isAccepted(), TRUE);
      }
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Test revision inflation with disabled "Auto accept finished translations".
   */
  public function testRevisionInflationAutoAcceptFinishedTranslationsIsDisabled() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $providerSettings = $this->smartlingPluginProviderSettings;
      $providerSettings['auto_accept'] = FALSE;
      $translator = $this->setUpSmartlingProviderSettings($providerSettings);
      $job = $this->requestTranslationForNode($this->testNodeId, $this->targetLanguage, $translator);

      $this->drupalGet("/admin/tmgmt/jobs/1");
      $this->assertResponse(200);
      $this->assertNoText("is finished and can now be reviewed.");
      $this->assertNoText("Import of downloaded file was skipped: downloaded and existing translations are equal.");

      $job->getTranslatorPlugin()->downloadTranslation($job);
      $this->drupalGet("/admin/tmgmt/jobs/1");
      $this->assertResponse(200);
      $this->assertText("is finished and can now be reviewed.");
      $this->assertNoText("Import of downloaded file was skipped: downloaded and existing translations are equal.");

      foreach ($job->getItems() as $item) {
        $item->acceptTranslation();
      }

      $job->getTranslatorPlugin()->downloadTranslation($job);
      $this->drupalGet("/admin/tmgmt/jobs/1");
      $this->assertResponse(200);
      $this->assertText("Import of downloaded file was skipped: downloaded and existing translations are equal.");
      $this->drupalGet("/admin/tmgmt/jobs/1");
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

}
