<?php
/**
 * @file
 * Tests for Taxonews module.
 *
 * @copyright (c) 2009-2013 Ouest Systèmes Informatiques (OSInet)
 * @author Frédéric G. MARAND <fgm@osinet.fr>
 * @license Licensed under the CeCILL 2.0 and General Public License version 2 and later
 * @link http://www.cecill.info/licences/Licence_CeCILL_V2-en.html @endlink
 * @link http://drupal.org/project/ThemeTests.php @endlink
 * @since Version DRUPAL-6--1.0
 *
 * @link http://drupal.org/node/325974 @endlink
 *
 * Note: _taxonews_form_rerouter() has nothing to test.
 *
 * @todo Invent functional tests able to go wrong when unit tests succeed.
 */

namespace Drupal\taxonews\Tests;

use Drupal\Core\Language\Language;

use Drupal\taxonews\Taxonews;
use Drupal\taxonomy\Plugin\Core\Entity\Term;
use Drupal\taxonomy\Tests\TaxonomyTestBase;

/**
 * Test theme functions
 *
 * @todo FIXME
 */
class ThemeTests extends TaxonomyTestBase {

  public static $group;

  /**
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  public $vocabulary;

  public static function getInfo() {
    $ret = array(
      'name'        => t('Theme'),
      'description' => t('Developer-type tests for Taxonews default theme'),
      'group'       => t('Taxonews'),
    );
    return $ret;
  }

  // Only way to initialize static properties to non constant content
  public function __construct($test_id = NULL) {
    parent::__construct($test_id);
    self::$group = t('Theme');
  }

  public function setUp($deps = NULL) {
    parent::setUp('taxonews');
    $this->vocabulary = $this->createVocabulary();
  }

  /**
   * Just how do you test a theme ? Here we just check it returns a string,
   * but this means we need to have a valid block.
   *
   * @return void
   */
  public function testBlockView() {
    // 1. set the Taxonews vocabulary to the default vocabulary
    variable_set(Taxonews::VAR_VOCABULARY, 1);

    // 2. create a term within it
    $term = $this->createTerm($this->vocabulary);
    $tid = intval($term->id());
    $this->assertTrue(is_a($term, 'Drupal\\taxonomy\\Plugin\\Core\\Entity\\Term') && $tid > 0, t('Term created.'), self::$group);

    // 3. create an 'article' node bearing the term. Only bundle carrying tags by default
    $settings = array(
      'type'          => 'article',
      'field_tags' => array(Language::LANGCODE_NOT_SPECIFIED => array(array('tid' => $tid))),
      'title'         => $this->randomName(8),
    );
    $original_node = $this->drupalCreateNode($settings);
    // $this->pass(var_export($original_node, true), self::$group);

    // 5. Check the theme function, not depending on actual block content
    $delta = $tid;
    $item = $this->randomName();
    $items = array(
      1 => array('link' => $item),
      2 => array('link' => 'foo'),
      3 => array('link' => 'bar'));

    // We do not use theme('...') because it doesn't currently work in Simpletest anyway.
    // Should be fixed in D7 at some point.
    $path = drupal_get_path('module', 'taxonews');
    $variables = array(
      'delta'    => $delta,
      'ar_items' => $items,
      'tid'      => $delta,
      );
    template_preprocess_taxonews_block_view($variables);
    // Cannot use module_load_include ?
    require_once DRUPAL_ROOT . '/core/themes/engines/phptemplate/phptemplate.engine';
    $block = \phptemplate_render_template($path . '/taxonews-block-view.tpl.php', $variables);
//    $this->pass(var_export($items, true));
//    $this->pass(var_export($variables, true));
//    $this->pass(var_export($block, true));
    $this->assertTrue(is_string($block),
      t('Theme returns a string.'), self::$group);
    $this->assertTrue(strpos($block, $item) !== FALSE, t('Content pattern found in string.'), self::$group);
  }
}
