<?php

namespace Drupal\address_cn;

/**
 * Defines the default address cn manager.
 */
class AddressCnManager implements AddressCnManagerInterface {

  /**
   * An associative array of address settings.
   */
  protected $options;

  /**
   * The flipped city array keyed by code.
   *
   * @var array
   */
  protected $cities_flip;

  /**
   * The subdivision repository.
   *
   * @var \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface
   */
  protected $subdivisionRepository;

  /**
   * Constructs a new address cn manager instance.
   *
   * @param array $options
   *   An associative array of area settings.
   */
  public function __construct($options = []) {
    $this->options = $options;
    $this->cities_flip = array_flip(static::CITIES);
  }

  /**
   * {@inheritdoc}
   */
  public function hasChildren($code, array $parents) {
    // The $parents begins with the country code 'CN'.
    $parents_depth = count($parents);
    return $parents_depth == 1 || ($parents_depth == 2 && static::PROVINCES[$parents[1]] == 3 && !isset($this->cities_flip[$code]));
  }

  /**
   * {@inheritdoc}
   */
  public function sortProvinces(array &$provinces) {
    $preferred = array_flip(array_keys(static::PROVINCES));
    return uksort($provinces, function ($a, $b) use ($preferred) {
      return $preferred[$a] - $preferred[$b];
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getSubdivision($code) {
//    $this->subdivisionRepository->get()
  }

}
