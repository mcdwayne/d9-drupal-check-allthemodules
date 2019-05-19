<?php

namespace Drupal\Tests\wbm2cm\Kernel\Migration;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\wbm2cm\Kernel\MigrationTestTrait;
use Drupal\workbench_moderation\Entity\ModerationState;

/**
 * Tests the save-clear-restore migration flow for a single entity type.
 */
abstract class TestBase extends KernelTestBase {

  use MigrationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'language',
    'migrate',
    'options',
    'system',
    'user',
    'views',
    'wbm2cm',
    'workbench_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    ModerationState::create([
      'id' => 'draft',
      'label' => $this->randomMachineName(),
      'published' => FALSE,
      'default_revision' => FALSE,
    ])->save();

    ModerationState::create([
      'id' => 'review',
      'label' => $this->randomMachineName(),
      'published' => FALSE,
      'default_revision' => FALSE,
    ])->save();

    ModerationState::create([
      'id' => 'published',
      'label' => $this->randomMachineName(),
      'published' => TRUE,
      'default_revision' => TRUE,
    ])->save();

    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('hu')->save();

    $this->prepareDatabase();
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareDatabase() {
    // TODO: Put this bullshit in the source plugin configuration.
    $db = $this->getDatabaseConnectionInfo();
    $db['default']['prefix']['default'] = $this->getDatabasePrefix();
    Database::addConnectionInfo('migrate', 'default', $db['default']);
  }

  /**
   * Tests that moderation states are safely saved.
   *
   * @param array $expectations
   *   (optional) The expectations to assert. See ::createRevisions() for info.
   */
  public function testSave(array $expectations = NULL) {
    $expectations = $expectations ?: $this->createRevisions();

    $entity_type = $this->storage->getEntityType();
    $id_map = $this->execute($entity_type->id(), 'save')->getIdMap();

    // Assert that all translations of all revisions were imported.
    $reduce = function ($total, array $languages) {
      return $total + count($languages);
    };
    $expected_count = array_reduce($expectations, $reduce, 0);
    $this->assertSame($expected_count, (int) $id_map->importedCount());

    $keys = $entity_type->getKeys();

    foreach ($expectations as $vid => $languages) {
      foreach ($languages as $language => $expected_state) {
        $lookup = [
          $keys['revision'] => $vid,
          $keys['langcode'] => $language,
        ];
        $actual_state = $id_map->lookupDestinationIds($lookup);
        $this->assertEquals($expected_state, $actual_state[0][0]);
      }
    }
  }

  /**
   * Tests that moderation states are correctly cleared.
   *
   * @param array $expectations
   *   (optional) The expectations to assert. See ::createRevisions() for info.
   */
  public function testClear(array $expectations = NULL) {
    $expectations = $expectations ?: $this->createRevisions();

    $this->testSave($expectations);
    $this->execute($this->storage->getEntityTypeId(), 'clear');

    foreach ($expectations as $vid => $languages) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $revision */
      $revision = $this->storage->loadRevision($vid);

      foreach (array_keys($languages) as $language) {
        $this->assertTrue($revision->getTranslation($language)->get('moderation_state')->isEmpty());
      }
    }
  }

  /**
   * Tests that moderation states are properly restored.
   */
  public function testRestore() {
    $expectations = $this->createRevisions();

    $this->testClear($expectations);
    $this->execute($this->storage->getEntityTypeId(), 'restore');

    foreach ($expectations as $vid => $languages) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $revision */
      $revision = $this->storage->loadRevision($vid);

      foreach ($languages as $language => $expected_state) {
        $this->assertEquals($expected_state, $revision->getTranslation($language)->moderation_state->target_id);
      }
    }
  }

}
