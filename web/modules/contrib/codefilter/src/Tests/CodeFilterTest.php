<?php

namespace Drupal\codefilter\Tests;

use Drupal\Tests\UnitTestCase;
use Drupal\codefilter\Plugin\Filter\CodeFilter;

/**
 * Tests code filter functions.
 *
 * @group CodeFilter
 */
class CodeFilterTest extends UnitTestCase {

  /**
   * Test info.
   */
  public static function getInfo() {
    return [
      'name' => 'Codefilter module text filters',
      'description' => 'Tests raw filtering functions.',
    ];
  }

  /**
   * Test codefilter_process_php().
   *
   * @dataProvider providerCodefilterProcessPHP
   */
  public function testCodefilterProcessPHP($input, $expected) {
    $result = CodeFilter::processPHP($input);
    $this->assertEquals($expected, $result);
  }

  /**
   * Test codefilter_process_code().
   *
   * @dataProvider providerCodefilterProcessCode
   */
  public function testCodefilterProcessCode($input, $expected) {
    $result = CodeFilter::processCode($input);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testCodefilterProcessPHP().
   */
  public static function providerCodefilterProcessPHP() {
    return [
      [
        self::providerCode(),
        '<div class="codeblock"><code><span style="color: #000000"><span style="color: #0000BB">&lt;?php<br /></span><span style="color: #FF8000">// Comment is here.<br /></span><span style="color: #0000BB">watchdog</span><span style="color: #007700">(</span><span style="color: #DD0000">\'actions\'</span><span style="color: #007700">, </span><span style="color: #DD0000">\'@count orphaned actions (%orphans) exist in the actions table. !link\'</span><span style="color: #007700">, array(</span><span style="color: #DD0000">\'@count\' </span><span style="color: #007700">=&gt; </span><span style="color: #0000BB">$count</span><span style="color: #007700">, </span><span style="color: #DD0000">\'%orphans\' </span><span style="color: #007700">=&gt; </span><span style="color: #0000BB">$orphans</span><span style="color: #007700">, </span><span style="color: #DD0000">\'!link\' </span><span style="color: #007700">=&gt; </span><span style="color: #0000BB">$link</span><span style="color: #007700">), </span><span style="color: #0000BB">WATCHDOG_INFO</span><span style="color: #007700">);<br /></span><span style="color: #FF8000">/**<br />&nbsp;* Longer comment is here.<br />&nbsp;**/<br /></span><span style="color: #0000BB">?&gt;</span></span></code></div>',
      ],
    ];
  }

  /**
   * Data provider for testCodefilterProcessPHP().
   */
  public static function providerCodefilterProcessCode() {
    return [
      [
        self::providerCode(),
        '<div class="codeblock"><code>// Comment is here.<br />watchdog(\'actions\', \'@count orphaned actions (%orphans) exist in the actions table. !link\', array(\'@count\' => $count, \'%orphans\' => $orphans, \'!link\' => $link), WATCHDOG_INFO);<br />/**<br /> * Longer comment is here.<br /> **/</code></div>',
      ],
    ];
  }

  /**
   * Provides a string with php code.
   *
   * @return string
   *   The php code as a string.
   */
  public static function providerCode() {
    return <<<EOF
// Comment is here.
watchdog('actions', '@count orphaned actions (%orphans) exist in the actions table. !link', array('@count' => \$count, '%orphans' => \$orphans, '!link' => \$link), WATCHDOG_INFO);
/**
 * Longer comment is here.
 **/
EOF;
  }

}
