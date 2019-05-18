<?php

/**
 * @file
 * Contains Drupal\feeds_xpathparser\Tests\XPathXMLParserTest.
 */

namespace Drupal\feeds_xpathparser\Tests;

use Drupal\feeds_xpathparser\WebTestBase;

/**
 * Test single feeds.
 */
class XPathXMLParserTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'XML Parser',
      'description' => 'Regression tests for Feeds XPath XML parser.',
      'group' => 'Feeds XPath Parser',
    );
  }

  /**
   * Run tests.
   */
  public function test() {
    $this->createImporterConfiguration('XPathXML', 'xpath_xml');

    $this->setPlugin('xpath_xml', 'parser', 'feeds_xpathparser_xml');
    $importer_url = self::FEEDS_BASE . '/xpath_xml/settings/parser';
    // Check help message.
    $this->drupalGet($importer_url);
    $this->assertText('No XPath mappings are defined.');

    $this->addMappings('xpath_xml', array(
      0 => array(
        'source' => 'xpathparser:0',
        'target' => 'title',
        'unique' => FALSE,
      ),
      1 => array(
        'source' => 'xpathparser:1',
        'target' => 'guid',
        'unique' => TRUE,
      ),
      2 => array(
        'source' => 'xpathparser:2',
        'target' => 'body',
      ),
    ));
    // Set importer default settings.
    $edit = array(
      'context' => '//entry',
      'sources[xpathparser:0]' => 'title',
      'sources[xpathparser:1]' => 'id',
      'sources[xpathparser:2]' => 'id',
      'allow_override' => TRUE,
    );
    $this->postAndCheck($importer_url, $edit, 'Save', 'Your changes have been saved.');

    // Test import.
    $path = $GLOBALS['base_url'] . '/' . drupal_get_path('module', 'feeds_xpathparser') . '/tests/';
    // We use an atom feed so that we can test that default namespaces are being
    // applied appropriately.
    $fid = $this->createFeed('xpath_xml', $path . 'sample_atom_feed.xml', 'XPathXML XPath XML Parser');
    $feed_edit_url = "feed/$fid/edit";
    $this->assertText('Created 3 nodes');

    // Import again, this verifies url field was mapped correctly.
    $this->feedImportItems($fid);
    $this->assertText('There are no new nodes');

    // Assert accuracy of aggregated content. I find humor in using our own
    // issue queue to run tests against.
    $this->drupalGet('node');
    $this->assertText('Atom-Powered Robots Run Amok');
    $this->assertText('My dog Jack is the best.');
    $this->assertText('Physics is cool.');

    // Test debugging.
    $edit = array(
      'parser[debug][xpathparser:0]' => TRUE,
    );
    $this->postAndCheck($feed_edit_url, $edit, 'Save', 'XPathXML XPath XML Parser has been updated.');
    $this->feedImportItems($fid);
    $this->assertText('&lt;title&gt;Atom-Powered Robots Run Amok&lt;/title&gt;');
    $this->assertText('&lt;title&gt;My dog Jack is the best.&lt;/title&gt;');
    $this->assertText('&lt;title&gt;Physics is cool.&lt;/title&gt;');
    $this->assertText('There are no new nodes.');

    // Turn debugging off.
    $edit = array(
      'parser[debug][xpathparser:0]' => FALSE,
    );
    $this->postAndCheck($feed_edit_url, $edit, 'Save', 'XPathXML XPath XML Parser has been updated.');

    // Check if update existing nodes works.
    $this->setSettings('xpath_xml', 'processor', array('update_existing' => 2));
    $edit = array(
      'fetcher[source]' => $path . 'sample_atom_feed_updated.xml',
    );
    $this->postAndCheck($feed_edit_url, $edit, 'Save', 'XPathXML XPath XML Parser has been updated.');
    $this->feedImportItems($fid);
    $this->assertText('Updated 1');
    $this->drupalGet('node');
    $this->assertText('Atom-Powered Robots Run Amok');
    $this->assertText('My dog Jack is the best.');
    $this->assertText('Physics is really cool.'); // The one that changed.
    $this->assertNoText('Physics is cool.'); // Make sure the old node is changed.
    // Be extra sure we updated.
    $this->drupalGet('node/3');
    $this->assertText('Physics is really cool.');

    // Check if replace existing nodes works.
    $this->setSettings('xpath_xml', 'processor', array('update_existing' => 1));
    $edit = array(
      'fetcher[source]' => $path . 'sample_atom_feed.xml',
    );
    $this->postAndCheck($feed_edit_url, $edit, 'Save', 'XPathXML XPath XML Parser has been updated.');
    $this->feedImportItems($fid);
    $this->assertText('Updated 1');
    $this->drupalGet('node');
    $this->assertText('Atom-Powered Robots Run Amok');
    $this->assertText('My dog Jack is the best.');
    $this->assertText('Physics is cool.'); // The one that changed.
    $this->assertNoText('Physics is really cool.'); // Make sure the old node is changed.
    // Be extra sure we updated.
    $this->drupalGet('node/3');
    $this->assertText('Physics is cool.');

    // Test that overriding default settings works.
    $edit = array(
      'parser[context]' => '/foo',
      'parser[sources][xpathparser:0]' => 'bar',
      'parser[sources][xpathparser:1]' => 'baz',
      'parser[sources][xpathparser:2]' => 'wee',
    );
    $this->postAndCheck($feed_edit_url, $edit, 'Save', 'XPathXML XPath XML Parser has been updated.');

    // Assert the we don't create an empty node when XPath values don't return anything.
    // That happened at one point.
    $this->feedImportItems($fid);
    $this->assertText('There are no new nodes.');

    // Test that validation works.
    $edit = array(
      'parser[context]' => 'sdf asf',
      'parser[sources][xpathparser:0]' => 'asdf[sadfas asdf]',
    );
    $this->drupalPost($feed_edit_url, $edit, 'Save');
    // Check for valid error messages.
    $this->assertText('There was an error with the XPath selector: Invalid expression');
    $this->assertText('There was an error with the XPath selector: Invalid predicate');
    // Make sure the fields are errored out correctly. I.e. we have red outlines.
    $this->assertFieldByXPath('//input[@id="edit-parser-context"][1]/@class', 'form-text required error');
    $this->assertFieldByXPath('//input[@id="edit-parser-sources-xpathparser0"][1]/@class', 'form-text error');

    // Put the values back so we can test inheritance if the form was changed
    // and then changed back.
    $edit = array(
      'parser[context]' => '//entry',
      'parser[sources][xpathparser:0]' => 'title',
      'parser[sources][xpathparser:1]' => 'id',
      'parser[sources][xpathparser:2]' => 'id',
    );
    $this->postAndCheck($feed_edit_url, $edit, 'Save', 'XPathXML XPath XML Parser has been updated.');

    // Change importer defaults.
    $edit = array(
      'context' => '//tr',
      'sources[xpathparser:0]' => 'booya',
      'sources[xpathparser:1]' => 'boyz',
      'sources[xpathparser:2]' => 'woot',
    );
    $this->postAndCheck($importer_url, $edit, 'Save', 'Your changes have been saved.');

    // Make sure the changes propigated.
    $this->drupalGet($feed_edit_url);
    $this->assertFieldByName('parser[context]', '//tr');
    $this->assertFieldByName('parser[sources][xpathparser:0]', 'booya');
    $this->assertFieldByName('parser[sources][xpathparser:1]', 'boyz');
    $this->assertFieldByName('parser[sources][xpathparser:2]', 'woot');
    // Check that our message comes out correct.
    $this->assertRaw('Field <strong>GUID</strong> is mandatory and considered unique: only one item per GUID value will be created.');

    // Check that allow_override works as expected.
    $this->setSettings('xpath_xml', 'parser', array('allow_override' => FALSE));
    $this->drupalGet($feed_edit_url);
    $this->assertNoText('XPath Parser Settings');
    $this->assertNoField('context');
  }

  /**
   * Test variable substitution.
   */
  public function testVariables() {
    $this->createImporterConfiguration();

    $this->setPlugin('syndication', 'parser', 'feeds_xpathparser_xml');
    $importer_url = self::FEEDS_BASE . '/syndication/settings/parser';
    $this->addMappings('syndication', array(
      0 => array(
        'source' => 'xpathparser:0',
        'target' => 'title',
        'unique' => FALSE,
      ),
      1 => array(
        'source' => 'xpathparser:1',
        'target' => 'guid',
        'unique' => TRUE,
      ),
      2 => array(
        'source' => 'xpathparser:2',
        'target' => 'body',
      ),
    ));
    // Set importer default settings.
    $edit = array(
      'context' => '//entry',
      'sources[xpathparser:0]' => 'title',
      'sources[xpathparser:1]' => 'id',
      'sources[xpathparser:2]' => 'link/@$title',
    );
    $this->postAndCheck($importer_url, $edit, 'Save', 'Your changes have been saved.');

    // Test import.
    $path = $GLOBALS['base_url'] . '/' . drupal_get_path('module', 'feeds_xpathparser') . '/tests/';
    // We use an atom feed so that we can test that default namespaces are being
    // applied appropriately.
    $fid = $this->createFeed('syndication', $path . 'rewrite_test.xml', 'Testing XPath XML Parser');
    $feed_edit_url = 'feed/' . $fid . '/edit';
    $this->assertText('Created 3 nodes');
    $this->drupalGet('node');
  }

}
