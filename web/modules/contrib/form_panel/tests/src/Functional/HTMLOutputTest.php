<?php

namespace Drupal\Tests\form_panel\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * @group FormPanel
 */
class HTMLOutputTest extends BrowserTestBase {

  protected static $modules = ['form_panel'];

  /**
   * Test the contents of a range of tables generated via a bitmap.
   */
  public function testCellContents() {
    $renderer = $this->container->get('renderer');
    for ($i = 0xFC00; $i <= 0xFCFF; $i++) {
      $form = $this->generateTestGrid(4, $i, $text);
      $plain = strip_tags($renderer->renderPlain($form));
      $this->assertEquals($text, $plain, sprintf('Output for i = 0x04%X', $i));
    }
  }

  /**
   * @param array $expected
   *  The expected values.
   *
   * @dataProvider providerTestHTMLOutput
   */
  public function testHTMLOutput($expected) {
    $renderer = $this->container->get('renderer');

    foreach ($expected['table'] as $i => $expected_table) {
      $table_form = $this->generateTestGrid(4, $i, $text);
      $rendered = (string) $renderer->renderPlain($table_form);
      $this->assertEquals($expected_table, $rendered, sprintf('Output for i = 0x%04X (table)', $i));
    }

    foreach ($expected['div'] as $i => $expected_div) {
      $div_form = $this->generateTestGrid(4, $i, $text);
      $div_form['#theme'] = 'form_panel_div';

      $rendered = (string) $renderer->renderPlain($div_form);
      $this->assertEquals($expected_div, $rendered, sprintf('Output for i = 0x%04X (div)', $i));
    }
  }

  /**
   * Generate a square test grid, using a bitmap for which cells in the grid
   * to fill with data.
   *
   * @param int $wh
   *   Width and height of the grid.
   * @param int $i
   *   Bitmap, where bits that are set get data and others become empty.
   * @param string &$text
   *   A text-only version of the grid.
   * @return array
   *   A render array for the grid.
   */
  private function generateTestGrid($wh, $i, &$text) {
    $start_row = 1;
    $form = [
      '#theme' => 'form_panel_table',
      '#form_panel_table_filler' => '',
      '#form_panel_table_span_rows' => TRUE,
    ];
    $a = ord('A');
    $r = $start_row * 1000;
    $c = 1;
    $text = '';
    while ($i && $r < ($wh + $start_row) * 1000) {
      if ($i & 1) {
        // By using ($c*2)+1 for the column, we are improving the test, by making
        // the column numbers non-contiguous.
        $w = $r + ($c * 2 + 1);
        $form[chr($a)] = array('#markup' => $r . $c . chr($a), '#weight' => $w);
        $text .= $r . $c . chr($a);
      }

      if (++$c > $wh) {
        $c = 1;
        $r += 1000;
      }
      $a++;
      $i >>= 1;
    }
    return $form;
  }

