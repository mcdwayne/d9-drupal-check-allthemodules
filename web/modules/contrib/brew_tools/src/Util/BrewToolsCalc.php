<?php

/**
 * @file
 * This class holds a collection of methods based on formulas.
 */
namespace Drupal\brew_tools\Util;
use Drupal\Core\Form\FormState;

class BrewToolsCalc {
  protected $form_values;
  public function __construct(FormState $form_state) {
      $this->form_values = $form_state->getValues();
  }

  /**
   * Works out Strike Water Temp.
   *
   * @param array $form_values
   *   Values from form.
   * 
   * @return float
   *   The strike temp.
   */
  public function strikeTemp() {
    // Initial Infusion Equation:
    // strike temp = desired mash temp x (water l + (0.4 x kg malt))
    // – (0.4 x kg malt x malt temp)/water l.
    $rtn = 0;
    if(!empty($this->form_values['mash_temp'])){
    $rtn = round((($this->form_values['mash_temp'] * ($this->form_values['water_volume'] + (0.4 * $this->form_values['malt_weight'])) - (0.4 * $this->form_values['malt_weight'] * $this->form_values['malt_temp'])) / $this->form_values['water_volume']), 1);
    }
    return $rtn;
  }

  /**
   * Works out the hop ultilisation factor.
   * 
   * @param array $form_values
   *   Values from form.
   * 
   * @return float
   *   Utilistaion factor.
   */
  public function utilFactor() {
    return round($this->bignessValue($this->form_values['wort_gravity']) * $this->boilTimeFactor($this->form_values['time']) * 100, 1);
  }

  /**
   * Calculates bigness value.
   * 
   * @param float $wort_gravity
   *   The gravity of the wort.
   * 
   * @return float 
   *   The bigness factor.
   */
  protected function bignessValue($wort_gravity) {
    return (1.65 * pow(0.000125, $wort_gravity - 1));
  }

  /**
   * Calculates boil time factor.
   * 
   * @param int $time 
   *   Minutes.
   * 
   * @return float 
   *   The boil time factor.
   */
  protected function boilTimeFactor($time) {
    $config = \Drupal::config("brew_tools.settings");
    return ((1 - exp(-$config->get('utilization_time_curve') * $time)) / $config->get('max_utilization'));
  }

  /**
   * Calulates ABV.
   * 
   * @param array $form_values
   *   Values from form.
   * 
   * @return float
   *   The ABV.
   */
  public function getABV() {
    return round(($this->form_values['og'] - $this->form_values['fg']) * 131, 2);
  }

  /**
   * Converts between Plato and Gravity
   * 
   * @param type $form_state
   * 
   * @return int The result
   */
  public function getPlatoGrav() { 
  //plato_grav_value
    //{Plato/(258.6-([Plato/258.2]*227.1)}+1 = Gravity
    //°Plato = -616.868+1111.14*SG-630.272*SG^2+135.997*SG^3

    switch ($this->form_values['plato_grav_radio']) {
      case "p":
        return round(-616.868 + 1111.14 * $this->form_values['plato_grav_value'] - 630.272 * ($this->form_values['plato_grav_value'] * $this->form_values['plato_grav_value']) + 135.997 * ($this->form_values['plato_grav_value'] * $this->form_values['plato_grav_value'] * $this->form_values['plato_grav_value']), 1);

      case "g":
        return round($this->form_values['plato_grav_value'] / (258.6 - (($this->form_values['plato_grav_value'] / 258.2) * 227.1)) + 1, 3);
    }
  }

}
