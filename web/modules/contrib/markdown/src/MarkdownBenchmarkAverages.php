<?php

namespace Drupal\markdown;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\ToStringTrait;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class MarkdownBenchmarkAverages.
 */
class MarkdownBenchmarkAverages {

  use DependencySerializationTrait;
  use StringTranslationTrait;
  use ToStringTrait;

  /**
   * The Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected static $renderer;

  /**
   * A fallback benchmark.
   *
   * @var \Drupal\markdown\MarkdownBenchmark
   */
  protected $fallbackBenchmark;

  /**
   * The number of iterations.
   *
   * @var int
   */
  protected $iterationCount = 10;

  /**
   * An array of benchmarks.
   *
   * @var \Drupal\markdown\MarkdownBenchmark[]
   */
  protected $benchmarks = [];

  /**
   * MarkdownBenchmarkAverages constructor.
   *
   * @param int $iteration_count
   *   The amount of of loop iterations used to average the results of each
   *   MarkdownParser benchmark.
   * @param \Drupal\markdown\MarkdownBenchmark $fallback_benchmark
   *   A fallback benchmark to use if/when there are no benchmarks available.
   */
  public function __construct($iteration_count = 10, MarkdownBenchmark $fallback_benchmark = NULL) {
    $this->iterationCount = $iteration_count;
    $this->fallbackBenchmark = $fallback_benchmark;
  }

  /**
   * Creates a new MarkdownBenchmarkAverages object.
   *
   * @param int $iteration_count
   *   The amount of of loop iterations used to average the results of each
   *   MarkdownParser benchmark.
   * @param \Drupal\markdown\MarkdownBenchmark $fallback
   *   A fallback benchmark to use if/when there are no benchmarks available.
   *
   * @return static
   */
  public static function create($iteration_count = 10, MarkdownBenchmark $fallback = NULL) {
    return new static($iteration_count, $fallback);
  }

  public function build($type = 'total') {
    if (!$this->hasBenchmarks()) {
      $build = MarkdownBenchmark::invalid();
      $title = $this->t('No available benchmark tests.');
    }
    else {
      $build = $this->getAverage($type);
      $iterations = $this->getIterationCount() > 1 ? new FormattableMarkup('(@count<em>𝒙</em>) ', ['@count' => $this->getIterationCount()]) : '';

      $variables = [
        '@iterations' => $iterations,
      ];

      if ($type !== 'all') {
        $variables += [
          '@parsed' => $this->getAverage('parsed', TRUE),
          '@rendered' => $this->getAverage('rendered', TRUE),
          '@total' => $this->getAverage('total', TRUE),
        ];
      }

      switch ($type) {
        case 'all':
          $title = $this->t('@iterationsParsed / Rendered / Total', $variables);
          break;

        case 'parsed':
          $title = $this->t('@iterationsParsed (rendered @rendered, total @total)', $variables);
          break;

        case 'rendered':
          $title = $this->t('@iterationsRendered (parsed @parsed, total @total)', $variables);
          break;

        // Total.
        default:
          $title = $this->t('@iterationsTotal (parsed @parsed, rendered @rendered)', $variables);
          break;
      }
    }

    $build['#attributes']['data-toggle'] = 'tooltip';
    $build['#attributes']['data-placement'] = 'bottom';
    $build['#attributes']['title'] = $title;

    return $build;
  }

  /**
   * Iterates a callback that produces benchmarks.
   *
   * @param callable $callback
   *   A callback.
   * @param array $args
   *   The arguments to provide to the $callback.
   *
   * @return static
   */
  public function iterate(callable $callback, array $args = []) {
    $this->benchmarks = [];

    // Iterate the callback the specified amount of times.
    for ($i = 0; $i < $this->iterationCount; $i++) {
      $benchmarks = (array) call_user_func_array($callback, $args);

      // Verify all benchmarks are the proper object.
      foreach ($benchmarks as $benchmark) {
        if (!($benchmark instanceof MarkdownBenchmark)) {
          throw new \InvalidArgumentException(sprintf('The provided callback must return an instance of \\Drupal\\markdown\\MarkdownBenchmark, got "%s" instead.', (string) $benchmark));
        }

        // Remove the result if this is the last benchmark. This is to reduce
        // the amount of storage needed on the backend.
        if ($this->iterationCount > 1 && $i < $this->iterationCount - 1) {
          $benchmark->clearResult();
        }
      }

      $this->benchmarks = array_merge($this->benchmarks, $benchmarks);
    }

    return $this;
  }

