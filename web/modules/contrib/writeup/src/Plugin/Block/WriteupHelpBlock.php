<?php

/**
 * @file
 * Contains \Drupal\block_example\Plugin\Block\WriteupHelpBlock.
 */

namespace Drupal\writeup\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Writeup Formatting Tips block.
 *
 * @Block(
 *   id = "writeup_help",
 *   admin_label = @Translation("Writeup Formatting Tips")
 * )
 */
class WriteupHelpBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#type' => 'markup',
      '#markup' => '<pre>' . $this->t("
.Header 1
..Header 2
...Header 3
....Header 4
.....Header 5

Link [[http://writeup.org Link to writeup.org]]

Inline markup like _italics_,
 *bold*, and <`code()`>.

// Comments on a line

-Bullet lists can be any level of nesting
 -Nested
 -And again
 simple list element (not bullet or number)

1. Numbered list
3. With a period, numbers need not be contiguous
#. # means auto numbering
 A. Can be letters (upper or lower)
 i. Roman
 -mixed with unordered lists
  list elements can be continued
#. will be numbered as 5.

<span>html may be used anywhere</span>

<(anything between these marks is
<div>displayed, *literally* and not
interpreted</div>)>") . '</pre>',
    );
  }

}
