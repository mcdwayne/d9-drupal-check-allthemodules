<?php

namespace Drupal\Tests\language_neutral_aliases\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language_neutral_aliases\LanguageNeutralAliasesStorage;

/**
 * Test language neutral aliases.
 *
 * @group language_neutral_aliases
 */
class LanguageNeutralAliasesStorageTest extends KernelTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['language_neutral_aliases', 'path'];

  /**
   * Setup test.
   */
  protected function setUp() {
    parent::setUp();
    $database = $this->container->get('database');

    // Create some test data.
    $storage = $this->container->get('path.alias_storage');
    $storage->save('/node/1', '/path/first');
    $storage->save('/node/2', '/path/second');
    $storage->save('/node/3', '/path/third');

    // Change the language of some aliases.
    $database->update(LanguageNeutralAliasesStorage::TABLE)
      ->fields(['langcode' => 'de'])
      ->condition('source', '/node/1')
      ->execute();
    $database->update(LanguageNeutralAliasesStorage::TABLE)
      ->fields(['langcode' => 'da'])
      ->condition('source', '/node/2')
      ->execute();
  }

  /**
   * Get pid of an alias.
   *
   * Used to get pids of aliases not accessible through the storage class, due
   * to non-neutral language.
   */
  protected function getPid($source) {
    return $this->container->get('database')
      ->select(LanguageNeutralAliasesStorage::TABLE, 'u')
      ->fields('u', ['pid'])
      ->condition('source', $source)
      ->execute()
      ->fetchField();
  }

  /**
   * Return all aliases in database unfiltered.
   */
  public function getAllUnfiltered($fields) {
    $query = $this->container->get('database')
      ->select(LanguageNeutralAliasesStorage::TABLE, 'u')
      ->fields('u', $fields)
      ->orderBy('source')
      ->execute();

    return count($fields) > 1 ? $query->fetchAllAssoc() : $query->fetchCol();
  }

  /**
   * Test that new aliases gets saved with language neutral.
   */
  public function testSave() {
    $storage = $this->container->get('path.alias_storage');
    $storage->save('/node/4', '/path/fourth', 'de');

    $expected = [
      'pid' => '4',
      'source' => '/node/4',
      'alias' => '/path/fourth',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ];
    $actual = $storage->load(['source' => '/node/4']);
    $this->assertEqual($expected, $actual);

    // Ensure that non-neutral language aliases is not overwritten.
    $storage->save('/node/1', '/path/fifth', 'de', $this->getPid('/node/1'));
    $expected = [
      'pid' => '5',
      'source' => '/node/1',
      'alias' => '/path/fifth',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ];
    $actual = $storage->load(['source' => '/node/1']);
    $this->assertEqual($expected, $actual);

    // Ensure that language neutral aliases can be updated.
    $storage->save('/node/4', '/path/sixth', LanguageInterface::LANGCODE_NOT_SPECIFIED, $this->getPid('/node/4'));
    $expected = [
      'pid' => '4',
      'source' => '/node/4',
      'alias' => '/path/sixth',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ];
    $actual = $storage->load(['source' => '/node/4']);
  }

  /**
   * Test that load() only returns language neutral aliases.
   */
  public function testLoad() {
    $storage = $this->container->get('path.alias_storage');
    $this->assertFalse($storage->load(['source' => '/node/1']));
    $this->assertFalse($storage->load(['source' => '/node/2']));
    $this->assertNotFalse($storage->load(['source' => '/node/3']));
    // Check that specified language is ignored.
    $this->assertNotFalse($storage->load(['source' => '/node/3', 'langcode' => 'de']));
  }

  /**
   * Test that delete() only deletes language neutral aliases.
   */
  public function testDelete() {
    $storage = $this->container->get('path.alias_storage');
    $storage->delete(['source' => '/node/1']);
    $storage->delete(['source' => '/node/3']);

    $this->assertEqual(['/node/1', '/node/2'], $this->getAllUnfiltered(['source']));
  }

  /**
   * Test that preloadPathAlias() only returns language neutral aliases.
   */
  public function testPreloadPathAlias() {
    $storage = $this->container->get('path.alias_storage');

    $this->assertEqual(
      ['/node/3' => '/path/third'],
      $storage->preloadPathAlias(['/node/1', '/node/3'], LanguageInterface::LANGCODE_NOT_SPECIFIED)
    );

    $this->assertEqual(
      ['/node/3' => '/path/third'],
      $storage->preloadPathAlias(['/node/1', '/node/3'], 'de')
    );
  }

  /**
   * Test that lookupPathAlias() only returns language neutral aliases.
   */
  public function testLookupPathAlias() {
    $storage = $this->container->get('path.alias_storage');

    $this->assertEqual(
      '/path/third',
      $storage->lookupPathAlias('/node/3', LanguageInterface::LANGCODE_NOT_SPECIFIED)
    );

    $this->assertEqual(
      '/path/third',
      $storage->lookupPathAlias('/node/3', 'de')
    );

    $this->assertFalse($storage->lookupPathAlias('/node/1', LanguageInterface::LANGCODE_NOT_SPECIFIED));

    $this->assertFalse($storage->lookupPathAlias('/node/1', 'de'));
  }

  /**
   * Test that lookupPathSource() only returns language neutral aliases.
   */
  public function testLookupPathSource() {
    $storage = $this->container->get('path.alias_storage');

    $this->assertEqual(
      '/node/3',
      $storage->lookupPathSource('/path/third', LanguageInterface::LANGCODE_NOT_SPECIFIED)
    );

    $this->assertEqual(
      '/node/3',
      $storage->lookupPathSource('/path/third', 'de')
    );

    $this->assertFalse($storage->lookupPathSource('/path/first', LanguageInterface::LANGCODE_NOT_SPECIFIED));

    $this->assertFalse($storage->lookupPathSource('/path/first', 'de'));
  }

  /**
   * Test that aliasExists() ignore non-language neutral aliases.
   */
  public function testAliasExists() {
    $storage = $this->container->get('path.alias_storage');
    $this->assertFalse($storage->aliasExists('/path/first', LanguageInterface::LANGCODE_NOT_SPECIFIED));
    $this->assertFalse($storage->aliasExists('/path/first', 'de'));

    $this->assertTrue($storage->aliasExists('/path/third', LanguageInterface::LANGCODE_NOT_SPECIFIED));
    $this->assertTrue($storage->aliasExists('/path/third', 'de'));
  }

  /**
   * Test that languageAliasExists() returns false.
   */
  public function testLanguageAliasExists() {
    $storage = $this->container->get('path.alias_storage');
    $this->assertFalse($storage->languageAliasExists());
  }

  /**
   * Test that getAliasesForAdminListing only lists language neutral aliases.
   */
  public function testGetAliasesForAdminListing() {
    $storage = $this->container->get('path.alias_storage');
    // If it filters out non-language neutral aliases, it should only return one
    // item.
    $this->assertCount(1, $storage->getAliasesForAdminListing([], ''));
  }

  /**
   * Test that pathHasMatchingAlias() returns only language neutral aliases.
   */
  public function testPathHasMatchingAlias() {
    $storage = $this->container->get('path.alias_storage');
    $this->assertFalse($storage->pathHasMatchingAlias('/node/1'));
    $this->assertTrue($storage->pathHasMatchingAlias('/node/3'));
  }

}
