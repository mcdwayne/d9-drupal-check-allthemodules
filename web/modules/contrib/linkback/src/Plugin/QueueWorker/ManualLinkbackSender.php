<?php

namespace Drupal\linkback\Plugin\QueueWorker;

/**
 * A Linkback Sender that fire the events of sending linkbacks.
 *
 * Via a manual action triggered by an admin.
 *
 * @QueueWorker(
 *   id = "manual_linkback_sender",
 *   title = @Translation("Manual Linkback Sender"),
 * )
 */
class ManualLinkbackSender extends LinkbackSender {}
