<?php

namespace Drupal\taarikh\Plugin\TaarikhAlgorithm;

use Drupal\Core\Annotation\Translation;
use Drupal\taarikh\Annotation\TaarikhAlgorithm;
use Drupal\taarikh\TaarikhAlgorithmPluginInterface;

/**
 * Class FatimidAstronomical
 *
 * @TaarikhAlgorithm(
 *   id = "fatimid_astronomical",
 *   title = @Translation("Fatimid Astronomical"),
 *   algorithm_class = "Hussainweb\DateConverter\Algorithm\Hijri\HijriFatimidAstronomical"
 * )
 */
class FatimidAstronomical extends TaarikhAlgorithmPluginBase implements TaarikhAlgorithmPluginInterface { }
