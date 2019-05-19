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

use Drupal\Core\Language\Language;
use Drupal\taxonews\Taxonews;
use Drupal\taxonomy\Tests\TaxonomyTestBase;

/**
 * Unit tests for the Taxonews class and global functions in taxonews.module
 */
class ClassTests extends TaxonomyTestBase {

  public static $group;

  /**
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  public $vocabulary;


  // Only way to initialize static properties to non constant content
  public function __construct($test_id = NULL) {
    parent::__construct($test_id);
    self::$group = t('Taxonews');
  }

  /**
   *  Declare test group to simpletest
   *
   * @return array
   */
  public static function getInfo() {
    $ret = array(
      'name'        => t('Class'),
      'description' => t('Developer-type tests for Taxonews class'),
      'group'       => t('Taxonews'),
    );
    return $ret;
  }

  public function setUp() {
    parent::setUp('taxonews');
    $this->vocabulary = $this->createVocabulary();
  }

  /**
   * Helper function: create a vocabulary
   *
   * @param string $name
   * @return array
   */
  public function d7createVocabulary($name = NULL) {
    if (empty($name)) {
      $name = $this->randomName();
    }

    $vocabulary = new stdClass();
    $vocabulary->name = $name;
    $vocabulary->machine_name = $name;
    $status = taxonomy_vocabulary_save($vocabulary);

    $ret = array(
      'status' => $status,
      'vid'    => $vocabulary->vid,
      'name'   => $name,
    );
  return $ret;
  }

  /**
   * Make sure that Taxonews::adminSettings returns a system settings form
   *
   * @return void
   */
  public function testAdminSettings() {
    $form = Taxonews::adminSettings();
    $this->assertTrue(is_array($form)
      && array_key_exists('#submit', $form)
      && in_array('system_settings_form_submit', $form['#submit'])
      && array_key_exists('#theme', $form)
      && ($form['#theme'] == 'system_settings_form'),
      t('adminSettings() looks like a system settings form.'), self::$group);
  }

  /**
   * Test whether an archive exists when we know it should
   */
  public function testArchiveExistsTrue() {
    // 1. Create a term in the predefined Tags vocabulary, vid 1, field field_tags
    $term = entity_create('taxonomy_term', array(
      'name' => 'test term name',
      'description' => 'test term description',
      'vid' => $this->vocabulary->vid,
    ));
    $status = $term->save();
    $tid = $term->id();
    $this->assertEqual($status, SAVED_NEW, t('Term @tid created in default vocabulary.', array('@tid' => $tid)), self::$group);

    // 2. Create a node bearing it. "article" is the only bundle bearing a taxonomy instance by default
    $settings = array(
      'type'          => 'article',
      'field_tags' => array(Language::LANGCODE_NOT_SPECIFIED => array(array('tid' => $tid))),
    );
    $node = $this->drupalCreateNode($settings);
    $this->assertNotNull($node->nid, t('Node created with taxonomy field.'), self::$group);

    // 4. Does archiveExists() see it ?
    $status = Taxonews::archiveExists($tid);
    $this->assertTrue($status, t('Archive exists when it should.'), self::$group);
  }

  /**
   * Test whether an archive exists when we know it should not
   */
  public function testArchiveExistsFalse() {
    // 1. Create a term in the predefined Tags vocabulary, vid 1, field field_tags
    $term = entity_create('taxonomy_term', array(
      'name' => 'test term name',
      'description' => 'test term description',
      'vid' => $this->vocabulary->vid,
    ));
    $status = $term->save();
    $tid = $term->id();
    $this->assertEqual($status, SAVED_NEW, t('Term @tid created in default vocabulary.', array('@tid' => $tid)), self::$group);

    // 3. no existing node bears the term, create a node not bearing it either
    $settings = array(); // No taxonomy
    $this->drupalCreateNode($settings);

    // 4. Does archiveExists() see it ?
    $status = Taxonews::archiveExists($tid);
    $this->assertFalse($status, t('Archive does not exist when it should not.'), self::$group);
  }

  /**
   * Test whether blockConfigure returns a configure form.
   *
   * @return void
   */
  public function testBlockConfigure() {
    $form = Taxonews::blockConfigure(0);
    $this->assertTrue(is_array($form),
      t('blockConfigure() is an array.'), self::$group);
    $this->assertTrue(empty($form),
      t('No setting offered on config form for invalid delta.'), self::$group);
  }

