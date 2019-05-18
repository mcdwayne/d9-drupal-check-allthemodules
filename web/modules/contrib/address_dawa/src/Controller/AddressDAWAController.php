<?php

namespace Drupal\address_dawa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\address_dawa\AddressDawaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AddressDAWAController.
 */
class AddressDAWAController extends ControllerBase {

  /**
   * AddressDawa servcie.
   *
   * @var \Drupal\address_dawa\AddressDawaInterface
   */
  protected $addressDawa;

  /**
   * {@inheritdoc}
   */
  public function __construct(AddressDawaInterface $address_dawa) {
    $this->addressDawa = $address_dawa;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('address_dawa.address_dawa')
    );
  }

  /**
   * Autocomplete controller to fetch Addresser from Dawa service.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Autocomplete Json response.
   */
  public function fetchDawaAdresse(Request $request) {
    $matches = $this->fetchDawaAddress($request->query->get('q'), 'adresse');
    // The Json response should not be cacheable.
    return new JsonResponse($matches);
  }

  /**
   * Autocomplete controller to fetch Adgangsaddresse from Dawa service.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Autocomplete Json response.
   */
  public function fetchDawaAdgangsadresse(Request $request) {
    $matches = $this->fetchDawaAddress($request->query->get('q'), 'adgangsadresse');
    // The Json response should not be cacheable.
    return new JsonResponse($matches);
  }

  /**
   * Fetch DAWA address.
   *
   * @param string $query
   *   Search query.
   * @param string $address_type
   *   Address type.
   *
   * @return array
   *   Address matched results from DAWA.
   */
  protected function fetchDawaAddress($query, $address_type) {
    $matches = [];
    $options = [
      'q' => $query,
      'type' => $address_type,
      'caretpos' => 4,
      'fuzzy' => '',
    ];
    $addresses = $this->addressDawa->fetchAddress($options);
    if (!empty($addresses)) {
      foreach ($addresses as $address) {
        $matches[] = (string) str_replace(',', '', $address->tekst);
      }
    }
    return $matches;
  }

}