  /**
   * Returns expected data for testHTMLOutput.
   *
   * @return array
   */
  public function providerTestHTMLOutput() {
    // HTML test data for patterns 0x0101 through 0x0F0F.
    return [[[
      'table' => [
        0x0101 => '<table class="sticky-enabled form-panel"><thead><tr class="form-panel-row"><th class="form-panel-cell">10001A</th></tr></thead><tbody><tr class="form-panel-row"><td class="form-panel-cell">30001I</td></tr></tbody></table>',
        0x0202 => '<table class="sticky-enabled form-panel"><thead><tr class="form-panel-row"><th class="form-panel-cell">10002B</th></tr></thead><tbody><tr class="form-panel-row"><td class="form-panel-cell">30002J</td></tr></tbody></table>',
        0x0303 => '<table class="sticky-enabled form-panel"><thead><tr class="form-panel-row"><th class="form-panel-cell">10001A</th><th class="form-panel-cell">10002B</th></tr></thead><tbody><tr class="form-panel-row"><td class="form-panel-cell">30001I</td><td class="form-panel-cell">30002J</td></tr></tbody></table>',
        0x0404 => '<table class="sticky-enabled form-panel"><thead><tr class="form-panel-row"><th class="form-panel-cell">10003C</th></tr></thead><tbody><tr class="form-panel-row"><td class="form-panel-cell">30003K</td></tr></tbody></table>',
        0x0505 => '<table class="sticky-enabled form-panel"><thead><tr class="form-panel-row"><th class="form-panel-cell">10001A</th><th class="form-panel-cell">10003C</th></tr></thead><tbody><tr class="form-panel-row"><td class="form-panel-cell">30001I</td><td class="form-panel-cell">30003K</td></tr></tbody></table>',
        0x0606 => '<table class="sticky-enabled form-panel"><thead><tr class="form-panel-row"><th class="form-panel-cell">10002B</th><th class="form-panel-cell">10003C</th></tr></thead><tbody><tr class="form-panel-row"><td class="form-panel-cell">30002J</td><td class="form-panel-cell">30003K</td></tr></tbody></table>',
        0x0707 => '<table class="sticky-enabled form-panel"><thead><tr class="form-panel-row"><th class="form-panel-cell">10001A</th><th class="form-panel-cell">10002B</th><th class="form-panel-cell">10003C</th></tr></thead><tbody><tr class="form-panel-row"><td class="form-panel-cell">30001I</td><td class="form-panel-cell">30002J</td><td class="form-panel-cell">30003K</td></tr></tbody></table>',
        0x0808 => '<table class="sticky-enabled form-panel"><thead><tr class="form-panel-row"><th class="form-panel-cell">10004D</th></tr></thead><tbody><tr class="form-panel-row"><td class="form-panel-cell">30004L</td></tr></tbody></table>',
        0x0909 => '<table class="sticky-enabled form-panel"><thead><tr class="form-panel-row"><th class="form-panel-cell">10001A</th><th class="form-panel-cell">10004D</th></tr></thead><tbody><tr class="form-panel-row"><td class="form-panel-cell">30001I</td><td class="form-panel-cell">30004L</td></tr></tbody></table>',
        0x0A0A => '<table class="sticky-enabled form-panel"><thead><tr class="form-panel-row"><th class="form-panel-cell">10002B</th><th class="form-panel-cell">10004D</th></tr></thead><tbody><tr class="form-panel-row"><td class="form-panel-cell">30002J</td><td class="form-panel-cell">30004L</td></tr></tbody></table>',
        0x0B0B => '<table class="sticky-enabled form-panel"><thead><tr class="form-panel-row"><th class="form-panel-cell">10001A</th><th class="form-panel-cell">10002B</th><th class="form-panel-cell">10004D</th></tr></thead><tbody><tr class="form-panel-row"><td class="form-panel-cell">30001I</td><td class="form-panel-cell">30002J</td><td class="form-panel-cell">30004L</td></tr></tbody></table>',
        0x0C0C => '<table class="sticky-enabled form-panel"><thead><tr class="form-panel-row"><th class="form-panel-cell">10003C</th><th class="form-panel-cell">10004D</th></tr></thead><tbody><tr class="form-panel-row"><td class="form-panel-cell">30003K</td><td class="form-panel-cell">30004L</td></tr></tbody></table>',
        0x0D0D => '<table class="sticky-enabled form-panel"><thead><tr class="form-panel-row"><th class="form-panel-cell">10001A</th><th class="form-panel-cell">10003C</th><th class="form-panel-cell">10004D</th></tr></thead><tbody><tr class="form-panel-row"><td class="form-panel-cell">30001I</td><td class="form-panel-cell">30003K</td><td class="form-panel-cell">30004L</td></tr></tbody></table>',
        0x0E0E => '<table class="sticky-enabled form-panel"><thead><tr class="form-panel-row"><th class="form-panel-cell">10002B</th><th class="form-panel-cell">10003C</th><th class="form-panel-cell">10004D</th></tr></thead><tbody><tr class="form-panel-row"><td class="form-panel-cell">30002J</td><td class="form-panel-cell">30003K</td><td class="form-panel-cell">30004L</td></tr></tbody></table>',
        0x0F0F => '<table class="sticky-enabled form-panel"><thead><tr class="form-panel-row"><th class="form-panel-cell">10001A</th><th class="form-panel-cell">10002B</th><th class="form-panel-cell">10003C</th><th class="form-panel-cell">10004D</th></tr></thead><tbody><tr class="form-panel-row"><td class="form-panel-cell">30001I</td><td class="form-panel-cell">30002J</td><td class="form-panel-cell">30003K</td><td class="form-panel-cell">30004L</td></tr></tbody></table>',
      ],
      'div' => [
        0x0101 => '<div><div class="form-panel-div">10001A</div></div><div><div class="form-panel-div">30001I</div></div>',
        0x0202 => '<div><div class="form-panel-div">10002B</div></div><div><div class="form-panel-div">30002J</div></div>',
        0x0303 => '<div><div class="form-panel-div">10001A</div><div class="form-panel-div">10002B</div></div><div><div class="form-panel-div">30001I</div><div class="form-panel-div">30002J</div></div>',
        0x0404 => '<div><div class="form-panel-div">10003C</div></div><div><div class="form-panel-div">30003K</div></div>',
        0x0505 => '<div><div class="form-panel-div">10001A</div><div class="form-panel-div">10003C</div></div><div><div class="form-panel-div">30001I</div><div class="form-panel-div">30003K</div></div>',
        0x0606 => '<div><div class="form-panel-div">10002B</div><div class="form-panel-div">10003C</div></div><div><div class="form-panel-div">30002J</div><div class="form-panel-div">30003K</div></div>',
        0x0707 => '<div><div class="form-panel-div">10001A</div><div class="form-panel-div">10002B</div><div class="form-panel-div">10003C</div></div><div><div class="form-panel-div">30001I</div><div class="form-panel-div">30002J</div><div class="form-panel-div">30003K</div></div>',
        0x0808 => '<div><div class="form-panel-div">10004D</div></div><div><div class="form-panel-div">30004L</div></div>',
        0x0909 => '<div><div class="form-panel-div">10001A</div><div class="form-panel-div">10004D</div></div><div><div class="form-panel-div">30001I</div><div class="form-panel-div">30004L</div></div>',
        0x0A0A => '<div><div class="form-panel-div">10002B</div><div class="form-panel-div">10004D</div></div><div><div class="form-panel-div">30002J</div><div class="form-panel-div">30004L</div></div>',
        0x0B0B => '<div><div class="form-panel-div">10001A</div><div class="form-panel-div">10002B</div><div class="form-panel-div">10004D</div></div><div><div class="form-panel-div">30001I</div><div class="form-panel-div">30002J</div><div class="form-panel-div">30004L</div></div>',
        0x0C0C => '<div><div class="form-panel-div">10003C</div><div class="form-panel-div">10004D</div></div><div><div class="form-panel-div">30003K</div><div class="form-panel-div">30004L</div></div>',
        0x0D0D => '<div><div class="form-panel-div">10001A</div><div class="form-panel-div">10003C</div><div class="form-panel-div">10004D</div></div><div><div class="form-panel-div">30001I</div><div class="form-panel-div">30003K</div><div class="form-panel-div">30004L</div></div>',
        0x0E0E => '<div><div class="form-panel-div">10002B</div><div class="form-panel-div">10003C</div><div class="form-panel-div">10004D</div></div><div><div class="form-panel-div">30002J</div><div class="form-panel-div">30003K</div><div class="form-panel-div">30004L</div></div>',
        0x0F0F => '<div><div class="form-panel-div">10001A</div><div class="form-panel-div">10002B</div><div class="form-panel-div">10003C</div><div class="form-panel-div">10004D</div></div><div><div class="form-panel-div">30001I</div><div class="form-panel-div">30002J</div><div class="form-panel-div">30003K</div><div class="form-panel-div">30004L</div></div>',
      ],
    ]]];
  }
}
