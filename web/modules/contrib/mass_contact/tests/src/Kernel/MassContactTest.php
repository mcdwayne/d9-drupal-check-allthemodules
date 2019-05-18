<?php

namespace Drupal\Tests\mass_contact\Kernel;

use Drupal\mass_contact\Entity\MassContactMessage;
use Drupal\simpletest\UserCreationTrait;

/**
 * Tests for the mass contact service.
 *
 * @group mass_contact
 *
 * @coversDefaultClass \Drupal\mass_contact\MassContact
 */
class MassContactTest extends MassContactTestBase {

  use CategoryCreationTrait;
  use UserCreationTrait;

  /**
   * The mass contact service.
   *
   * @var \Drupal\mass_contact\MassContactInterface
   */
  protected $massContact;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field', 'options', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installConfig(['mass_contact']);
    $this->installSchema('system', ['sequences']);

    $this->massContact = $this->container->get('mass_contact');
  }

  /**
   * Tests the send me a copy functionality.
   *
   * @covers ::queueRecipients
   */
  public function testQueueRecipients() {
    $category = $this->createCategory();
    $role = $this->createRole([]);
    $category->setRecipients([
      'role' => [
        'categories' => [$role],
        'conjunction' => 'AND',
      ],
    ]);
    $category->save();

    $message = MassContactMessage::create([
      'categories' => [$category->id()],
    ]);
    $this->massContact->queueRecipients($message);

    // Queue should be empty.
    $queue = \Drupal::queue('mass_contact_send_message');
    $this->assertEquals(0, $queue->numberOfItems());

    // Add some users with this role.
    /** @var \Drupal\user\UserInterface[] $accounts */
    $accounts = [];
    foreach (range(1, 5) as $i) {
      $accounts[$i] = $this->createUser();
      $accounts[$i]->addRole($role);
      $accounts[$i]->save();
    }

    $this->massContact->queueRecipients($message);
    $queue = \Drupal::queue('mass_contact_send_message');
    $this->assertEquals(1, $queue->numberOfItems());
    // Grab the item and verify recipients.
    $item = $queue->claimItem();
    $this->assertEquals(5, count($item->data['recipients']));
    $this->assertEquals(array_keys($accounts), $item->data['recipients']);
    $queue->deleteItem($item);

    // Send me a copy user.
    $copy_user = $this->createUser();
    $this->massContact->queueRecipients($message, ['send_me_copy_user' => $copy_user->id()]);
    $queue = \Drupal::queue('mass_contact_send_message');
    $this->assertEquals(1, $queue->numberOfItems());
    // Grab the item and verify recipients.
    $item = $queue->claimItem();
    $this->assertEquals(6, count($item->data['recipients']));
    // Copy user should be first recipient.
    $this->assertEquals($copy_user->id(), array_shift($item->data['recipients']));
    $this->assertEquals(array_keys($accounts), $item->data['recipients']);
    $queue->deleteItem($item);
  }

}
