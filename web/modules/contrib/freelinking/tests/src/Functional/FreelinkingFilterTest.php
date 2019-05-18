<?php

namespace Drupal\Tests\freelinking\Functional;

use Drupal\file\Entity\File;

/**
 * Tests that freelinking filter is functional.
 *
 * @group freelinking
 */
class FreelinkingFilterTest extends FreelinkingBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var \Drupal\Core\File\FileSystemInterface $filesystem */
    $filesystem = $this->container->get('file_system');

    // Make sure that freelinking filter is activated.
    $this->updateFilterSettings();

    // Create a third freelinking page, which will not be freelinked.
    $this->drupalCreateNode(['type' => 'page', 'title' => t('Third page')]);

    // Upload Drupal logo to files directory to test file and image plugins.
    $root_path = $_SERVER['DOCUMENT_ROOT'];
    $image_path = $root_path . '/core/themes/bartik/logo.png';
    file_unmanaged_copy($image_path, 'public://logo.png');
    $image = File::create([
      'uri' => 'public://logo.png',
      'status' => FILE_STATUS_PERMANENT,
    ]);
    $image->save();
    $this->assertTrue(is_string($filesystem->realpath('public://logo.png')),
                      t('Image @image was saved successfully',
                      ['@image' => 'public://logo.png']));
  }

  /**
   * Tests all plugins.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testFreelinkingPlugins() {
    // Create node that will contain a sample of each plugin.
    $edit = [];
    $edit['title[0][value]'] = t('Testing all freelinking plugins');
    $edit['body[0][value]'] = $this->getNodeBodyValue();

    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $this->assertSession()
      ->pageTextContains(t('Basic page @title has been created.', ['@title' => $edit['title[0][value]']]));

    // Verify each freelink plugin.
    $this->assertSession()
      ->linkExists(t('First page'), 0, 'Generate default plugin (nodetitle) freelink.');
    $this->assertSession()
      ->linkExists(t('Second page'), 0, 'Generate Nodetitle freelink.');
    $this->assertSession()
      ->linkExists(t('Second page'), 0, 'Generate Nid freelink.');
    $this->assertSession()
      ->linkExists($this->privilegedUser->getAccountName(), 0, 'Generate User freelink.');

    $this->assertSession()
      ->linkByHrefExists('https://drupal.org/project/freelinking', 0, 'Generate Drupalproject freelink.');
    $this->assertSession()
      ->linkByHrefExists('https://drupal.org/node/1', 0, 'Generate Drupalorg freelink.');
    $this->assertSession()
      ->linkByHrefExists('/search/node?keys=test', 0, 'Generate Search freelink.');
    // Query parameters are not guaranteed to be in a specific order based on
    // PHP version changes. This should be covered better by
    // \Drupal\Tests\freelinking\Unit\Plugin\freelinking\GoogleSearchTest.
    $this->assertSession()
      ->linkByHrefExists('https://google.com/search', 0, 'Generate Google freelink.');

    $this->assertSession()
      ->linkExists('logo.png', 0, 'Generate File freelink.');
    $this->assertSession()
      ->linkByHrefExists('https://en.wikipedia.org/wiki/Main_Page', 0, 'Generate Wikipedia freelink.');
    $this->assertSession()
      ->linkByHrefExists('https://en.wikisource.org/wiki/Main_Page', 0, 'Generate Wikisource freelink.');
    $this->assertSession()
      ->linkByHrefExists('https://en.wiktionary.org/wiki/Main_Page', 0, 'Generate Wiktionary freelink.');
    $this->assertSession()
      ->linkByHrefExists('https://en.wikiquote.org/wiki/Main_Page', 0, 'Generate Wikiquote freelink.');
    $this->assertSession()
      ->linkByHrefExists('https://en.wikibooks.org/wiki/Main_Page', 0, 'Generate Wikibooks freelink.');
    $this->assertSession()
      ->linkByHrefExists('https://en.wikinews.org/wiki/Main_Page', 0, 'Generate Wikinews freelink.');

    $this->assertSession()
      ->pageTextContains('Shown Text');
    $this->assertSession()
      ->pageTextContains('[[No Wiki]]');

    // @todo Media module parse test.
  }

  /**
   * Get HTML to use for node body.
   *
   * @return string
   *   The value to use for the node body.
   */
  protected function getNodeBodyValue() {
    $uid = $this->privilegedUser->id();
    return <<<EOF
      <ul>
        <li>Default plugin (nodetitle):  [[First page]]</li>
        <li>Nodetitle:      [[nodetitle:Second page]]</li>
        <li>Nid:            [[nid:2]]</li>
        <li>User:           [[u:$uid]]</li>
        <li>Drupalproject:  [[drupalproject:freelinking]]</li>
        <li>Drupalorg:      [[drupalorg:1]]</li>
        <li>Search:         [[search:test]]</li>
        <li>Google:         [[google:drupal]]</li>
        <li>File:           [[file:logo.png]]</li>
        <li>Wikipedia:      [[wikipedia:Main_Page]]</li>
        <li>Wikiquote:      [[wikiquote:Main Page]]</li>
        <li>Wiktionary:     [[wiktionary:Main Page]]</li>
        <li>Wikinews:       [[wikinews:Main Page]]</li>
        <li>Wikisource:     [[wikisource:Main Page]]</li>
        <li>Wikibooks:      [[wikibooks:Main Page]]</li>
        <li>Showtext:       [[showtext:Shown Text]]</li>
        <li>Nowiki:         [[nowiki:No Wiki]]</li>
      </ul>
      <p>Testing compatibility with other modules</p>
      <ul>
        <li>Respects [[drupalproject:media]] tags, such as:
        [[{"type":"media","view_mode":"media_large","fid":"286","attributes":{"alt":"","class":"media-image","typeof":"foaf:Image"}}]]
        </li>
      </ul>
EOF;
  }

}
