<?php

/**
 * @file
 * Contains Drupal\expressions\ExpressionLanguage.
 */

namespace Drupal\expressions;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Psr\Log\LoggerInterface;

/**
 * Class ExpressionLanguage.
 */
class ExpressionLanguage extends BaseExpressionLanguage {

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a Collector object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(LoggerInterface $logger) {

    $this->logger = $logger;

    parent::__construct();
  }

  public function evaluate($expression, $values = [])  {
    try {
      return $this->parse($expression, array_keys($values))->getNodes()->evaluate($this->functions, $values);
    }
    catch (SyntaxError $e) {
      // TODO: Change logger channel.
      $this->logger->error($e->getMessage());
    }
  }

}
