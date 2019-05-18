<?php

namespace Drupal\drush_progress\Drush;

use Drush\Drush;

/**
 * Single progress bar instance.
 */
class ProgressBar {

  /**
   * The amount of columns this progress bar spans.
   *
   * @var int
   */
  private $columns;

  /**
   * Whether the progress bar is currently active or not.
   *
   * @var boolean
   */
  public $inProgress = TRUE;

  /**
   * Default constructor.
   *
   * @param bool $withEta
   *   Display ETA.
   * @param bool $onlyVerbose
   *   Do not display the progress bar when not in verbose mode.
   */
  public function __construct() {
    $config = Drush::config();
    $environment = $config->getContext('environment');
    $environment_options = $environment->get('options');
    $width = $environment_options['width'];
    $this->columns = $width;
  }

  /**
   * Update the progress bar.
   *
   * @param float $ratio
   */
  public function update($ratio) {
    $this->display($ratio);
  }

  /**
   * Set the state of the progress bar.
   *
   * @param int $whole
   *   The final value of whatever you're measuring.
   * @param int $part
   *   The current state of whatever you're measuring.
   */
  public function setProgress($whole, $part) {
    // Avoid a 'divided by zero' error.
    if ($whole == 0) {
      $this->update(0);
    }
    else {
      $this->update($part / $whole);
    }
  }

  /**
   * Terminate the progess bar.
   */
  public function end() {
    $this->update(1);
    $this->inProgress = FALSE;
    print "\n";
  }

  /**
   * Convert ratio to percentage.
   *
   * @param float $ratio
   *   The progress.
   *
   * @return string
   *   The percentage complete.
   */
  protected function getPercentage($ratio) {
    $percentage = min([
      max([
        0,
        round($ratio * 100),
      ]),
      100,
    ]);
    return $percentage . '%';
  }

  /**
   * Display the progress bar in it's current state.
   *
   * @param float $ratio
   */
  protected function display($ratio) {
    // Subtract 8 characters for the percentage, brackets, spaces and arrow.
    $progress_columns = $this->columns - 8;// - strlen($suffix) - 1;

    // Determine the current length of the progress string.
    $current_length = floor($ratio * $progress_columns);

    // If ratio is 1, then progress bar is complete and the arrow should
    // change from a '>' character to a '='.
    $arrow = ($ratio < 1) ? '>' : '=';
    $params = [
      '@percentage' => str_pad($this->getPercentage($ratio), 4, ' ', STR_PAD_LEFT),
      '@progress_string' => str_pad('', $current_length, '='),
      '@arrow' => str_pad($arrow, $progress_columns - $current_length, ' '),
    ];

    print dt("@percentage [@progress_string@arrow]\r", $params);
  }

}
