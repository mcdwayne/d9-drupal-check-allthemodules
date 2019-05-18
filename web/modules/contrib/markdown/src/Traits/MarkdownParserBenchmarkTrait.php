<?php

namespace Drupal\markdown\Traits;

use Drupal\Core\Language\LanguageInterface;
use Drupal\markdown\MarkdownBenchmark;
use Drupal\markdown\MarkdownBenchmarkAverages;
use Drupal\markdown\ParsedMarkdown;

trait MarkdownParserBenchmarkTrait {

  /**
   * Flag indicating whether this is currently in the process of a benchmark.
   *
   * @var bool
   */
  protected static $benchmark = FALSE;

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\markdown\Plugin\Markdown\MarkdownParserBenchmarkInterface::benchmark()
   *
   * @return \Drupal\markdown\MarkdownBenchmark[]
   */
  public function benchmark($markdown, $format = NULL) {
    // Start.
    $parsed = $this->benchmarkParse($markdown);
    $rendered = $this->benchmarkRender($markdown, $format);
    $total = MarkdownBenchmark::create('total', $parsed->getStart(), $rendered->getEnd(), $rendered->getResult());

    // Stop
    static::$benchmark = FALSE;

    // Return parsed, rendered, total.
    return [$parsed, $rendered, $total];
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\markdown\Plugin\Markdown\MarkdownParserBenchmarkInterface::benchmarkAverages()
   *
   * @return \Drupal\markdown\MarkdownBenchmarkAverages
   */
  public function benchmarkAverages($markdown, $format = NULL, $iterations = 10) {
    return MarkdownBenchmarkAverages::create($iterations)->iterate([$this, 'benchmark'], [$markdown, $format]);
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\markdown\Plugin\Markdown\MarkdownParserBenchmarkInterface::benchmarkParse()
   *
   * @return \Drupal\markdown\MarkdownBenchmark
   */
  public function benchmarkParse($markdown) {
    $start = microtime(TRUE);
    $result = $this->convertToHtml($markdown);
    $end = microtime(TRUE);
    return MarkdownBenchmark::create('parsed', $start, $end, static::$benchmark = $result);
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\markdown\Plugin\Markdown\MarkdownParserBenchmarkInterface::benchmarkRender()
   *
   * @return \Drupal\markdown\MarkdownBenchmark
   */
  public function benchmarkRender($markdown, $format = NULL) {
    if ($format === NULL) {
      /** @var \Drupal\Core\Render\RendererInterface $renderer */
      $renderer = \Drupal::service('renderer');
      $start = microtime(TRUE);
      $build = ['#markup' => $this->parse($markdown)];
      $result = $renderer->renderPlain($build);
      $end = microtime(TRUE);
    }
    else {
      $start = microtime(TRUE);
      $result = check_markup($markdown, $format);
      $end = microtime(TRUE);
    }

    return MarkdownBenchmark::create('rendered', $start, $end, $result);
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface
   */
  public function parse($markdown, LanguageInterface $language = NULL) {
    return ParsedMarkdown::create($markdown, static::$benchmark ?: $this->convertToHtml($markdown, $language), FALSE, $language);
  }


}
