<?php

namespace Drupal\Tests\entity_redirect\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Provide basic setup for all color field functional tests.
 *
 * @group color_field
 */
class EntityRedirectTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'entity_redirect',
  ];

  /**
   * The node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * The entity redirect settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->nodeType = $this->drupalCreateContentType(['type' => 'article']);
    $this->settings = [
      'edit' => [
        'active' => TRUE,
        'destination' => 'url',
        'url' => '/user/2',
      ],
      'add' => [
        'active' => FALSE,
        'destination' => 'external',
        'external' => 'https://google.ca',
        'url' => '/user/2/'

      ]
    ];
    $this->nodeType->setThirdPartySetting('entity_redirect',
      'redirect',
      $this->settings
    )->save();
    $this->drupalLogin($this->drupalCreateUser([
      'create article content',
      'edit own article content',
    ]));
  }

  public function testBasicRedirect() {
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
    ];
    $this->drupalPostForm('/node/add/article', $edit, t('Save'));
    $session = $this->assertSession();
    $session->addressEquals('/node/1');
    $session->statusCodeEquals(200);

    $this->drupalPostForm('/node/1/edit', $edit, t('Save'));
    $session->addressEquals('/user/2');
    $session->statusCodeEquals(200);

    $this->settings['add']['active'] = TRUE;
    $this->nodeType->setThirdPartySetting('entity_redirect',
      'redirect',
      $this->settings
    )->save();
    $this->drupalPostForm('/node/add/article/', $edit, t('Save'));
    $this->assertEquals('https://www.google.ca/', $this->getUrl());

    $this->settings['edit']['destination'] = 'add_form';
    $this->nodeType->setThirdPartySetting('entity_redirect',
      'redirect',
      $this->settings
    )->save();
    $this->drupalPostForm('/node/2/edit', $edit, t('Save'));
    $session->addressEquals('/node/add/article');
    $session->statusCodeEquals(200);

    $this->settings['edit']['destination'] = 'created';
    $this->nodeType->setThirdPartySetting('entity_redirect',
      'redirect',
      $this->settings
    )->save();
    $this->drupalPostForm('/node/2/edit', $edit, t('Save'));
    $session->addressEquals('/node/2');
    $session->statusCodeEquals(200);

    $this->settings['edit']['destination'] = 'edit_form';
    $this->nodeType->setThirdPartySetting('entity_redirect',
      'redirect',
      $this->settings
    )->save();
    $this->drupalPostForm('/node/2/edit', $edit, t('Save'));
    $session->addressEquals('/node/2/edit');
    $session->statusCodeEquals(200);

  }


}
