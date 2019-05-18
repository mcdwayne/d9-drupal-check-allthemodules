<?php

namespace Drupal\Tests\message_thread\Functional;

/**
 * Testing the listing functionality for the Message thread template entity.
 *
 * @group message_thread
 */
class MessageThreadTemplateListTest extends MessageThreadTestBase {

  /**
   * The user object.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Currently experiencing schema errors.
   *
   * @var strictConfigSchema
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Listing of messages.
   */
  public function testEntityTypeList() {
    $this->user = $this->drupalCreateUser(['administer message thread templates']);
    $this->drupalLogin($this->user);

    $this->drupalGet('admin/structure/message-threads');
    $this->assertResponse(200);
  }

}
