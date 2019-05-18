<?php

namespace Drupal\Tests\commerce_migrate\Kernel;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Helper function to test migrations.
 */
trait CommerceMigrateCoreTestTrait {

  /**
   * The cached taxonomy tree items, keyed by vid and tid.
   *
   * @var array
   */
  protected $treeData = [];

  /**
   * Tests a single file entity.
   *
   * @param int $id
   *   The file ID.
   * @param string $name
   *   The expected file name.
   * @param string $uri
   *   The expected URI.
   * @param string $mime
   *   The expected MIME type.
   * @param int $size
   *   The expected file size.
   * @param int $uid
   *   The expected owner ID.
   */
  protected function assertFileEntity($id, $name, $uri, $mime, $size, $uid) {
    /** @var \Drupal\file\FileInterface $file */
    $file = File::load($id);
    $this->assertTrue($file instanceof FileInterface);
    $this->assertSame($name, $file->getFilename());
    $this->assertSame($uri, $file->getFileUri());
    $this->assertTrue(file_exists($uri));
    $this->assertSame($mime, $file->getMimeType());
    $this->assertSame($size, $file->getSize());
    $this->assertTrue($file->isPermanent());
    $this->assertSame($uid, $file->getOwnerId());
  }

  /**
   * Assert that a term is present in the tree storage, with the right parents.
   *
   * @param string $vid
   *   Vocabulary ID.
   * @param int $tid
   *   ID of the term to check.
   * @param array $parent_ids
   *   The expected parent term IDs.
   */
  protected function assertHierarchy($vid, $tid, array $parent_ids) {
    if (!isset($this->treeData[$vid])) {
      $tree = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadTree($vid);
      $this->treeData[$vid] = [];
      foreach ($tree as $item) {
        $this->treeData[$vid][$item->tid] = $item;
      }
    }

    $this->assertArrayHasKey($tid, $this->treeData[$vid], "Term $tid exists in taxonomy tree");
    $term = $this->treeData[$vid][$tid];
    $this->assertEquals($parent_ids, array_filter($term->parents), "Term $tid has correct parents in taxonomy tree");
  }

  /**
   * Validate a migrated term contains the expected values.
   *
   * @param string $id
   *   Entity ID to load and check.
   * @param string $expected_label
   *   The label the migrated entity should have.
   * @param string $expected_vid
   *   The parent vocabulary the migrated entity should have.
   * @param string $expected_description
   *   The description the migrated entity should have.
   * @param string $expected_format
   *   The format the migrated entity should have.
   * @param int $expected_weight
   *   The weight the migrated entity should have.
   * @param array $expected_parents
   *   The parent terms the migrated entity should have.
   */
  protected function assertTermEntity($id, $expected_label, $expected_vid, $expected_description = '', $expected_format = NULL, $expected_weight = 0, array $expected_parents = []) {
    /** @var \Drupal\taxonomy\TermInterface $entity */
    $entity = Term::load($id);
    $this->assertInstanceOf(TermInterface::class, $entity);
    $this->assertEquals($expected_label, $entity->label());
    $this->assertEquals($expected_vid, $entity->bundle());
    $this->assertEquals($expected_description, $entity->getDescription());
    $this->assertEquals($expected_format, $entity->getFormat());
    $this->assertEquals($expected_weight, $entity->getWeight());
    // TODO: https://www.drupal.org/project/commerce_migrate/issues/2976125
    // Remove this hack after 8.6 lands.
    $parent_ids = array_keys(\Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadParents($id));
    $this->assertEquals($expected_parents, $parent_ids);
    $this->assertHierarchy($expected_vid, $id, $expected_parents);
  }

  /**
   * Validate a migrated vocabulary contains the expected values.
   *
   * @param string $id
   *   Entity ID to load and check.
   * @param string $expected_label
   *   The label the migrated entity should have.
   * @param string $expected_description
   *   The description the migrated entity should have.
   * @param string $expected_hierarchy
   *   The hierarchy setting the migrated entity should have.
   * @param string $expected_weight
   *   The weight the migrated entity should have.
   */
  protected function assertVocabularyEntity($id, $expected_label, $expected_description, $expected_hierarchy, $expected_weight) {
    /** @var \Drupal\taxonomy\VocabularyInterface $entity */
    $entity = Vocabulary::load($id);
    $this->assertTrue($entity instanceof VocabularyInterface);
    $this->assertSame($expected_label, $entity->label());
    $this->assertSame($expected_description, $entity->getDescription());
    $this->assertSame($expected_weight, $entity->get('weight'));
  }

  /**
   * Asserts various aspects of a user account.
   *
   * @param string $id
   *   The user ID.
   * @param string $label
   *   The username.
   * @param string $mail
   *   The user's email address.
   * @param string $password
   *   The password for this user.
   * @param int $login
   *   The last login time.
   * @param bool $blocked
   *   Whether or not the account is blocked.
   * @param string $langcode
   *   The user account's language code.
   * @param string $timezone
   *   The user account's timezone name.
   * @param string $init
   *   The user's initial email address.
   * @param string[] $roles
   *   Role IDs the user account is expected to have.
   */
  protected function assertUserEntity($id, $label, $mail, $password, $login, $blocked, $langcode, $timezone, $init, array $roles) {
    /** @var \Drupal\user\UserInterface $user */
    $user = User::load($id);
    $this->assertTrue($user instanceof UserInterface);
    $this->assertSame($label, $user->label());
    $this->assertSame($mail, $user->getEmail());
    $this->assertSame($password, $user->getPassword());
    $this->assertSame($login, $user->getLastLoginTime());
    $this->assertNotSame($blocked, $user->isBlocked());

    // Ensure the user's langcode, preferred_langcode and
    // preferred_admin_langcode are valid.
    // $user->getPreferredLangcode() might fallback to default language if the
    // user preferred language is not configured on the site. We just want to
    // test if the value was imported correctly.
    $language_manager = $this->container->get('language_manager');
    $default_langcode = $language_manager->getDefaultLanguage()->getId();
    if ($langcode == '') {
      $this->assertSame('en', $user->langcode->value);
      $this->assertSame($default_langcode, $user->preferred_langcode->value);
      $this->assertSame($default_langcode, $user->preferred_admin_langcode->value);
    }
    elseif ($language_manager->getLanguage($langcode) === NULL) {
      $this->assertSame($default_langcode, $user->langcode->value);
      $this->assertSame($default_langcode, $user->preferred_langcode->value);
      $this->assertSame($default_langcode, $user->preferred_admin_langcode->value);
    }
    else {
      $this->assertSame($langcode, $user->langcode->value);
      $this->assertSame($langcode, $user->preferred_langcode->value);
      $this->assertSame($langcode, $user->preferred_admin_langcode->value);
    }

    $this->assertSame($timezone, $user->getTimeZone());
    $this->assertSame($init, $user->getInitialEmail());
    $this->assertSame($roles, $user->getRoles());
  }

}
