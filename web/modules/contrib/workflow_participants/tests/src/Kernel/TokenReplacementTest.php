<?php

namespace Drupal\Tests\workflow_participants\Kernel;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests token replacements.
 *
 * @group workflow_participants
 */
class TokenReplacementTest extends WorkflowParticipantsTestBase {

  use NodeCreationTrait;
  use ContentTypeCreationTrait;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Some participants.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $participants;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->token = $this->container->get('token');
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['node']);
    $this->installEntitySchema('node');

    foreach (range(1, 5) as $i) {
      $this->participants[$i] = $this->createUser(['can be workflow participant']);
    }

    // Enable workflow for nodes and entity_test_rev.
    $this->createContentType(['type' => 'article']);
    $this->enableModeration('node', 'article');
  }

  /**
   * Tests workflow participant tokens for nodes.
   */
  public function testNodeTokens() {
    $node = $this->createNode();
    $participants = $this->setUpParticipants($node);

    // Verify expected tokens.
    $tokens = [];
    workflow_participants_token_info_alter($tokens);
    $this->assertArrayHasKey('node', $tokens['tokens']);
    $this->assertArrayNotHasKey('user', $tokens['tokens']);
    $this->assertArrayNotHasKey('workflow_participants', $tokens['tokens']);

    $tests = [];
    $editors = [
      $this->participants[3]->id() => $this->participants[3]->getDisplayName(),
      $this->participants[4]->id() => $this->participants[4]->getDisplayName(),
      $this->participants[5]->id() => $this->participants[5]->getDisplayName(),
    ];
    $reviewers = [
      $this->participants[1]->id() => $this->participants[1]->getDisplayName(),
      $this->participants[2]->id() => $this->participants[2]->getDisplayName(),
      $this->participants[3]->id() => $this->participants[3]->getDisplayName(),
    ];
    $all = array_unique($editors + $reviewers);
    asort($editors);
    asort($reviewers);
    asort($all);
    $tests['[node:editors]'] = implode(', ', $editors);
    $tests['[node:reviewers]'] = implode(', ', $reviewers);
    $tests['[node:all-participants]'] = implode(', ', $all);
    $tests['[node:participant-type]'] = 'Editor';

    // Metadata.
    $base_bubbleable_metadata = BubbleableMetadata::createFromObject($node);
    $base_bubbleable_metadata->addCacheableDependency($participants);

    $metadata_tests = [];
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[node:editors]'] = $bubbleable_metadata->addCacheTags([
      'user:' . $this->participants[3]->id(),
      'user:' . $this->participants[4]->id(),
      'user:' . $this->participants[5]->id(),
    ]);
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[node:reviewers]'] = $bubbleable_metadata->addCacheTags([
      'user:' . $this->participants[1]->id(),
      'user:' . $this->participants[2]->id(),
      'user:' . $this->participants[3]->id(),
    ]);
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[node:all-participants]'] = $bubbleable_metadata->addCacheTags([
      'user:' . $this->participants[1]->id(),
      'user:' . $this->participants[2]->id(),
      'user:' . $this->participants[3]->id(),
      'user:' . $this->participants[4]->id(),
      'user:' . $this->participants[5]->id(),
    ]);
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[node:participant-type]'] = $bubbleable_metadata->addCacheTags([
      'user:' . $this->participants[3]->id(),
    ]);

    foreach ($tests as $input => $expected) {
      $bubbleable_metadata = new BubbleableMetadata();
      $output = $this->token->replace($input, ['node' => $node, 'user' => $this->participants[3]], [], $bubbleable_metadata);
      $this->assertEquals($expected, $output, 'Token replacement did not match for ' . $input);
      $this->assertEquals($metadata_tests[$input], $bubbleable_metadata, 'Metadata for token replacement did not match for ' . $input);
    }

    // Test reviewers are replaced too.
    $bubbleable_metadata = new BubbleableMetadata();
    $expected_metatdata = clone $base_bubbleable_metadata;
    $expected_metatdata->addCacheTags([
      'user:' . $this->participants[1]->id(),
    ]);
    $output = $this->token->replace('[node:participant-type]', ['node' => $node, 'user' => $this->participants[1]], [], $bubbleable_metadata);
    $this->assertEquals(t('Reviewer'), $output, 'Token replacement did not match for [node:participant-type]');
    $this->assertEquals($expected_metatdata, $bubbleable_metadata);
  }

  /**
   * Setup participants for the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to setup participants for.
   *
   * @return \Drupal\workflow_participants\Entity\WorkflowParticipantsInterface
   *   The participants entity.
   */
  protected function setUpParticipants(ContentEntityInterface $entity) {
    $participants = $this->participantStorage->loadForModeratedEntity($entity);
    $participants->reviewers = [
      ['target_id' => $this->participants[1]->id()],
      ['target_id' => $this->participants[2]->id()],
      ['target_id' => $this->participants[3]->id()],
    ];
    $participants->editors = [
      ['target_id' => $this->participants[3]->id()],
      ['target_id' => $this->participants[4]->id()],
      ['target_id' => $this->participants[5]->id()],
    ];
    $participants->save();
    $this->participantStorage->resetCache();
    return $this->participantStorage->loadForModeratedEntity($entity);

  }

}
