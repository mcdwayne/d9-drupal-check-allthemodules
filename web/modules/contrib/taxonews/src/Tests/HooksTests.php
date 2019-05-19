<?php
/**
 * @file
 * Tests for Taxonews module.
 *
 * @copyright (c) 2009-2013 Ouest Systèmes Informatiques (OSInet)
 * @author Frédéric G. MARAND <fgm@osinet.fr>
 * @license Licensed under the CeCILL 2.0 and General Public License version 2 and later
 * @link http://www.cecill.info/licences/Licence_CeCILL_V2-en.html @endlink
 * @link http://drupal.org/project/taxonews @endlink
 * @since Version DRUPAL-6--1.0
 *
 * @link http://drupal.org/node/325974 @endlink
 *
 * Note: _taxonews_form_rerouter() has nothing to test.
 *
 * @todo Invent functional tests able to go wrong when unit tests succeed.
 */

namespace Drupal\taxonews\Tests;

use Drupal\taxonomy\Tests\TaxonomyTestBase;

use Drupal\taxonews\Taxonews;

use Drupal\simpletest\WebTestBase;

/**
 * Test hook implementations.
 *
 * These are mostly well-formedness tests: actual content is tested in the
 * Class group.
 *
 * @todo FIXME
 */
class HooksTests extends TaxonomyTestBase {

    const MODULE = 'taxonews';

    /**
     * @var \Drupal\taxonomy\VocabularyInterface
     */
    public $vocabulary;

    public static $group;

    // Only way to initialize static properties to non constant content
    public function __construct($test_id = NULL) {
      parent::__construct($test_id);
      self::$group = t('Taxonews');
    }

    public static function getInfo() {
    $ret = array(
      'name'        => t('Hooks'),
      'description' => t('Developer-type tests for Taxonews hook implementations'),
      'group'       => t('Taxonews'),
    );
    return $ret;
  }

  public function setUp() {
    parent::setUp('taxonews');
    $this->vocabulary = $this->createVocabulary();
  }

  /**
   * Well-formedness tests for hook_block.
   *
   * WARNING: until 7.1.0-beta4, coder_review incorrectly triggers critical
   * errors about module_list() on each line containing module_invoke().
   * See issue #704032.
   *
   * The actual contents is tested in the Taxonews class group.
   * @return void
   */
  public function testHookBlock() {
    // 1. assign the default vocabulary to taxonews
    variable_set(Taxonews::VAR_VOCABULARY, array(1 => 1));

    // 2. create a term within it
    $term = $this->createTerm($this->vocabulary);
    $tid = $term->id();

    // 3. Test hook_block_list()
    self::$group = t('hook_block_list');
    $blocks_list = module_invoke(self::MODULE, 'block_info');
    $this->assertTrue(is_array($blocks_list), t('Blocks list is an array.'), self::$group);
    $this->assertTrue(count($blocks_list) == 1, t('Blocks list contains the proper number of entries'), self::$group);
    $this->assertTrue(array_keys($blocks_list) == array($tid), t('Blocks list is well-formed'), self::$group);

    // 4. Test hook_block_view()
    self::$group = t('hook_block_view');
    $block = module_invoke(self::MODULE, 'block_view', 0); // can never exist
    $this->assertNull($block['subject'], t('No block subject for invalid delta.'), self::$group);
    $this->assertNull($block['content'], t('No block content for invalid delta.'), self::$group);

    $block = module_invoke(self::MODULE, 'block_view', $tid); // should exist
    $this->assertNotNull($block['subject'], t('A block subject exists for a valid delta.'), self::$group);
    $this->assertNotNull($block['content'], t('A block content exists for a valid delta.'), self::$group);

    // 5. Test hook_block_configure()
    self::$group = t('hook_block_configure');
    $form = module_invoke(self::MODULE, 'block_configure', 0);
    $this->assertEqual($form, array(), t('Empty config form for invalid delta.'), self::$group);

    $form = module_invoke(self::MODULE, 'block_configure', $term->tid);
    $this->assertTrue(is_array($form) && array_key_exists('taxonews_empty_messages', $form)
      && is_array($form['taxonews_empty_messages']), t('Well-formed config form for valid delta'), self::$group);

    // 6. Test hook_block_save()
    self::$group = t('hook_block_save');
    $ret = module_invoke(self::MODULE, 'block_save', 0, array());
    $this->assertNull($ret, t('No returns from blockSave.'), self::$group);
  }

  /**
   * Well-formedness tests for hook_menu.
   *
   * @return void
   */
  public function testHookMenu() {
    $menu = module_invoke('taxonews', 'menu');
    $this->assertTrue(is_array($menu), t('Menu is an array.'), self::$group);
  }

  /**
   * Tests the H _menu() implementation.
   *
   * @return void
   */
  public function testHookHelp() {
    $module = 'taxonews';
    $section = 'admin/help#' . $module;
    $help = module_invoke($module, 'help', $section, arg());
    $this->assertTrue(is_string($help), t('Module description present.'), self::$group);

    $section = $this->randomName();
    $help = module_invoke($module, 'help', $section, arg());
    $this->assertNull($help,
      t('No help for invalid section "@section".', array('@section' => $section)), self::$group);
  }

  /**
   * Tests the H _theme() implementation for well-formedness.
   *
   * @return void
   */
  public function testHookTheme() {
    $theme = module_invoke(self::MODULE, 'theme');
    $this->assertTrue(is_array($theme), t('Theme info is an array.'), self::$group);
    foreach ($theme as $template => $items) {
      $this->assertTrue(is_array($items)
        && (array_key_exists('variables', $items) && is_array($items['variables']))
        || (array_key_exists('render element', $items) && !array_key_exists('variables', $items)),
        t('Theme info for @template has required parameters', array('@template' => $template)),
        self::$group);
    }
  }
}
