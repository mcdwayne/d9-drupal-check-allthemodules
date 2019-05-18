<?php

/**
 * @file
 * Contains Drupal\chessboard_filter\Tests\ChessboardFilterTest.
 */

namespace Drupal\chessboard_filter\Tests;

use Drupal\simpletest\WebTestBase;

 /**
 * Tests each supported Chessboard Filter syntax.
 *
 * @group chessboard_filter
 */
class ChessboardFilterTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'chessboard_filter');
  protected $expected, $profile = 'testing';

  public function setUp() {
    parent::setUp();

    $image_base = file_url_transform_relative(file_create_url(drupal_get_path('module', 'chessboard_images') . '/default'));

    $this->expected['starting position'] = '<div class="chessboard"><div class="row"><img src="' . $image_base . '/rdl.png" width="40" height="40" alt="r" title="" /><img src="' . $image_base . '/ndd.png" width="40" height="40" alt="n" title="" /><img src="' . $image_base . '/bdl.png" width="40" height="40" alt="b" title="" /><img src="' . $image_base . '/qdd.png" width="40" height="40" alt="q" title="" /><img src="' . $image_base . '/kdl.png" width="40" height="40" alt="k" title="" /><img src="' . $image_base . '/bdd.png" width="40" height="40" alt="b" title="" /><img src="' . $image_base . '/ndl.png" width="40" height="40" alt="n" title="" /><img src="' . $image_base . '/rdd.png" width="40" height="40" alt="r" title="" /></div><div class="row"><img src="' . $image_base . '/pdd.png" width="40" height="40" alt="p" title="" /><img src="' . $image_base . '/pdl.png" width="40" height="40" alt="p" title="" /><img src="' . $image_base . '/pdd.png" width="40" height="40" alt="p" title="" /><img src="' . $image_base . '/pdl.png" width="40" height="40" alt="p" title="" /><img src="' . $image_base . '/pdd.png" width="40" height="40" alt="p" title="" /><img src="' . $image_base . '/pdl.png" width="40" height="40" alt="p" title="" /><img src="' . $image_base . '/pdd.png" width="40" height="40" alt="p" title="" /><img src="' . $image_base . '/pdl.png" width="40" height="40" alt="p" title="" /></div><div class="row"><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /></div><div class="row"><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /></div><div class="row"><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /></div><div class="row"><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /></div><div class="row"><img src="' . $image_base . '/pll.png" width="40" height="40" alt="P" title="" /><img src="' . $image_base . '/pld.png" width="40" height="40" alt="P" title="" /><img src="' . $image_base . '/pll.png" width="40" height="40" alt="P" title="" /><img src="' . $image_base . '/pld.png" width="40" height="40" alt="P" title="" /><img src="' . $image_base . '/pll.png" width="40" height="40" alt="P" title="" /><img src="' . $image_base . '/pld.png" width="40" height="40" alt="P" title="" /><img src="' . $image_base . '/pll.png" width="40" height="40" alt="P" title="" /><img src="' . $image_base . '/pld.png" width="40" height="40" alt="P" title="" /></div><div class="row"><img src="' . $image_base . '/rld.png" width="40" height="40" alt="R" title="" /><img src="' . $image_base . '/nll.png" width="40" height="40" alt="N" title="" /><img src="' . $image_base . '/bld.png" width="40" height="40" alt="B" title="" /><img src="' . $image_base . '/qll.png" width="40" height="40" alt="Q" title="" /><img src="' . $image_base . '/kld.png" width="40" height="40" alt="K" title="" /><img src="' . $image_base . '/bll.png" width="40" height="40" alt="B" title="" /><img src="' . $image_base . '/nld.png" width="40" height="40" alt="N" title="" /><img src="' . $image_base . '/rll.png" width="40" height="40" alt="R" title="" /></div></div>' . "
";

    $this->expected['marked squares'] = '<div class="chessboard"><div class="row"><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /></div><div class="row"><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/xxl.png" width="40" height="40" alt="x" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/xxl.png" width="40" height="40" alt="x" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /></div><div class="row"><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/xxl.png" width="40" height="40" alt="x" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/xxl.png" width="40" height="40" alt="x" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /></div><div class="row"><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/nld.png" width="40" height="40" alt="N" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /></div><div class="row"><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/xxl.png" width="40" height="40" alt="x" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/xxl.png" width="40" height="40" alt="x" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /></div><div class="row"><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/xxl.png" width="40" height="40" alt="x" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/xxl.png" width="40" height="40" alt="x" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /></div><div class="row"><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /></div><div class="row"><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /></div></div>' . "
";

    $this->expected['portion'] = '<div class="chessboard"><div class="border-top"><img src="' . $image_base . '/h.png" width="40" height="4" alt="" title="" /><img src="' . $image_base . '/h.png" width="40" height="4" alt="" title="" /><img src="' . $image_base . '/h.png" width="40" height="4" alt="" title="" /><img src="' . $image_base . '/c.png" width="4" height="4" alt="" title="" /></div><div class="row"><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/rdl.png" width="40" height="40" alt="r" title="" /><img src="' . $image_base . '/kdd.png" width="40" height="40" alt="k" title="" /><img src="' . $image_base . '/v.png" width="4" height="40" alt="" title="" /></div><div class="row"><img src="' . $image_base . '/nll.png" width="40" height="40" alt="N" title="" /><img src="' . $image_base . '/pdd.png" width="40" height="40" alt="p" title="" /><img src="' . $image_base . '/pdl.png" width="40" height="40" alt="p" title="" /><img src="' . $image_base . '/v.png" width="4" height="40" alt="" title="" /></div><div class="row"><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/v.png" width="4" height="40" alt="" title="" /></div></div>' . "
";

    $this->expected['borders'] = '<div class="chessboard"><div class="border-top"><img src="' . $image_base . '/c.png" width="4" height="4" alt="" title="" /><img src="' . $image_base . '/h.png" width="40" height="4" alt="" title="" /><img src="' . $image_base . '/c.png" width="4" height="4" alt="" title="" /></div><div class="row"><img src="' . $image_base . '/v.png" width="4" height="40" alt="" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/v.png" width="4" height="40" alt="" title="" /></div><div class="border-bottom"><img src="' . $image_base . '/c.png" width="4" height="4" alt="" title="" /><img src="' . $image_base . '/h.png" width="40" height="4" alt="" title="" /><img src="' . $image_base . '/c.png" width="4" height="4" alt="" title="" /></div></div>' . "
";

    $this->expected['borders'] .= '<div class="chessboard"><div class="border-top"><img src="' . $image_base . '/c.png" width="4" height="4" alt="" title="" /><img src="' . $image_base . '/h.png" width="40" height="4" alt="" title="" /><img src="' . $image_base . '/h.png" width="40" height="4" alt="" title="" /><img src="' . $image_base . '/c.png" width="4" height="4" alt="" title="" /></div><div class="row"><img src="' . $image_base . '/v.png" width="4" height="40" alt="" title="" /><img src="' . $image_base . '/l.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/d.png" width="40" height="40" alt="-" title="" /><img src="' . $image_base . '/v.png" width="4" height="40" alt="" title="" /></div><div class="border-bottom"><img src="' . $image_base . '/c.png" width="4" height="4" alt="" title="" /><img src="' . $image_base . '/h.png" width="40" height="4" alt="" title="" /><img src="' . $image_base . '/h.png" width="40" height="4" alt="" title="" /><img src="' . $image_base . '/c.png" width="4" height="4" alt="" title="" /></div></div>' . "
";

    $this->expected['archbishops'] = '<div class="chessboard"><div class="row"><img src="' . $image_base . '/all.png" width="40" height="40" alt="A" title="" /><img src="' . $image_base . '/ald.png" width="40" height="40" alt="A" title="" /><img src="' . $image_base . '/adl.png" width="40" height="40" alt="a" title="" /><img src="' . $image_base . '/add.png" width="40" height="40" alt="a" title="" /></div></div>' . "
";

    $this->expected['chancellors'] = '<div class="chessboard"><div class="row"><img src="' . $image_base . '/cll.png" width="40" height="40" alt="C" title="" /><img src="' . $image_base . '/cld.png" width="40" height="40" alt="C" title="" /><img src="' . $image_base . '/cdl.png" width="40" height="40" alt="c" title="" /><img src="' . $image_base . '/cdd.png" width="40" height="40" alt="c" title="" /></div></div>' . "
";

    // Add text format.
    $filtered_html_format = entity_create('filter_format', array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => array(
        // URL filter.
        'filter_url' => array(
          'weight' => 0,
          'status' => 1,
        ),
        // HTML filter.
        'filter_html' => array(
          'weight' => 1,
          'status' => 1,
        ),
        // Line break filter.
        'filter_autop' => array(
          'weight' => 2,
          'status' => 1,
        ),
        // Chessboard diagram filter.
        'chessboard_filter_diagram' => array(
          'weight' => 10,
          'status' => 1,
        ),
      ),
    ));
    $filtered_html_format->save();

    // Create Basic page node type.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
  }

  public function testChessboardFilterProcessField() {
    $expected = '';
    $expected .= $this->expected['starting position'];
    $expected .= $this->expected['marked squares'];
    $expected .= $this->expected['borders'];
    $expected .= $this->expected['archbishops'];
    $expected .= $this->expected['chancellors'];

    $text = '';

    // Starting position.
    $text .= '[chessboard]rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR[/chessboard]';

    // Marked squares.
    $text .= '[chessboard]8/3x1x2/2x3x1/4N3/2x3x1/3x1x2/8/8[/chessboard]';

    // Borders.
    $text .= '[chessboard](1TRBL)1[/chessboard]';
    $text .= '[chessboard](2TRBL)2[/chessboard]';

    // Archbishops.
    $text .= '[chessboard](4)AAaa[/chessboard]';

    // Chancellors.
    $text .= '[chessboard](4)CCcc[/chessboard]';

    $settings = array(
      'body' => array(array('value' => $text, 'format' => 'filtered_html')),
    );
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw($expected);
  }

  public function testChessboardFilterProcessSimple() {
    $expected = '';
    $expected .= $this->expected['starting position'];
    $expected .= $this->expected['marked squares'];
    $expected .= $this->expected['portion'];

    $text = '';

    // Starting position.
    $text .= '[chessboard]
rnbqkbnr
pppppppp
--------
--------
--------
--------
PPPPPPPP
RNBQKBNR
[/chessboard]';

    // Marked squares.
    $text .= '[chessboard]
--------
---x-x--
--x---x-
----N---
--x---x-
---x-x--
--------
--------
[/chessboard]';

    // Portion of a chessboard.
    $text .= '[chessboard](d3TR)
-rk
Npp
---
[/chessboard]';

    $settings = array(
      'body' => array(array('value' => $text, 'format' => 'filtered_html')),
    );
    $node = $this->drupalCreateNode($settings);

    $this->drupalGet('node/' . $node->id());
    $this->assertRaw($expected);
  }

  public function testChessboardFilterProcessMixture() {
    $expected = $this->expected['starting position'];

    $text = '';

    // Starting position.
    $text .= '[chessboard]
rnbqkbnr
pppppppp
8/8/8/8
PPPPPPPP
RNBQKBNR
[/chessboard]';

    $settings = array(
      'body' => array(array('value' => $text, 'format' => 'filtered_html')),
    );
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw($expected);
  }

  public function testChessboardFilterProcessAttached() {
    $media = 'all';
    $file = drupal_get_path('module', 'chessboard_images') . '/css/chessboard_images.css';
    $query_string = $this->container->get('state')->get('system.css_js_query_string') ?: '0';
    $raw = '<link rel="stylesheet" href="' . file_url_transform_relative(file_create_url($file)) . '?' . $query_string . '" media="' . $media . '"';

    $settings = array(
      'body' => array(array('value' => '[chessboard]rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR[/chessboard]', 'format' => 'filtered_html')),
    );
    $node = $this->drupalCreateNode($settings);

    // Activate the adding to the page of chessboard structures.
    $this->drupalGet('node/' . $node->id());

    // Assert presence of chessboard structures.
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw($raw);
  }

}
