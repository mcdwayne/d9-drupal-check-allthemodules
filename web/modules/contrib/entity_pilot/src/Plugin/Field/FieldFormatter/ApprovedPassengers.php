<?php

namespace Drupal\entity_pilot\Plugin\Field\FieldFormatter;

use Drupal\options\Plugin\Field\FieldFormatter\OptionsDefaultFormatter;

/**
 * Plugin implementation of the 'ep_approved_passengers' formatter.
 *
 * @FieldFormatter(
 *   id = "ep_approved_passengers",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "ep_approved_passengers",
 *   }
 * )
 */
class ApprovedPassengers extends OptionsDefaultFormatter {}
