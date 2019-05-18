<?php

namespace Drupal\datagouvfr_geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ProviderUsingHandlerBase;

/**
 * Provides a geocoder provider based on adress.data.gouv.fr API .
 *
 * @GeocoderProvider(
 *   id = "adress_data_gouv_fr",
 *   name = "adresse.data.gouv.fr",
 *   handler = "\Drupal\datagouvfr_geocoder\Geocoder\Provider\AdressDataGouv"
 * )
 */
class AdressDataGouv extends ProviderUsingHandlerBase {}
