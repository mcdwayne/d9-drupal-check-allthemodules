<?php

namespace Drupal\Tests\workflow_participants\Kernel;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the notifier service.
 *
 * @group workflow_participants
 *
 * @coversDefaultClass \Drupal\workflow_participants\ParticipantNotifier
 *
 * @requires module token
 */
class ParticipantNotifierTest extends WorkflowParticipantsTestBase {

  use AssertMailTrait;
  use NodeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['token', 'filter_test'];

  /**
   * Test fixture.
   *
   * @var \Drupal\workflow_participants\ParticipantNotifierInterface
   */
  protected $notifier;

  /**
   * User accounts to be used as participants.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $accounts;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->notifier = \Drupal::service('workflow_participants.notifier');

    $this->installEntitySchema('node');

    // Add some participants.
    $this->installSchema('system', ['sequences']);
    foreach (range(1, 5) as $i) {
      $this->accounts[$i] = $this->createUser(['can be workflow participant']);
    }
    $this->installConfig($this->installConfig(['filter_test']));
  }

  /**
   * Tests new participants.
   *
   * @covers ::getNewParticipants
   */
  public function testGetNewParticipants() {
    $entity = EntityTestRev::create();
    $entity->save();
    $participants = $this->participantStorage->loadForModeratedEntity($entity);
    $this->assertEmpty($this->notifier->getNewParticipants($participants));

    // Add some participants.
    $participants->editors = [
      ['target_id' => $this->accounts[1]->id()],
      ['target_id' => $this->accounts[2]->id()],
    ];
    $participants->reviewers = [
      ['target_id' => $this->accounts[4]->id()],
    ];
    $expected = [
      (int) $this->accounts[1]->id(),
      (int) $this->accounts[2]->id(),
      (int) $this->accounts[4]->id(),
    ];
    $this->assertSame($expected, array_keys($this->notifier->getNewParticipants($participants)));

    // Save the participants.
    $participants->save();

    // Reload and save to mimic an update.
    $this->participantStorage->resetCache();
    $participants = $this->participantStorage->loadForModeratedEntity($entity);
    $participants->save();
    $participants->original = $this->participantStorage->loadUnchanged($participants->id());
    $this->assertEmpty($this->notifier->getNewParticipants($participants));

    $participants->reviewers[] = ['target_id' => $this->accounts[5]->id()];
    $expected = [(int) $this->accounts[5]->id()];
    $this->assertSame($expected, array_keys($this->notifier->getNewParticipants($participants)));
  }

  /**
   * Tests process notifications.
   *
   * @covers ::processNotifications
   */
  public function testProcessNotifications() {
    $config = \Drupal::configFactory()->getEditable('workflow_participants.settings');
    $config->set('participant_message.subject', 'A subject [node:title]');
    $config->set('participant_message.body.value', 'A body [node:type]');
    $config->save();

    $node = $this->createNode();
    $node->save();
    $participants = $this->participantStorage->loadForModeratedEntity($node);
    $participants->save();

    $this->assertEmpty($this->getMails());

    $node = $this->createNode();
    $node->save();
    $participants = $this->participantStorage->loadForModeratedEntity($node);
    $participants->editors = [
      ['target_id' => $this->accounts[1]->id()],
      ['target_id' => $this->accounts[2]->id()],
    ];
    $participants->reviewers = [
      ['target_id' => $this->accounts[4]->id()],
    ];
    $participants->save();
    $mails = $this->getMails();
    $this->assertCount(3, $mails);

    // Ensure token replacement.
    $this->assertMail('subject', 'A subject ' . $node->label());
    $this->assertMail('body', 'A body ' . $node->getType() . "\n\n");

    // Add 2 new users.
    $this->container->get('state')->set('system.test_mail_collector', []);
    $participants->editors[] = ['target_id' => $this->accounts[5]->id()];
    $participants->reviewers[] = ['target_id' => $this->accounts[3]->id()];
    $participants->save();
    $mails = $this->getMails();
    $this->assertCount(2, $mails);

    $mail = array_shift($mails);
    $this->assertEquals($this->accounts[3]->getEmail(), $mail['to']);
  }

  /**
   * Tests process notifications.
   *
   * @covers ::processNotifications
   */
  public function testFilterFormatNotifications() {
    // Ensure that the filter formats are used.
    $config = \Drupal::configFactory()->getEditable('workflow_participants.settings');
    $this->container->get('state')->set('system.test_mail_collector', []);
    $config->set('participant_message.body.value', 'A <strong>filtered</strong> body');
    $config->set('participant_message.body.format', 'plain_text');
    $config->save();

    $node = $this->createNode();
    $participants = $this->participantStorage->loadForModeratedEntity($node);
    $participants->editors[] = ['target_id' => $this->accounts[1]->id()];
    $participants->save();

    $mail = $this->getMails();
    // Ensure that <strong> is not escaped.
    $this->assertNotEquals((string) $mail[0]['params']['body'], $config->get('participant_message.body.value'));

    $this->container->get('state')->set('system.test_mail_collector', []);
    $config->set('participant_message.body.format', 'filtered_html');
    $config->save();
    $participants->editors[] = ['target_id' => $this->accounts[2]->id()];
    $participants->save();

    $mail = $this->getMails();
    // Ensure that <strong> is escaped.
    $this->assertEquals((string) $mail[0]['params']['body'], $config->get('participant_message.body.value'));
  }

}
