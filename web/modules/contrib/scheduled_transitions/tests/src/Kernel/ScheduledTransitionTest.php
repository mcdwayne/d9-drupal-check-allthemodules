<?php

declare(strict_types = 1);

namespace Drupal\Tests\scheduled_transitions\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_test_revlog\Entity\EntityTestWithRevisionLog;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\scheduled_transitions\Entity\ScheduledTransition;
use Drupal\scheduled_transitions\Entity\ScheduledTransitionInterface;
use Drupal\scheduled_transitions_test\Entity\ScheduledTransitionsTestEntity as TestEntity;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\user\Entity\User;
use Symfony\Component\Debug\BufferingLogger;

/**
 * Tests basic functionality of scheduled_transitions fields.
 *
 * @group scheduled_transitions
 */
class ScheduledTransitionTest extends KernelTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test_revlog',
    'entity_test',
    'scheduled_transitions_test',
    'scheduled_transitions',
    'content_moderation',
    'workflows',
    'dynamic_entity_reference',
    'user',
    'language',
    'system',
  ];

  /**
   * The service name of a logger.
   *
   * @var string
   */
  protected $testLoggerServiceName = 'test.logger';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('st_entity_test');
    $this->installEntitySchema('st_nont_entity_test');
    $this->installEntitySchema('entity_test_revlog');
    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('user');
    $this->installEntitySchema('scheduled_transition');
    $this->installSchema('system', ['queue']);
    $this->installConfig(['scheduled_transitions']);
  }

  /**
   * Tests a scheduled revision.
   *
   * Publish a revision in the past (not latest).
   */
  public function testScheduledRevision() {
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_revlog', 'entity_test_revlog');
    $workflow->save();

    $author = User::create([
      'uid' => 2,
      'name' => $this->randomMachineName(),
    ]);
    $author->save();

    $entity = EntityTestWithRevisionLog::create(['type' => 'entity_test_revlog']);
    $entity->moderation_state = 'draft';
    $entity->save();
    $entityId = $entity->id();
    $this->assertEquals(1, $entity->getRevisionId());

    $entity->setNewRevision();
    $entity->moderation_state = 'draft';
    $entity->save();
    $this->assertEquals(2, $entity->getRevisionId());

    $entity->setNewRevision();
    $entity->moderation_state = 'draft';
    $entity->save();
    $this->assertEquals(3, $entity->getRevisionId());

    $newState = 'published';
    $scheduledTransition = ScheduledTransition::create([
      'entity' => $entity,
      'entity_revision_id' => 2,
      'author' => $author,
      'workflow' => $workflow->id(),
      'moderation_state' => $newState,
      'transition_on' => (new \DateTime('2 Feb 2018 11am'))->getTimestamp(),
    ]);
    $scheduledTransition->save();

    $this->runTransition($scheduledTransition);

    $logs = $this->getLogs();
    $this->assertCount(2, $logs);
    $this->assertEquals('Copied revision #2 and changed from Draft to Published', $logs[0]['message']);
    $this->assertEquals('Deleted scheduled transition #1', $logs[1]['message']);

    $revisionIds = $this->getRevisionIds($entity);
    $this->assertCount(4, $revisionIds);

    // Reload the entity.
    $entity = EntityTestWithRevisionLog::load($entityId);
    $this->assertEquals('published', $entity->moderation_state->value, sprintf('Entity is now %s.', $newState));
    $this->assertEquals('Scheduled transition: copied revision #2 and changed from Draft to Published', $entity->getRevisionLogMessage());
  }

  /**
   * Tests a scheduled revision.
   *
   * Publish the latest revision.
   */
  public function testScheduledRevisionLatestNonDefault() {
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_revlog', 'entity_test_revlog');
    $workflow->save();

    $author = User::create([
      'uid' => 2,
      'name' => $this->randomMachineName(),
    ]);
    $author->save();

    $entity = EntityTestWithRevisionLog::create(['type' => 'entity_test_revlog']);
    $entity->moderation_state = 'draft';
    $entity->save();
    $entityId = $entity->id();
    $this->assertEquals(1, $entity->getRevisionId());

    $entity->setNewRevision();
    $entity->moderation_state = 'draft';
    $entity->save();
    $this->assertEquals(2, $entity->getRevisionId());

    $entity->setNewRevision();
    $entity->moderation_state = 'draft';
    $entity->save();
    $this->assertEquals(3, $entity->getRevisionId());

    $newState = 'published';
    $scheduledTransition = ScheduledTransition::create([
      'entity' => $entity,
      'entity_revision_id' => 3,
      'author' => $author,
      'workflow' => $workflow->id(),
      'moderation_state' => $newState,
      'transition_on' => (new \DateTime('2 Feb 2018 11am'))->getTimestamp(),
    ]);
    $scheduledTransition->save();

    $this->runTransition($scheduledTransition);

    $logs = $this->getLogs();
    $this->assertCount(2, $logs);
    $this->assertEquals('Transitioning latest revision from Draft to Published', $logs[0]['message']);
    $this->assertEquals('Deleted scheduled transition #1', $logs[1]['message']);

    $revisionIds = $this->getRevisionIds($entity);
    $this->assertCount(4, $revisionIds);

    // Reload the entity.
    $entity = EntityTestWithRevisionLog::load($entityId);
    $this->assertEquals('published', $entity->moderation_state->value, sprintf('Entity is now %s.', $newState));
    $this->assertEquals('Scheduled transition: transitioning latest revision from Draft to Published', $entity->getRevisionLogMessage());
  }

  /**
   * Tests a scheduled revision.
   */
  public function testScheduledRevisionRecreateNonDefaultHead() {
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_revlog', 'entity_test_revlog');
    $workflow->save();

    $author = User::create([
      'uid' => 2,
      'name' => $this->randomMachineName(),
    ]);
    $author->save();

    $entity = EntityTestWithRevisionLog::create(['type' => 'entity_test_revlog']);
    $entity->name = 'foobar1';
    $entity->moderation_state = 'draft';
    $entity->save();
    $entityId = $entity->id();
    $this->assertEquals(1, $entity->getRevisionId());

    $entity->setNewRevision();
    $entity->name = 'foobar2';
    $entity->moderation_state = 'draft';
    $entity->save();
    $this->assertEquals(2, $entity->getRevisionId());

    $revision3State = 'draft';
    $entity->setNewRevision();
    $entity->name = 'foobar3';
    $entity->moderation_state = $revision3State;
    $entity->save();
    $this->assertEquals(3, $entity->getRevisionId());

    $newState = 'published';
    $scheduledTransition = ScheduledTransition::create([
      'entity' => $entity,
      'entity_revision_id' => 2,
      'author' => $author,
      'workflow' => $workflow->id(),
      'moderation_state' => $newState,
      'transition_on' => (new \DateTime('2 Feb 2018 11am'))->getTimestamp(),
      'options' => [
        [ScheduledTransition::OPTION_RECREATE_NON_DEFAULT_HEAD => TRUE],
      ],
    ]);
    $scheduledTransition->save();

    $this->runTransition($scheduledTransition);

    $logs = $this->getLogs();
    $this->assertCount(3, $logs);
    $this->assertEquals('Copied revision #2 and changed from Draft to Published', $logs[0]['message']);
    $this->assertEquals('Reverted Draft revision #3 back to top', $logs[1]['message']);
    $this->assertEquals('Deleted scheduled transition #1', $logs[2]['message']);

    $revisionIds = $this->getRevisionIds($entity);
    $this->assertCount(5, $revisionIds);

    // Reload the entity default revision.
    $entityStorage = \Drupal::entityTypeManager()->getStorage('entity_test_revlog');
    $entity = EntityTestWithRevisionLog::load($entityId);
    $revision4 = $entityStorage->loadRevision($revisionIds[3]);
    $revision5 = $entityStorage->loadRevision($revisionIds[4]);
    $this->assertEquals($revision4->getRevisionId(), $entity->getRevisionId(), 'Default revision is revision 4');
    $this->assertEquals($newState, $entity->moderation_state->value, sprintf('Entity is now %s.', $newState));

    $this->assertEquals($revision4->name->value, 'foobar2');
    $this->assertEquals('Scheduled transition: copied revision #2 and changed from Draft to Published', $revision4->getRevisionLogMessage());

    $this->assertEquals($revision5->name->value, 'foobar3');
    $this->assertEquals('Scheduled transition: reverted Draft revision #3 back to top', $revision5->getRevisionLogMessage());
  }

  /**
   * Tests a scheduled revision.
   *
   * The latest revision is published, ensure it doesnt get republished when
   * recreate_non_default_head is TRUE.
   */
  public function testScheduledRevisionRecreateDefaultHead() {
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_revlog', 'entity_test_revlog');
    $workflow->save();

    $author = User::create([
      'uid' => 2,
      'name' => $this->randomMachineName(),
    ]);
    $author->save();

    $entity = EntityTestWithRevisionLog::create(['type' => 'entity_test_revlog']);
    $entity->name = 'foobar1';
    $entity->moderation_state = 'draft';
    $entity->save();
    $entityId = $entity->id();
    $this->assertEquals(1, $entity->getRevisionId());

    $entity->setNewRevision();
    $entity->name = 'foobar2';
    $entity->moderation_state = 'draft';
    $entity->save();
    $this->assertEquals(2, $entity->getRevisionId());

    $revision3State = 'published';
    $entity->setNewRevision();
    $entity->name = 'foobar3';
    $entity->moderation_state = $revision3State;
    $entity->save();
    $this->assertEquals(3, $entity->getRevisionId());

    $newState = 'published';
    $scheduledTransition = ScheduledTransition::create([
      'entity' => $entity,
      'entity_revision_id' => 2,
      'author' => $author,
      'workflow' => $workflow->id(),
      'moderation_state' => $newState,
      'transition_on' => (new \DateTime('2 Feb 2018 11am'))->getTimestamp(),
      'options' => [
        [ScheduledTransition::OPTION_RECREATE_NON_DEFAULT_HEAD => TRUE],
      ],
    ]);
    $scheduledTransition->save();

    $this->runTransition($scheduledTransition);

    $logs = $this->getLogs();
    $this->assertCount(2, $logs);
    $this->assertEquals('Copied revision #2 and changed from Draft to Published', $logs[0]['message']);
    $this->assertEquals('Deleted scheduled transition #1', $logs[1]['message']);

    $revisionIds = $this->getRevisionIds($entity);
    $this->assertCount(4, $revisionIds);

    // Reload the entity default revision.
    $entityStorage = \Drupal::entityTypeManager()->getStorage('entity_test_revlog');
    $entity = EntityTestWithRevisionLog::load($entityId);
    $revision4 = $entityStorage->loadRevision($revisionIds[3]);
    $this->assertEquals($revision4->getRevisionId(), $entity->getRevisionId(), 'Default revision is revision 4');
    $this->assertEquals($newState, $entity->moderation_state->value, sprintf('Entity is now %s.', $newState));

    $this->assertEquals($revision4->name->value, 'foobar2');
    $this->assertEquals('Scheduled transition: copied revision #2 and changed from Draft to Published', $revision4->getRevisionLogMessage());
  }

  /**
   * Test scheduled transitions are cleaned up when entities are deleted.
   */
  public function testScheduledTransitionEntityCleanUp() {
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_revlog', 'entity_test_revlog');
    $workflow->save();

    $entity = EntityTestWithRevisionLog::create([
      'type' => 'entity_test_revlog',
      'name' => 'foo',
      'moderation_state' => 'draft',
    ]);
    $entity->save();

    $scheduledTransition = ScheduledTransition::create([
      'entity' => $entity,
      'entity_revision_id' => $entity->getRevisionId(),
      'author' => 1,
      'workflow' => $workflow->id(),
      'moderation_state' => 'published',
      'transition_on' => (new \DateTime('2 Feb 2018 11am'))->getTimestamp(),
      'options' => [
        ['recreate_non_default_head' => TRUE],
      ],
    ]);
    $scheduledTransition->save();

    $entity->delete();
    $this->assertNull(ScheduledTransition::load($scheduledTransition->id()));
  }

  /**
   * Test when a default or latest revision use a state that no longer exists.
   *
   * Log message displays appropriate info.
   */
  public function testLogsDeletedState() {
    $testState1Name = 'foo_default_test_state1';
    $testState2Name = 'foo_non_default_test_state2';
    $testState3Name = 'published';
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_revlog', 'entity_test_revlog');
    $configuration = $workflow->getTypePlugin()->getConfiguration();
    $configuration['states'][$testState1Name] = [
      'label' => 'Foo',
      'published' => TRUE,
      'default_revision' => TRUE,
      'weight' => 0,
    ];
    $configuration['states'][$testState2Name] = [
      'label' => 'Foo2',
      'published' => TRUE,
      'default_revision' => FALSE,
      'weight' => 0,
    ];
    $workflow->getTypePlugin()->setConfiguration($configuration);
    $workflow->save();

    $author = User::create([
      'uid' => 2,
      'name' => $this->randomMachineName(),
    ]);
    $author->save();

    $entity = EntityTestWithRevisionLog::create(['type' => 'entity_test_revlog']);
    $entity->name = 'foobar1';
    $entity->moderation_state = $testState1Name;
    $entity->save();
    $entityId = $entity->id();
    $this->assertEquals(1, $entity->getRevisionId());

    $entity->setNewRevision();
    $entity->name = 'foobar3';
    $entity->moderation_state = $testState2Name;
    $entity->save();
    $this->assertEquals(2, $entity->getRevisionId());

    $scheduledTransition = ScheduledTransition::create([
      'entity' => $entity,
      'entity_revision_id' => 1,
      'author' => $author,
      'workflow' => $workflow->id(),
      'moderation_state' => $testState3Name,
      'transition_on' => (new \DateTime('2 Feb 2018 11am'))->getTimestamp(),
      'options' => [
        [ScheduledTransition::OPTION_RECREATE_NON_DEFAULT_HEAD => TRUE],
      ],
    ]);
    $scheduledTransition->save();

    $workflow->getTypePlugin()->deleteState($testState1Name);
    $workflow->getTypePlugin()->deleteState($testState2Name);
    $workflow->save();

    $type = $workflow->getTypePlugin();

    // Transitioning the first revision, will also recreate the pending revision
    // in this workflow because of the OPTION_RECREATE_NON_DEFAULT_HEAD option
    // above.
    $this->runTransition($scheduledTransition);

    $logBuffer = $this->getLogBuffer();
    $logs = $this->getLogs($logBuffer);
    $this->assertCount(2, $logs);
    $this->assertEquals('Copied revision #1 and changed from - Unknown state - to Published', $logs[0]['message']);
    $this->assertEquals('Deleted scheduled transition #1', $logs[1]['message']);

    // Also check context of logs, to ensure missing states are present as
    // 'Missing' strings.
    [2 => $context] = $logBuffer[0];
    $this->assertEqual('- Unknown state -', $context['@original_state']);
    $this->assertEqual('- Unknown state -', $context['@original_latest_state']);
    $this->assertEqual('Published', $context['@new_state']);
  }

  /**
   * Tests the moderation state for a specific translation is changed.
   *
   * Other translations remain unaffected.
   */
  public function testTranslationTransition(): void {
    ConfigurableLanguage::createFromLangcode('de')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('st_entity_test', 'st_entity_test');
    $workflow->save();

    $entity = TestEntity::create(['type' => 'st_entity_test']);
    $de = $entity->addTranslation('de');
    $fr = $entity->addTranslation('fr');
    $de->name = 'deName';
    $fr->name = 'frName';
    $de->moderation_state = 'draft';
    $fr->moderation_state = 'draft';
    $entity->save();

    $originalRevisionId = $entity->getRevisionId();
    $originalDeRevisionId = $de->getRevisionId();
    $originalFrRevisionId = $fr->getRevisionId();
    $this->assertEquals(1, $entity->id());
    $this->assertEquals(1, $entity->getRevisionId());
    $this->assertEquals(1, $originalDeRevisionId);
    $this->assertEquals(1, $originalDeRevisionId);

    $author = User::create([
      'uid' => 2,
      'name' => $this->randomMachineName(),
    ]);
    $author->save();
    $scheduledTransition = ScheduledTransition::create([
      'entity' => $entity,
      'entity_revision_id' => 1,
      // Transition 'de'.
      'entity_revision_langcode' => 'de',
      'author' => $author,
      'workflow' => $workflow->id(),
      'moderation_state' => 'published',
      'transition_on' => (new \DateTime('2 Feb 2018 11am'))->getTimestamp(),
    ]);
    $scheduledTransition->save();

    $this->runTransition($scheduledTransition);

    // Reload entity.
    $entity = TestEntity::load($entity->id());
    // Revision ID increments for all translations.
    $this->assertEquals($originalRevisionId + 1, $entity->getRevisionId());
    $this->assertEquals($originalFrRevisionId + 1, $entity->getTranslation('fr')->getRevisionId());
    $this->assertEquals($originalDeRevisionId + 1, $entity->getTranslation('de')->getRevisionId());
    $this->assertEquals('draft', $entity->moderation_state->value);
    $this->assertEquals('draft', $entity->getTranslation('fr')->moderation_state->value);
    // Only 'de' is published.
    $this->assertEquals('published', $entity->getTranslation('de')->moderation_state->value);
  }

  /**
   * Checks and runs any ready transitions.
   *
   * @param \Drupal\scheduled_transitions\Entity\ScheduledTransitionInterface $scheduledTransition
   *   A scheduled transition.
   */
  protected function runTransition(ScheduledTransitionInterface $scheduledTransition): void {
    $runner = $this->container->get('scheduled_transitions.runner');
    $runner->runTransition($scheduledTransition);
  }

  /**
   * Gets logs from buffer and cleans out buffer.
   *
   * Reconstructs logs into plain strings.
   *
   * @param array|null $logBuffer
   *   A log buffer from getLogBuffer, or provide an existing value fetched from
   *   getLogBuffer. This is a workaround for the logger clearing values on
   *   call.
   *
   * @return array
   *   Logs from buffer, where values are an array with keys: severity, message.
   */
  protected function getLogs(?array $logBuffer = NULL): array {
    $logs = array_map(function (array $log) {
      [$severity, $message, $context] = $log;
      return [
        'severity' => $severity,
        'message' => str_replace(array_keys($context), array_values($context), $message),
      ];
    }, $logBuffer ?? $this->getLogBuffer());
    return array_values($logs);
  }

  /**
   * Gets logs from buffer and cleans out buffer.
   *
   * @array
   *   Logs from buffer, where values are an array with keys: severity, message.
   */
  protected function getLogBuffer(): array {
    return $this->container->get($this->testLoggerServiceName)->cleanLogs();
  }

  /**
   * Get revision IDs for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   *
   * @return int[]
   *   Revision IDs.
   */
  protected function getRevisionIds(EntityInterface $entity): array {
    $entityTypeId = $entity->getEntityTypeId();
    $entityDefinition = \Drupal::entityTypeManager()->getDefinition($entityTypeId);
    $entityStorage = \Drupal::entityTypeManager()->getStorage($entityTypeId);

    /** @var int[] $ids */
    $ids = $entityStorage->getQuery()
      ->allRevisions()
      ->condition($entityDefinition->getKey('id'), $entity->id())
      ->execute();
    return array_keys($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    $container
      ->register($this->testLoggerServiceName, BufferingLogger::class)
      ->addTag('logger');
  }

}
