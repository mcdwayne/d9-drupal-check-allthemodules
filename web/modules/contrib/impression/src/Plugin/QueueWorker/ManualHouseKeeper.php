<?php

namespace Drupal\impression\Plugin\QueueWorker;

/**
 * A house keeping for impression data  manual action triggered by an admin.
 *
 * @QueueWorker(
 *   id = "manual_house_keeper",
 *   title = @Translation("Manual Impression House Keeping"),
 * )
 */
class ManualHouseKeeper extends HouseKeeperBase {}
