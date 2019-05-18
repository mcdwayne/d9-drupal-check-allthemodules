<?php

namespace Drupal\just_giving;
use Drupal\just_giving\JustGivingClient;

/**
 * Class JustGivingCountries.
 */
class JustGivingCountries implements JustGivingCountriesInterface {

  /**
   * Drupal\just_giving\JustGivingClient definition.
   *
   * @var \Drupal\just_giving\JustGivingClient
   */
  protected $justGivingClient;

  /**
   * JustGivingCountries constructor.
   *
   * @param \Drupal\just_giving\JustGivingClientInterface $just_giving_client
   */
  public function __construct(JustGivingClientInterface $just_giving_client) {
    $this->justGivingClient = $just_giving_client;
  }

  /**
   * @return mixed
   */
  public function getCountriesFormList() {
    if ($this->justGivingClient->jgLoad() == FALSE) {
      return NULL;
    }
    else {
      $jgCountries = $this->justGivingClient->jgLoad()->Countries->Countries();
      $countryList = ['0' => "Please Select a Country"];
      foreach ($jgCountries as $index) {
        $countryCode = $index->countryCode;
        $name = $index->name;
        $countryList[$countryCode] = $name;
      }
      return $countryList;
    }
  }

}