  /**
   * Retrieves the averaged time from all benchmarks of a certain type.
   *
   * @param string $type
   *   The type of benchmark to retrieve, can be one of:
   *   - parsed
   *   - rendered
   *   - total (default)
   * @param bool $render
   *   Flag indicating whether to render the build array.
   *
   * @return array|\Drupal\Component\Render\MarkupInterface
   *   A renderable array containing the averaged time.
   */
  public function getAverage($type = 'total', $render = FALSE) {
    $build = [
      '#theme' => 'item_list__markdown_benchmark_average',
      '#items' => [],
      '#attributes' => [
        'class' => [
          'markdown-benchmark-average',
          "markdown-benchmark-average--$type",
        ],
      ],
      '#context' => ['type' => $type],
    ];

    if ($type === 'all') {
      $build['#items'] = [
        ['data' => $this->getAverage('parsed', 'all')],
        ['data' => $this->getAverage('rendered', 'all')],
        ['data' => $this->getAverage('total', 'all')],
      ];
      return $build;
    }

    $benchmarks = $this->getBenchmarks($type);

    if (!$benchmarks) {
      return [];
    }

    $last = array_slice($benchmarks, -1, 1)[0];
    $result = $last->getResult();

    if (count($benchmarks) === 1) {
      $start = $last->getStart();
      $end = $last->getEnd();
    }
    else {
      $ms = array_map(function ($benchmark) {
        /** @var \Drupal\markdown\MarkdownBenchmark $benchmark */
        return $benchmark->getMilliseconds(FALSE);
      }, $benchmarks);
      $averaged_ms = array_sum($ms) / count($ms);
      $start = microtime(TRUE);
      $end = $start + ($averaged_ms / 1000);
    }

    $average = MarkdownBenchmark::create('average', $start, $end, $result)->build();

    if ($render === 'all') {
      return $average;
    }

    $build['#items'][] = ['data' => $average];

    return $render ? $this->renderer()->renderPlain($build) : $build;
  }

  /**
   * Retrieves the currently set benchmarks.
   *
   * @param string $type
   *   The type of benchmark to retrieve, can be one of:
   *   - parsed
   *   - rendered
   *   - total (default)
   *
   * @return \Drupal\markdown\MarkdownBenchmark[]
   */
  public function getBenchmarks($type = NULL) {
    if ($type === NULL) {
      return array_values($this->benchmarks);
    }

    return array_values(array_filter($this->benchmarks, function ($benchmark) use ($type) {
      /** @type \Drupal\markdown\MarkdownBenchmark $benchmark */
      return $benchmark->getType() === $type;
    }));
  }

  /**
   * Retrieves a fallback benchmark, creating one if necessary.
   *
   * @return \Drupal\markdown\MarkdownBenchmark
   *   A fallback benchmark.
   */
  public function getFallbackBenchmark() {
    if ($this->fallbackBenchmark === NULL) {
      $this->fallbackBenchmark = MarkdownBenchmark::create('fallback', NULL, NULL, $this->t('N/A'));
    }
    return $this->fallbackBenchmark;
  }

  /**
   * Retrieves the last benchmark of a certain type.
   *
   * @param string $type
   *   The type of benchmark to retrieve, can be one of:
   *   - parsed
   *   - rendered
   *   - total (default)
   *
   * @return \Drupal\markdown\MarkdownBenchmark
   *   The last benchmark of $type.
   */
  public function getLastBenchmark($type = 'total') {
    $benchmarks = $this->getBenchmarks($type);
    return array_pop($benchmarks) ?: $this->getFallbackBenchmark();
  }

  /**
   * Retrieves the number of times the benchmarks were iterated over.
   *
   * @return int
   *   The iteration count.
   */
  public function getIterationCount() {
    return $this->iterationCount;
  }

  /**
   * Indicates whether there are benchmarks or not.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function hasBenchmarks() {
    return !!$this->benchmarks;
  }

  /**
   * {@inheritdoc}
   */
  public function render($type = 'total') {
    $build = $this->build($type);
    return $this->renderer()->renderPlain($build);
  }

  /**
   * Retrieves the Renderer service.
   *
   * @return \Drupal\Core\Render\RendererInterface
   */
  protected function renderer() {
    if (static::$renderer === NULL) {
      static::$renderer = \Drupal::service('renderer');
    }
    return static::$renderer;
  }

}
