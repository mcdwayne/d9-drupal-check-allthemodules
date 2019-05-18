<?php

namespace Drupal\markdown\Plugin\Markdown;

/**
 * Interface MarkdownInterface.
 */
interface MarkdownParserBenchmarkInterface {

  /**
   * Benchmarks the MarkdownParser.
   *
   * @param string $markdown
   *   The markdown string to benchmark.
   * @param string $format
   *   A specific filter format identifier to use. If provided, the "rendered"
   *   benchmark will be that of check_markup(). If not, it will be that of
   *   the MarkdownParser's render() method.
   *
   * @return \Drupal\markdown\MarkdownBenchmark[]
   *   An array containing three benchmarks to be used with list():
   *   - parsing
   *   - rendering
   *   - total
   */
  public function benchmark($markdown, $format = NULL);

  /**
   * Averages a certain number of benchmarks of the MarkdownParser.
   *
   * @param string $markdown
   *   The markdown string to benchmark.
   * @param string $format
   *   A specific filter format identifier to use. If provided, the "rendered"
   *   benchmark will be that of check_markup(). If not, it will be that of
   *   the MarkdownParser's render() method.
   * @param int $iterations
   *   The amount of of loop iterations used to average the results of each
   *   MarkdownParser benchmark.
   *
   * @return \Drupal\markdown\MarkdownBenchmarkAverages
   *   A MarkdownBenchmarkAverages object.
   */
  public function benchmarkAverages($markdown, $format = NULL, $iterations = 10);

  /**
   * Performs the "parsed" benchmark.
   *
   * @param string $markdown
   *   The markdown string to benchmark.
   *
   * @return \Drupal\markdown\MarkdownBenchmark
   *   A MarkdownBenchmark object.
   */
  public function benchmarkParse($markdown);

  /**
   * Performs the "rendered" benchmark.
   *
   * @param string $markdown
   *   The markdown string to benchmark.
   * @param string $format
   *   A specific filter format identifier to use. If provided, the "rendered"
   *   benchmark will be that of check_markup(). If not, it will be that of
   *   the MarkdownParser's render() method.
   *
   * @return \Drupal\markdown\MarkdownBenchmark
   *   A MarkdownBenchmark object.
   */
  public function benchmarkRender($markdown, $format = NULL);

}
