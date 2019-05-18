<?php

namespace Drupal\background_batch;

/**
 * Class batch context.Automatically updates.
 */
class BackgroundBatchContext extends ArrayObject {
  private $batch = NULL;
  private $interval = NULL;
  private $progress = NULL;

  /**
   * Implements @Constuctor.
   */
  public function __construct() {
    $this->interval = \Drupal::config('background_batch.settings')->get('background_batch_delay') / 1000000;
    $args = func_get_args();
    return call_user_func_array(
    [
      'parent', '__construct',
    ], $args);
  }

  /**
   * Implements to Set progress update interval in seconds.
   */
  public function setInterval($interval) {
    $this->interval = $interval;
  }

  /**
   * Implements to Update progress if needed.
   */
  public function offsetSet($name, $value) {
    if ($name == 'finished') {
      if (!isset($this->batch)) {
        $this->batch =& batch_get();
        $this->progress = progress_get_progress('_background_batch:' . $this->batch['id']);
      }
      if ($this->batch) {
        $total = $this->batch['sets'][$this->batch['current_set']]['total'];
        $count = $this->batch['sets'][$this->batch['current_set']]['count'];
        $elapsed = $this->batch['sets'][$this->batch['current_set']]['elapsed'];
        $progress_message = $this->batch['sets'][$this->batch['current_set']]['progress_message'];
        $current = $total - $count;
        $step = 1 / $total;
        $base = $current * $step;
        $progress = $base + $value * $step;

        progress_estimate_completion($this->progress);
        $elapsed = floor($this->progress->current - $this->progress->start);

        $values = [
          '@remaining'  => $count,
          '@total'      => $total,
          '@current'    => $current,
          '@percentage' => $progress * 100,
          '@elapsed'    => format_interval($elapsed),
          // If possible, estimate remaining processing time.
          '@estimate'   => format_interval(floor($this->progress->estimate) - floor($this->progress->current)),
        ];
        $message = strtr($progress_message, $values);
        $message .= $message && $this['message'] ? '<br/>' : '';
        $message .= $this['message'];
        progress_set_intervalled_progress('_background_batch:' . $this->batch['id'], $message ? $message : $this->progress->message, $progress, $this->interval);
      }
    }

    return parent::offsetSet($name, $value);
  }

}