  /**
   * Test whether we obtain a list of blocks matching the chosen terms
   *
   * @return void
   */
  public function testBlockInfo() {
    // 1. set the Taxonews vocabulary to the default Tags vocabulary
    variable_set(Taxonews::VAR_VOCABULARY, 1);

    // 2. create a random number of terms within it
    $max_tids = 50;
    $count = mt_rand(1, $max_tids);
    for ($i = 0 ; $i < $count ; $i++) {
      $term = entity_create('taxonomy_term', array(
        'name' => $this->randomName(),
        'description' => $this->randomName(),
      'vid' => $this->vocabulary->vid,
      ));
      $status = $term->save();
      $tid = $term->id();
      $ar_terms[$tid] = $term;
    }
    $this->assertEqual(count($ar_terms), $count, t('@count terms created', array('@count' => $count)), self::$group);

    // 3. make sure all the blocks are listed
    variable_set(Taxonews::VAR_SHOW_EMPTY, TRUE); // all these blocks are empty
    $ar_blocks = Taxonews::blockInfo();
    $this->assertTrue(is_array($ar_blocks) && count($ar_blocks) == $count,
      t('blockInfo returns one block per term.'), self::$group);

    // 4. make sure all blocks have proper content and caching mode
    $success = TRUE;
    foreach ($ar_blocks as $block) {
      if (($block['cache'] != DRUPAL_CACHE_GLOBAL) || empty($block['info'])) {
        $success = FALSE;
        break;
      }
    }
    $this->assertTrue($success, t('Blocks have proper info and caching values.'), self::$group);
  }

/**
   * Test whether the empty message is actually stored for an invalid delta.
   *
   * @todo FIXME
   * @return void
   */
  public function testBlockSave() {
    $delta = $this->randomName(); // deltas are numeric, so this won't ever be a valid delta

    $configured_empty = $this->randomName();
    $edit = array(Taxonews::VAR_EMPTY_MESSAGES => $configured_empty);
    Taxonews::blockSave($delta, $edit);
    $messages = $this->container->get('config')->get('taxonews.settings')->get(Taxonews::VAR_EMPTY_MESSAGES); //, NULL);
    $this->assertNull($messages, t('Empty value is not saved for invalid deltas.'), self::$group);
  }

  /**
   * Test block content for an invalid delta.
   *
   * @return void
   */
  public function testBlockViewInvalidDelta() {
    $block = Taxonews::blockView(0);
    $this->assertTrue(is_array($block), t('Invalid block is an array.'), self::$group);

    $this->assertTrue(array_key_exists('subject', $block) && empty($block['subject']),
      t('Subject of invalid block is empty.'), self::$group);

    // Since invalid block cannot be configured, its content must be the default
    // empty content, i.e. NULL
    $this->assertTrue($block['content'] === NULL,
      t('Content of invalid block is null.'), self::$group);
  }

  /**
   * Test block content for a known valid delta
   *
   * @return void
   */
  public function testBlockViewValidDelta() {
    // 1. set the Taxonews vocabulary to the default vocabulary
    variable_set(Taxonews::VAR_VOCABULARY, 1);

    // 2. create a term within it
    $term = entity_create('taxonomy_term', array(
      'name' => 'testBlockViewValidDelta name',
      'description' => 'testBlockViewValidDelta description',
      'vid' => $this->vocabulary->vid,
    ));
    $status = $term->save();
    $tid = $term->id();
    $name = $term->label();
    $this->assertEqual($status, SAVED_NEW, t('Term created: @tid = "@name"',
      array('@tid' => $tid, '@name' => $name)), self::$group);

    // 3. create an 'article' node bearing the term. Only bundle carrying tags by default
    $settings = array(
      'type'          => 'article',
      'field_tags' => array(Language::LANGCODE_NOT_SPECIFIED => array(array('tid' => $tid))),
      'title'         => $this->randomName(8),
    );
    $original_node = $this->drupalCreateNode($settings);
    // debug($original_node);
    // $this->pass(var_export($original_node, true), self::$group);

    // 5. Check
    $ar_nids = taxonomy_select_nodes($tid);
    // debug($ar_nids, 'dump nodes');
    $this->assertTrue(in_array($original_node->nid, $ar_nids),
      t('Just created node matches delta.'), self::$group);

    $block = Taxonews::blockView($tid);
    $this->assertTrue(is_array($block) && array_key_exists('subject', $block) && array_key_exists('content', $block),
      t('Block is well-formed.'), self::$group);
    $this->assertEqual($block['subject'], $name,
      t('Block subjet matches term name: @name', array('@name' => $name)),
      self::$group);

    /**
     * The content test is theme-dependent, a better one should be probably be
     * designed if possible.
     * 2009-07-13 But anyway, Simpletest, at this date, just does not
     * appear to support theme(). Content testing for block views has
     * therefore be moved to TaxonewsThemeUnitTest::testBlockView, and could
     * be completed by functional testing.
     */
    /*
    $this->pass(var_export($block, true));
    $this->pass(var_export($settings, true));
    $this->assertTrue(strpos($block['content'], $settings['title']) !== FALSE,
      t('Newly created node is found in block.'), self::$group);
     */
  }

