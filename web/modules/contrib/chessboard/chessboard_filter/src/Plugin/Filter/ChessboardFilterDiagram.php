<?php

namespace Drupal\chessboard_filter\Plugin\Filter;

use Drupal\chessboard_images\ToImagesTrait;
use Drupal\chessboard_lib\ericalvaresnl\chessboard_tag\ChessboardTagTrait;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to render chessboard diagrams.
 *
 * @Filter(
 *   id = "chessboard_filter_diagram",
 *   title = @Translation("Chessboard"),
 *   description = @Translation("Renders chessboard diagrams."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "file_max" = 8,
 *     "square_color_first" = 0
 *   },
 *   weight = 10
 * )
 */
class ChessboardFilterDiagram extends FilterBase {

  use ChessboardTagTrait;
  use ToImagesTrait;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if ($this->renderer === NULL) {
      $this->renderer = \Drupal::service('renderer');
    }
    $result = new FilterProcessResult('');
    $text = preg_replace_callback('@\[chessboard\](.*?)\[/chessboard\]@si',
      function($matches) use ($langcode) {
        $value = $this->parse($matches[1]) + ['language_code' => $langcode];
        $partial = $this->filter($value);
        $output = $this->renderer->render($partial);
        return $output;
      },
      $text);
    $result->setProcessedText($text);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('<h4>Chessboard Diagrams</h4>
<h5>Basic Usage</h5>
<p>Use the pair of <code>[chessboard]</code> and <code>[/chessboard]</code> tags to define a chessboard. The following formats are recognized:</p>
<ul>
  <li>The piece placement field (i.e., the first field) of the FEN (Forsyth-Edwards Notation) syntax. E.g., for the starting position:
    <code>[chessboard]rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR[/chessboard]</code>
  </li>
  <li>A simple and direct piece placement format. E.g., for the starting position:
<pre>[chessboard]
rnbqkbnr
pppppppp
--------
--------
--------
--------
PPPPPPPP
RNBQKBNR
[/chessboard]</pre>
Note that a dash (-) character represents an empty square.
  </li>
  <li>A mixture of the previous two formats. This is possible because the previous formats are in fact compatible to each other. E.g., for the starting position:
<pre>[chessboard]
rnbqkbnr
pppppppp
8/8/8/8
PPPPPPPP
RNBQKBNR
[/chessboard]</pre>
  However, the use of this format is NOT encouraged as it may lead to confusion.
  </li>
</ul>
<p>The renderer also supports a special feature that will converts \'x\' character to a marked square. For example, to display the diagram below which shows the squares a knight can control, you can write</p>
<pre>[chessboard]
--------
---x-x--
--x---x-
----N---
--x---x-
---x-x--
--------
--------
[/chessboard]</pre>
<p>or</p>
<pre>[chessboard]8/3x1x2/2x3x1/4N3/2x3x1/3x1x2/8/8[/chessboard]</pre>

<h5>Advanced Usage</h5>
<p>You can configure the appearance of the chessboard with a few flags, with the following syntax:</p>
<pre>[chessboard](<i>flags</i>)board[/chessboard]</pre>
<p>The following flags are supported:</p>
<ul>
  <li>Borders. Valid flags are T, B, L, and R, which correspond to the top border, the bottom border, the left border, and the right border respectively. The corresponding borders of the chessboard will be rendered for the flags specified. (Default: none is specified.)</li>
  <li>Number of Files. Valid flags are positive integers such as 4, 6, and 12. (Default: 8.)</li>
  <li>Color of the Upper Left Square. Valid flags are d and l, which correspond to the dark color and the light color. (Default: l.)</li>
</ul>
<p>These flags are especially useful in displaying a portion of a chessboard. For example, the following sentence illustrates a smothered mate at the upper-right corner:</p>
<pre>[chessboard](d3TR)
-rk
Npp
---
[/chessboard]
</pre>
<p>Any portion of a chessboard can be displayed by using these flags.</p>
');
    }
    else {
      return $this->t('Renders chessboard diagrams.');
    }
  }

}
