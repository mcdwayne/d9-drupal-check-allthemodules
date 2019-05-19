<?php

namespace Drupal\tagadelic\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Language\LanguageInterface;

/**
 * Tests for displaying tagadelic page.
 *
 * @group tagadelic
 */
class TagadelicAdminTest extends WebTestBase {

  /**
   * A user with permission to access the administrative toolbar.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A n array of vocabularies.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $vocabularies;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'block', 'menu_ui', 'user', 'taxonomy', 'toolbar', 'tagadelic');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $perms = array(
      'access toolbar',
      'access administration pages',
      'administer site configuration',
      'bypass node access',
      'administer themes',
      'administer nodes',
      'access content overview',
      'administer blocks',
      'administer menu',
      'administer modules',
      'administer permissions',
      'administer users',
      'access user profiles',
      'administer taxonomy',
    );

    // Create an administrative user and log it in.
    $this->adminUser = $this->drupalCreateUser($perms);

    $this->drupalLogin($this->adminUser);

    $this->vocabularies = array();

    $vocabulary1 = Vocabulary::create(array(
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => Unicode::strtolower($this->randomMachineName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'weight' => mt_rand(0, 10),
    ));
    $vocabulary1->save();
    $this->vocabularies[] = $vocabulary1;

    $vocabulary2 = Vocabulary::create(array(
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => Unicode::strtolower($this->randomMachineName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'weight' => mt_rand(0, 10),
    ));
    $vocabulary2->save();
    $this->vocabularies[] = $vocabulary2;
  }

  /**
   * Test all vocabularies appear on admin page.
   */
  function testAllVocabulariesLoaded() {
    $this->drupalGet('admin/structure/tagadelic');
    $this->assertResponse(200);
    $this->assertRaw('Tag Cloud');

    foreach($this->vocabularies as $vocabulary) {
      $this->assertRaw($vocabulary->get('name'));
    }
  }
}
