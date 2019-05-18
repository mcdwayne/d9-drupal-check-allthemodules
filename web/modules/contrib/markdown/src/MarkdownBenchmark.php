<?php

namespace Drupal\markdown;

use Drupal\Component\Utility\ToStringTrait;

/**
 * Class MarkdownBenchmark.
 */
class MarkdownBenchmark implements \Serializable {

  use ToStringTrait;

  const TYPE_INVALID = 'invalid';

  /**
   * The Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected static $renderer;

  /**
   * Contains the different date interval units.
   *
   * This array is keyed by strings representing the unit (e.g.
   * '1y|@county') and with the amount of values of the unit in
   * milliseconds.
   *
   * @var array
   */
  protected static $units = [
    'y' => 31536000000,
    'mo' => 2592000000,
    'w' => 604800000,
    'd' => 86400000,
    'h' => 3600000,
    'm' => 60000,
    's' => 1000,
    'ms' => 0,
  ];

  /**
   * The start time.
   *
   * @var \DateTime
   */
  protected $start;

  /**
   * The end time.
   *
   * @var \DateTime
   */
  protected $end;

  /**
   * The time difference.
   *
   * @var \DateInterval
   */
  protected $diff;

  /**
   * The result of the callback.
   *
   * @var mixed
   */
  protected $result;

  /**
   * The type of benchmark this is.
   *
   * @var string
   */
  protected $type;

  /**
   * MarkdownBenchmark constructor.
   *
   * @param string $type
   *   The type of benchmark.
   * @param \DateTime $start
   *   The start time.
   * @param \DateTime $end
   *   The end end time.
   * @param mixed $result
   *   The result of what was benchmarked.
   */
  public function __construct($type, \DateTime $start, \DateTime $end, $result = NULL) {
    $this->type = $type;
    $this->start = $start;
    $this->end = $end;
    $this->diff = $start->diff($end);
    $this->result = $result;
  }

  /**
   * Creates a new MarkdownBenchmark instance.
   *
   * @param string $type
   *   The type of benchmark.
   * @param float|\DateTime $start
   *   The start microtime(TRUE).
   * @param float|\DateTime $end
   *   The end microtime(TRUE).
   * @param mixed $result
   *   The result of what was benchmarked.
   *
   * @return static
   */
  public static function create($type = NULL, $start = NULL, $end = NULL, $result = NULL) {
    if ($type === NULL) {
      $type = 'unknown';
    }
    if (!$start instanceof \DateTime) {
      if ($start === NULL) {
        $start = microtime(TRUE);
      }
      $start = \DateTime::createFromFormat('U.u', sprintf('%.6F', (float) $start));
    }
    if (!$end instanceof \DateTime) {
      if ($end === NULL) {
        $end = microtime(TRUE);
      }
      $end = \DateTime::createFromFormat('U.u', sprintf('%.6F', (float) $end));
    }
    return new static($type, $start, $end, $result);
  }

  public static function invalid() {
    return static::create(static::TYPE_INVALID);
  }

  public function build() {
    $time = 'N/A';
    $unit = NULL;

    if (!$this->isInvalid()) {
      $milliseconds = $this->getMilliseconds(FALSE);
      $time = '0.00';
      $unit = array_slice(array_keys(static::$units), -1, 1)[0];
      foreach (static::$units as $unit => $interval) {
        if ($milliseconds >= $interval) {
          $time = str_replace('.00', '', rtrim(number_format($interval > 0 ? $milliseconds / $interval : $milliseconds, 2), 0));
          break;
        }
      }
    }

    return [
      '#theme' => 'markdown_benchmark',
      '#time' => $time,
      '#unit' => $unit,
    ];
  }

  /**
   * Removes the result from the benchmark.
   *
   * This is primarily only useful when there are a bunch of benchmarks being
   * grouped together and only the last one needs to retrain the result.
   *
   * @see \Drupal\markdown\MarkdownBenchmarkAverages::iterate()
   *
   * @return static
   */
  public function clearResult() {
    $this->result = NULL;
    return $this;
  }

  /**
   * Retrieves the benchmark difference between start and end times.
   *
   * @return \DateInterval
   *   The benchmark difference.
   */
  public function getDiff() {
    return $this->diff;
  }

  /**
   * Retrieves the benchmark end time.
   *
   * @return \DateTime
   *   The benchmark end time.
   */
  public function getEnd() {
    return $this->end;
  }

  /**
   * Retrieves the amount of milliseconds from the diff.
   *
   * @param bool $format
   *   Flag indicating whether to format the result to two decimals.
   *
   * @return string|float
   *   The milliseconds.
   */
  public function getMilliseconds($format = TRUE) {
    $ms = 0;
    $ms += $this->diff->m * 2630000000;
    $ms += $this->diff->d * 86400000;
    $ms += $this->diff->h * 3600000;
    $ms += $this->diff->i * 60000;
    $ms += $this->diff->s * 1000;
    $ms += $this->diff->f * 1000;
    return $format ? number_format($ms, 2) : $ms;
  }

  /**
   * Retrieves the result of the callback that was invoked.
   *
   * @return mixed
   *   The callback result.
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * Retrieves the benchmark start time.
   *
   * @return \DateTime
   *   The benchmark start time.
   */
  public function getStart() {
    return $this->start;
  }

  /**
   * Retrieves the type of benchmark.
   *
   * @return string
   *   The benchmark type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Indicates whether the benchmark is invalid.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isInvalid() {
    return $this->getType() === static::TYPE_INVALID;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = $this->build();
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

  /**
   * {@inheritdoc}
   */
  public function serialize() {
    // Create a new unreferenced array (this is needed as PHP sometimes
    // stores objects as references when they're passed around). This can
    // cause reference/recursion issues in the serialized data.
    $array = [];
    foreach (get_object_vars($this) as $key => $value) {
      $array[$key] = is_object($value) ? clone $value : $value;
    }

    $data['object'] = serialize($array);

    // Determine if PHP has gzip capabilities.
    $data['gzip'] = extension_loaded('zlib');

    // Compress and encode the markdown and html output.
    if ($data['gzip']) {
      $data['object'] = base64_encode(gzencode($data['object'], 9));
    }

    return serialize($data);
  }

  /**
   * {@inheritdoc}
   */
  public function unserialize($serialized) {
    $data = unserialize($serialized);

    // Data was gzipped.
    if ($data['gzip']) {
      // Decompress data if PHP has gzip capabilities.
      if (extension_loaded('zlib')) {
        $data['object'] = gzdecode(base64_decode($data['object']));
      }
      else {
        $this->result = sprintf('This cached %s object was stored using gzip compression. Unable to decompress. The PHP on this server must have the "zlib" extension installed.', static::class);
        return;
      }
    }

    $object = unserialize($data['object']);
    foreach ($object as $prop => $value) {
      $this->$prop = $value;
    }
  }

}
