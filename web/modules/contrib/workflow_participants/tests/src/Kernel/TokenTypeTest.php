<?php

namespace Drupal\Tests\workflow_participants\Kernel;

/**
 * Tests token types.
 *
 * @group workflow_participants
 */
class TokenTypeTest extends WorkflowParticipantsTestBase {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field', 'text', 'block', 'block_content', 'token'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->token = $this->container->get('token');
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['block_content']);
    $this->installEntitySchema('block_content');
  }

  /**
   * Tests workflow participant tokens for nodes.
   */
  public function testCustomBlockTokenType() {
    $info = $this->token->getInfo();

    // Verify that the custom block entity type received its name.
    $this->assertArrayHasKey('block_content', $info['types'], 'The custom block type is not defined.');
    $this->assertArrayHasKey('name', $info['types']['block_content'], 'The name property has not been set.');
  }

}
