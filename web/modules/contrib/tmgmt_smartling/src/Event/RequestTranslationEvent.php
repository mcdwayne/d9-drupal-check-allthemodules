<?php

namespace Drupal\tmgmt_smartling\Event;

use Symfony\Component\EventDispatcher\Event;

class RequestTranslationEvent extends Event {
  const REQUEST_TRANSLATION_EVENT = 'tmgmt_smartling.request_translation';

  protected $job;

  public function __construct($job) {
    $this->job = $job;
  }

  public function getJob() {
    return $this->job;
  }
}
