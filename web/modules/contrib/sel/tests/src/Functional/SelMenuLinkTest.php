<?php

namespace Drupal\Tests\sel\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that menu link attributes are well-handled.
 *
 * @group link
 */
class SelMenuLinkTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'block',
    'menu_link_content',
    'sel',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Enable the main menu block.
    $this->drupalPlaceBlock('system_menu_block:main', [
      'id' => 'test_menu',
    ]);

    $this->drupalLogin($this->drupalCreateUser([
      'access content',
      'link to any page',
    ]));
  }

  /**
   * Tests the link title settings of a link field.
   */
  public function testMenuLinkAttributes() {
    $request = \Drupal::request();
    $absoluteHost = $request->getSchemeAndHttpHost();

    // Add link '/user'.
    $menuLinkInternalRelative = MenuLinkContent::create([
      'title' => 'Internal relative url',
      'menu_name' => 'main',
      'bundle' => 'menu_link_content',
      'link' => ['uri' => 'internal:/user'],
    ]);
    $menuLinkInternalRelative->save();

    // Add link '/user/login'.
    $menuLinkInternalRelativeAlt = MenuLinkContent::create([
      'title' => 'Alternate internal relative url',
      'menu_name' => 'main',
      'bundle' => 'menu_link_content',
      'link' => ['uri' => 'internal:/user/login'],
    ]);
    $menuLinkInternalRelativeAlt->save();

    // Add link like 'https://local.test/user'.
    $menuLinkInternalAbsolute = MenuLinkContent::create([
      'title' => 'Internal absolute url',
      'menu_name' => 'main',
      'bundle' => 'menu_link_content',
      'link' => ['uri' => $absoluteHost . '/user'],
    ]);
    $menuLinkInternalAbsolute->save();

    // Add link 'https://example.com'
    $menuLinkExternal = MenuLinkContent::create([
      'title' => 'External url',
      'menu_name' => 'main',
      'bundle' => 'menu_link_content',
      'link' => ['uri' => 'https://example.com'],
    ]);
    $menuLinkExternal->save();

    // Add link like 'https://local.tester'.
    $menuLinkExternalAlt = MenuLinkContent::create([
      'title' => 'Alternate external url',
      'menu_name' => 'main',
      'bundle' => 'menu_link_content',
      'link' => ['uri' => $absoluteHost . 'er'],
    ]);
    $menuLinkExternalAlt->save();

    // Internal link with target and rel attributes.
    $menuLinkInternalAttrs = MenuLinkContent::create([
      'title' => 'Internal relative url with attributes',
      'menu_name' => 'main',
      'bundle' => 'menu_link_content',
      'link' => [
        'uri' => 'internal:/user',
        'options' => serialize([
          'attributes' => [
            'target' => '_blank',
            'rel' => 'nofollow',
          ],
        ]),
      ],
    ]);
    $menuLinkInternalAttrs->save();

    // External link with 'wrong' target and some rel attributes.
    $menuLinkExternalAttrs = MenuLinkContent::create([
      'title' => 'External url with attributes',
      'menu_name' => 'main',
      'bundle' => 'menu_link_content',
      'link' => [
        'uri' => 'https://example.com',
        'options' => serialize([
          'attributes' => [
            'target' => '_self',
            'rel' => 'nofollow',
          ],
        ]),
      ],
    ]);
    $menuLinkExternalAttrs->save();

    // External link with target and noopener rel attributes.
    $menuLinkExternalNoopener = MenuLinkContent::create([
      'title' => 'External url with noreferrer',
      'menu_name' => 'main',
      'bundle' => 'menu_link_content',
      'link' => [
        'uri' => 'https://example.com',
        'options' => serialize([
          'attributes' => [
            'target' => '_self',
            'rel' => 'noreferrer',
          ],
        ]),
      ],
    ]);
    $menuLinkExternalNoopener->save();

    $this->drupalGet('');

    // Test internal relative link.
    $link = $this->xpath('//a[text() = :title]', [
      ':title' => $menuLinkInternalRelative->getTitle(),
    ]);
    $this->assertEquals(TRUE, isset($link[0]));
    $this->assertEquals(NULL, $link[0]->getAttribute('target'));
    $this->assertEquals(NULL, $link[0]->getAttribute('rel'));
    $this->assertEquals($menuLinkInternalRelative->getUrlObject()->toString(), $link[0]->getAttribute('href'));

    // Test internal absolute link.
    $link = $this->xpath('//a[text() = :title]', [
      ':title' => $menuLinkInternalAbsolute->getTitle(),
    ]);
    $this->assertEquals(TRUE, isset($link[0]));
    $this->assertEquals(NULL, $link[0]->getAttribute('target'));
    $this->assertEquals(NULL, $link[0]->getAttribute('rel'));
    $this->assertEquals($menuLinkInternalAbsolute->getUrlObject()->toString(), $link[0]->getAttribute('href'));

    // Test external link.
    $link = $this->xpath('//a[text() = :title]', [
      ':title' => $menuLinkExternal->getTitle(),
    ]);
    $this->assertEquals(TRUE, isset($link[0]));
    $this->assertEquals('_blank', $link[0]->getAttribute('target'));
    $this->assertEquals('noreferrer', $link[0]->getAttribute('rel'));
    $this->assertEquals($menuLinkExternal->getUrlObject()->toString(), $link[0]->getAttribute('href'));

    // Test alternate external link.
    $link = $this->xpath('//a[text() = :title]', [
      ':title' => $menuLinkExternalAlt->getTitle(),
    ]);
    $this->assertEquals(TRUE, isset($link[0]));
    $this->assertEquals('_blank', $link[0]->getAttribute('target'));
    $this->assertEquals('noreferrer', $link[0]->getAttribute('rel'));
    $this->assertEquals($menuLinkExternalAlt->getUrlObject()->toString(), $link[0]->getAttribute('href'));

    // Test internal link with attributes.
    $link = $this->xpath('//a[text() = :title]', [
      ':title' => $menuLinkInternalAttrs->getTitle(),
    ]);
    $this->assertEquals(TRUE, isset($link[0]));
    $this->assertEquals('_blank', $link[0]->getAttribute('target'));
    $this->assertEquals('nofollow', $link[0]->getAttribute('rel'));
    $this->assertEquals($menuLinkInternalAttrs->getUrlObject()->toString(), $link[0]->getAttribute('href'));

    $this->drupalLogout();

    // Test alternate internal relative link.
    $link = $this->xpath('//a[text() = :title]', [
      ':title' => $menuLinkInternalRelativeAlt->getTitle(),
    ]);
    $this->assertEquals(TRUE, isset($link[0]));
    $this->assertEquals(NULL, $link[0]->getAttribute('target'));
    $this->assertEquals(NULL, $link[0]->getAttribute('rel'));
    $this->assertEquals($menuLinkInternalRelativeAlt->getUrlObject()->toString(), $link[0]->getAttribute('href'));

    // Test internal absolute link.
    $link = $this->xpath('//a[text() = :title]', [
      ':title' => $menuLinkInternalAbsolute->getTitle(),
    ]);
    $this->assertEquals(TRUE, isset($link[0]));
    $this->assertEquals(NULL, $link[0]->getAttribute('target'));
    $this->assertEquals(NULL, $link[0]->getAttribute('rel'));
    $this->assertEquals($menuLinkInternalAbsolute->getUrlObject()->toString(), $link[0]->getAttribute('href'));

    // Test external link.
    $link = $this->xpath('//a[text() = :title]', [
      ':title' => $menuLinkExternal->getTitle(),
    ]);
    $this->assertEquals(TRUE, isset($link[0]));
    $this->assertEquals('_blank', $link[0]->getAttribute('target'));
    $this->assertEquals('noreferrer', $link[0]->getAttribute('rel'));
    $this->assertEquals($menuLinkExternal->getUrlObject()->toString(), $link[0]->getAttribute('href'));

    // Test alternate external link.
    $link = $this->xpath('//a[text() = :title]', [
      ':title' => $menuLinkExternalAlt->getTitle(),
    ]);
    $this->assertEquals(TRUE, isset($link[0]));
    $this->assertEquals('_blank', $link[0]->getAttribute('target'));
    $this->assertEquals('noreferrer', $link[0]->getAttribute('rel'));
    $this->assertEquals($menuLinkExternalAlt->getUrlObject()->toString(), $link[0]->getAttribute('href'));

    // Test external link with attributes.
    $link = $this->xpath('//a[text() = :title]', [
      ':title' => $menuLinkExternalAttrs->getTitle(),
    ]);
    $this->assertEquals(TRUE, isset($link[0]));
    $this->assertEquals('_blank', $link[0]->getAttribute('target'));
    $this->assertEquals(TRUE, strpos($link[0]->getAttribute('rel'), 'noreferrer') !== FALSE);
    $this->assertEquals(TRUE, strpos($link[0]->getAttribute('rel'), 'nofollow') !== FALSE);
    $this->assertEquals($menuLinkExternalAttrs->getUrlObject()->toString(), $link[0]->getAttribute('href'));

    // Test external link with noreferrer rel.
    $link = $this->xpath('//a[text() = :title]', [
      ':title' => $menuLinkExternalNoopener->getTitle(),
    ]);
    $this->assertEquals(TRUE, isset($link[0]));
    $this->assertEquals('_blank', $link[0]->getAttribute('target'));
    $this->assertEquals('noreferrer', $link[0]->getAttribute('rel'));
    $this->assertEquals($menuLinkExternalNoopener->getUrlObject()->toString(), $link[0]->getAttribute('href'));
  }

}
