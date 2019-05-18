<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/18/15
 * Time: 10:54 AM
 */

namespace Drupal\forena\Token;


class SQLReplacer extends TokenReplacerBase {

  /**
   * @param $formatter
   * SQL Data replacer
   */
  public function __construct($formatter = NULL) {
    parent::__construct(FRX_SQL_TOKEN, ':');
    if ($formatter) $this->setFormatter($formatter);
  }
}