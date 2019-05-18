<?php

/**
 * BackgroundProcess class.
 */
class BackgroundProcess {
  public $handle;
  public $connection;
  public $serviceHost;
  public $serviceGroup;
  public $uid;

  /**
   * Implements Static Method.
   */
  public static function load($process) {
    $new = new BackgroundProcess($process->handle);
    @$new->callback = $process->callback;
    @$new->args = $process->args;
    @$new->uid = $process->uid;
    @$new->token = $process->token;
    @$new->serviceHost = $process->serviceHost;
    @$new->serviceGroup = $process->serviceGroup;
    @$new->exec_status = $process->exec_status;
    @$new->start_stamp = $process->start_stamp;
    @$new->status = $process->exec_status;
    @$new->start = $process->start_stamp;
    return $new;
  }

  /**
   * Implements Constructor.
   */
  public function __construct($handle = NULL) {
    $this->handle = $handle ? $handle : background_process_generate_handle('auto');
    $this->token = background_process_generate_handle('token');
    $this->serviceGroup = \Drupal::config('background_process.settings')->get('background_process_default_service_group');
  }

  /**
   * Implements to Lock the Background Process.
   */
  public function lock($status = BACKGROUND_PROCESS_STATUS_LOCKED) {
    // Preliminary select to avoid unnecessary write-attempt.
    if (background_process_get_process($this->handle)) {
      return FALSE;
    }

    // "Lock" handle.
    $this->start_stamp = $this->start = microtime(TRUE);
    if (!background_process_lock_process($this->handle, $status)) {
      \Drupal::logger('bg_process')->error('Could not lock handle %handle', ['%handle' => $this->handle]);
      return FALSE;
    }
    $this->exec_status = $this->status = BACKGROUND_PROCESS_STATUS_LOCKED;
    $this->sendMessage('locked');
    return TRUE;
  }

  /**
   * Implements to Call the service handler.
   */
  public function start($callback, $args = []) {
    if (!$this->lock()) {
      return FALSE;
    }

    return $this->execute($callback, $args);
  }

  /**
   * Implements Queues.
   */
  public function queue($callback, $args = []) {
    if (!$this->lock(BACKGROUND_PROCESS_STATUS_QUEUED)) {
      return FALSE;
    }

    $this->callback = $callback;
    $this->args = $args;

    if (!background_process_set_process($this->handle, $this->callback, $this->uid, $this->args, $this->token)) {
      return NULL;
    }

    \Drupal::moduleHandler()->invokeAll('background_process_pre_execute',
    [$this->handle, $this->callback, $this->args, $this->token]);

    // Initialize progress stats.
    $old_db = db_set_active('background_process');
    progress_remove_progress($this->handle);
    db_set_active($old_db);

    $queues = \Drupal::config('background_process.settings')->get('background_process_queues');
    $queue_name = isset($queues[$this->callback]) ? 'bgp:' . $queues[$this->callback] : 'background_process';
    $queue = DrupalQueue::get($queue_name);
    $queue->createItem([rawurlencode($this->handle), rawurlencode($this->token)]);
    _background_process_ensure_cleanup($this->handle, TRUE);
  }

  /**
   * Implements Function to Determine Service Host.
   */
  public function determineServiceHost() {

    $service_hosts = background_process_get_service_hosts();
    if ($this->serviceHost && empty($service_hosts[$this->serviceHost])) {
      $this->serviceHost = \Drupal::config('background_process.settings')->get('background_process_default_service_host');
      if (empty($service_hosts[$this->serviceHost])) {
        $this->serviceHost = NULL;
      }
    }

    // Find service group if a service host is not explicitly specified.
    if (!$this->serviceHost) {
      if (!$this->serviceGroup) {
        $this->serviceGroup = \Drupal::config('background_process.settings')->get('background_process_default_service_group');
      }
      if ($this->serviceGroup) {
        $service_groups = \Drupal::config('background_process.settings')->get('background_process_service_groups');
        if (isset($service_groups[$this->serviceGroup])) {
          $service_group = $service_groups[$this->serviceGroup];

          // Default method if none is provided.
          $service_group += [
            'method' => 'background_process_service_group_round_robin',
          ];

          if (is_callable($service_group['method'])) {
            $this->serviceHost = call_user_func($service_group['method'], $service_group);
            if ($this->serviceHost && empty($service_hosts[$this->serviceHost])) {
              $this->serviceHost = NULL;
            }
          }
        }
      }
    }

    // Fallback service host.
    if (!$this->serviceHost || empty($service_hosts[$this->serviceHost])) {
      $this->serviceHost = \Drupal::config('background_process.settings')->get('background_process_default_service_host');
      if ((empty($service_hosts[$this->serviceHost])) || ($service_hosts[$this->serviceHost] == 0)) {
        $this->serviceHost = 'default';
      }
    }

    return $this->serviceHost;
  }

  /**
   * Implements Execute Process.
   */
  public function execute($callback, $args = []) {
    $this->callback = $callback;
    $this->args = $args;
    if (!background_process_set_process($this->handle, $this->callback, $this->uid, $this->args, $this->token)) {
      // Could not update process.
      return NULL;
    }

    \Drupal::moduleHandler()->invokeAll('background_process_pre_execute',
    [$this->handle, $this->callback, $this->args, $this->token]);

    // Initialize progress stats.
    $old_db = db_set_active('background_process');
    progress_remove_progress($this->handle);
    db_set_active($old_db);

    $this->connection = FALSE;
    $this->determineServiceHost();

    return $this->dispatch();
  }

  /**
   * Implements to Dispatch Process.
   */
  public function dispatch() {
    $this->sendMessage('dispatch');
    $handle = rawurlencode($this->handle);
    $token = rawurlencode($this->token);
    if ($this->serviceHost == 0) {
      $this->serviceHost = 'default';
    }
    list($url, $headers) = background_process_build_request('bgp-start/' . $handle . '/' . $token, $this->serviceHost);
    background_process_set_service_host($this->handle, $this->serviceHost);

    $options = ['method' => 'POST', 'headers' => $headers];
    $result = background_process_http_request($url, $options);

    if (empty($result->error)) {
      $this->connection = $result->fp;
      _background_process_ensure_cleanup($this->handle, TRUE);
      return TRUE;
    }
    else {
      background_process_remove_process($this->handle);
      \Drupal::logger('bg_process')->error('Could not call service %handle for callback %callback: @error', [
        '%handle' => $this->handle,
        '%callback' => _background_process_callback_name($this->callback),
        '@error' => print_r($result, TRUE),
      ]);
      return NULL;
    }
    return FALSE;
  }

  /**
   * Implements to Send Message.
   */
  public function sendMessage($action) {
    if (\Drupal::moduleHandler()->moduleExists('nodejs')) {
      if (!isset($this->progress_object)) {
        if ($progress = progress_get_progress($this->handle)) {
          $this->progress_object = $progress;
          $this->progress = $progress->progress;
          $this->progress_message = $progress->message;
        }
        else {
          $this->progress = 0;
          $this->progress_message = '';
        }
      }
      $object = clone $this;
      $message = (object) [
        'channel' => 'background_process',
        'data' => (object) [
          'action' => $action,
          'background_process' => $object,
          'timestamp' => microtime(TRUE),
        ],
        'callback' => 'nodejsBackgroundProcess',
      ];
      drupal_alter('background_process_message', $message);
      nodejs_send_content_channel_message($message);
    }
  }

}
