<?php

namespace Drupal\content_parser;

use Drupal\Core\Url;

/**
 * Class Results.
 */
class Results {

  /**
   * {@inheritdoc}
   */
  const NO_ACCESS = 1;  

  /**
   * {@inheritdoc}
   */
  const NO_UPDATE = 2;  

  /**
   * {@inheritdoc}
   */
  const ERROR = 3;

  /**
   * {@inheritdoc}
   */
  const UPDATE = 4;

  /**
   * {@inheritdoc}
   */
  const CREATE = 5;  

  /**
   * {@inheritdoc}
   */
  protected $results = [];

  /**
   * {@inheritdoc}
   */
  protected $parser;

  /**
   * {@inheritdoc}
   */
  public function __construct($parser) {
    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorCode() {
    return self::ERROR; 
  }

  /**
   * {@inheritdoc}
   */
  public function getNoAccessCode() {
    return self::NO_ACCESS; 
  }

  /**
   * {@inheritdoc}
   */
  public function getNoUpdateCode() {
    return self::NO_UPDATE; 
  }

  /**
   * {@inheritdoc}
   */
  public function getCreateCode() {
    return self::CREATE; 
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdateCode() {
    return self::UPDATE; 
  }

  /**
   * {@inheritdoc}
   */
  public function setResults($array) {
    $this->results = $array;
  }

  /**
   * {@inheritdoc}
   */
  public function getResults() {
    return $this->results;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($code, $count) {
    $msg = 'Others';

    switch ($code) {
      case self::NO_ACCESS:
          $msg = 'Пропушено страниц';
          break;
      case self::NO_UPDATE:
          $msg = "Не обновлено сущностей";
          break;
      case self::ERROR:
          $msg = "Ошибок";
          break;
      case self::UPDATE:
          $msg = "Обновлено сущностей";
          break;
      case self::CREATE:
          $msg = 'Создано сущностей';
          break;
    }

    return t('@msg: @count', [
      '@msg' => $msg,
      '@count' => \Drupal::l($count, Url::fromRoute('view.parser_etities.page_1', [
        'arg_0' => $this->parser
      ]))
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function generateResults() {
    $msgs = [];

    foreach (array_count_values($this->results) as $code => $count) {
      $msgs[] = $this->getMessage($code, $count);
    }

    return $msgs;
  }
}
