<?php

namespace Drupal\big_pipe_sessionless_test;

use Drupal\big_pipe_test\BigPipeTestController;
use Drupal\Core\Url;

class BigPipeSessionlessTestController extends BigPipeTestController {

  /**
   * {@inheritdoc}
   */
  public function test() {
    $build = parent::test();

    $has_session = \Drupal::service('session_configuration')->hasSession(\Drupal::requestStack()->getMasterRequest());

    // We can't test CSRF tokens for no-session requests.
    if (!$has_session) {
      unset($build['html_attribute_value_subset']);
    }

    // Edge case for no-session (and hence anonymous) responses: active links.
    // @see \Drupal\Core\EventSubscriber\ActiveLinkResponseFilter
    $build['active_link'] = [
      '#type' => 'link',
      '#title' => 'This should be marked active',
      '#url' => Url::fromRoute('big_pipe_sessionless_test'),
      '#options' => [
        'set_active_class' => TRUE,
      ],
    ];

    $build['inactive_link'] = [
      '#type' => 'link',
      '#title' => 'This should be marked inactive',
      '#url' => Url::fromRoute('<front>'),
      '#options' => [
        'set_active_class' => TRUE,
      ],
    ];

    return $build;
  }

}
