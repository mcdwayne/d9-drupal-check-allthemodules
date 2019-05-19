<?php

namespace Drupal\xhprof\XHProfLib\Parser;

use Drupal\xhprof\XHProfLib\Run;

/**
 * Class DiffParser
 */
class DiffParser extends BaseParser {

  /**
   * @param \Drupal\xhprof\XHProfLib\Run $run
   * @param $sort
   * @param $symbol
   */
  public function __construct(Run $run, $sort, $symbol) {
    parent::__construct($run, $sort, $symbol);

    $this->diff_mode = TRUE;
  }

  /**
   *
   */
  public function parse() {
    // TODO: Implement parse() method.
  }
}
