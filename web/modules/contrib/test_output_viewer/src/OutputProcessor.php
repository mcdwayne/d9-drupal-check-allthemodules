<?php

namespace Drupal\test_output_viewer;

use Drupal\test_output_viewer\Exception\WrongOutputException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Test output processor.
 */
class OutputProcessor implements OutputProcessorInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * OutputProcessor constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   */
  public function __construct(ConfigFactoryInterface $configFactory, DateFormatterInterface $dateFormatter) {
    $this->configFactory = $configFactory;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * Processes test output.
   */
  public function process() {
    $output_path = $this->configFactory->get('test_output_viewer.settings')->get('output_path');

    $data = [];

    // Find all test output files.
    $files = glob($output_path . '/*.html');
    usort($files, function ($a, $b) {
      return filemtime($a) < filemtime($b);
    });

    if (count($files) > 0) {

      // Get the test ID of the most recent file and filter the list by this ID.
      preg_match('#(?<class>[^_]+Test)-\d+-(?<id>\d+?)\.html$#', $files[0], $matches);
      if (!isset($matches['class'])) {
        throw new WrongOutputException(sprintf('The file "%s" has wrong name format.', $files[0]));
      }

      $data['created'] = $this->dateFormatter->format(filemtime($files[0]), 'html_time');
      // Make the module name human readable.
      $data['module'] = ucwords(trim(str_replace('_', ' ', $matches['module'])));
      $data['class'] = $matches['class'];
      $data['id'] = $matches['id'];
      $files = preg_grep('#^.+-\d+-' . $matches['id'] . '.html$#', $files);

      $results = [];
      foreach ($files as $file) {
        $html = file_get_contents($file);

        preg_match('#-(?<output_id>\d+?)-\d+.html$#', $file, $matches);
        $output_id = $matches['output_id'];

        $result = static::parse($html);
        if (!$result) {
          throw new WrongOutputException(sprintf('Could not parse file "%s".', $file));
        }

        $result['src'] = basename($file);
        $result['previous'] = file_exists($output_path . '/' . $result['previous']) ? $result['previous'] : NULL;
        $result['next'] = file_exists($output_path . '/' . $result['next']) ? $result['next'] : NULL;

        $results[$output_id] = $result;
      }

      ksort($results);
      $data['results'] = array_values($results);
    }

    return $data;
  }

  /**
   * Parses test output.
   */
  protected static function parse($output) {

    $pattern = '#^<hr />(?<links>.+?)<hr />(?<called>.+?)<hr />(?<description>.+?)<hr />#';
    preg_match($pattern, $output, $matches);

    if (!isset($matches['links'], $matches['called'], $matches['description'])) {
      return;
    }

    $result = [
      'description' => $matches['description'],
    ];

    $pattern = '#<a href="(?<previous>.*\.html)">Previous</a> \| <a href="(?<next>.*\.html)">Next</a>#';
    preg_match($pattern, $matches['links'], $matches);

    if (!isset($matches['previous'], $matches['next'])) {
      return;
    }

    $result['previous'] = $matches['previous'];
    $result['next'] = $matches['next'];

    return $result;
  }

}