  /**
   * Test whether empty messages are correctly returned.
   *
   * - return with no deltas should be an array
   * - invalid deltas should not have a message
   * - valid deltas should have one
   *
   * Since Taxonews uses tids for its deltas, any non-numeric delta is invalid.
   *
   * @todo D7 port
   * @return void
   */
  public function testGetEmptyMessages() {
    // 1. set the Taxonews vocabulary to the default vocabulary
    variable_set(Taxonews::VAR_VOCABULARY, 1);

    // 2. create a term within it
    $term = entity_create('taxonomy_term', array(
      'name' => 'testGetEmptyMessages name',
      'description' => 'testGetEmptyMessages description',
      'vid' => $this->vocabulary->vid,
    ));
    $status = $term->save();
    $tid = $term->id();
    $name = $term->label();
    $this->assertEqual($status, SAVED_NEW, t('Term created: @tid = "@name"',
      array('@tid' => $tid, '@name' => $name)), self::$group);

    // 3. configure an empty text for its block
    $delta = $tid;
    $configured_empty = $this->randomName();
    $edit = array(Taxonews::VAR_EMPTY_MESSAGES => $configured_empty);
    Taxonews::blockSave($delta, $edit);

    // 4. check whether the list, which starts empty, now includes that text
    $messages = Taxonews::getEmptyMessages();
    $this->assertTrue(is_array($messages) && array_key_exists($delta, $messages),
      t('Valid key in empty messages for random delta.'), self::$group);
    $this->assertEqual($configured_empty, isset($messages[$delta]) ? $messages[$delta] : NULL,
      t('Valid value in empty messages for key matching random delta.'), self::$group);

    // 5. check whether the value is also returned by delta
    $this->assertEqual($configured_empty, Taxonews::getEmptyMessages($delta),
      t('Valid value returned by emptyMessages($delta).'), self::$group);
  }

  /**
   * Test our lightweight get-terms function.
   *
   * @todo D7 port
   * @return void
   */
  public function testGetTerms() {
    // 1. set the Taxonews vocabulary to the default tags vocabulary
    variable_set(Taxonews::VAR_VOCABULARY, 1);

    // 3. create a random number of terms within it
    $max_tids = 50;
    $count = mt_rand(1, $max_tids);
    for ($i = 0; $i < $count ; $i++) {
      $term = entity_create('taxonomy_term', array(
        'name' => $this->randomName(),
        'description' => $this->randomName(),
        'vid' => $this->vocabulary->vid,
      ));
      $status = $term->save();
      $tid = $term->id();
      $ar_terms[$tid] = $term;
    }
    $this->assertEqual(count($ar_terms), $count, t('@count terms created', array('@count' => $count)), self::$group);

    // 4. check whether the terms just set are the same one returned by getTerms
    $got_terms = Taxonews::getTerms(TRUE); // Reset to avoid retrieving data from another test
    $success = is_array($got_terms) && count($got_terms) == $count;
    $this->assertTrue($success, t('Well-formed terms array.'), self::$group);
    // $this->pass(print_r($got_terms, true));
    foreach ($ar_terms as $tid => $term) {
      if (!array_key_exists($tid, $got_terms)) {
        $success = FALSE;
        break;
      }
      if ($term->name != $got_terms[$tid]->name) {
        $this->pass(t('Term name mismatch for tid @tid: passed @original, retrieved @got',
          array('@tid' => $tid, '@original' => $term->name, '@got' => $got_terms[$tid]->name)));
        $success = FALSE;
        break;
      }
    }
    $this->assertTrue($success, t('Accurate terms returned.'), self::$group);
  }
}
