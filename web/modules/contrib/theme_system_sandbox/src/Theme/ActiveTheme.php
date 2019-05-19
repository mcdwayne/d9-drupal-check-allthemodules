<?php

/**
 * @file
 * Contains \Drupal\theme_system_sandbox\Theme\ActiveTheme.
 */

namespace Drupal\theme_system_sandbox\Theme;

use Drupal\Core\Theme\ActiveTheme as DefaultActiveTheme;

class ActiveTheme extends DefaultActiveTheme {

  /**
   * The component overrides.
   *
   * @var array
   */
  protected $componentOverrides;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values) {
    parent::__construct($values);

    // Add default values for the additional parameters.
    $values += [
      'component_overrides' => [],
    ];

    $this->componentOverrides = $values['component_overrides'];
  }

  /**
   * Gets component overrides.
   *
   * @return array
   */
  public function getComponentOverrides() {
    return $this->componentOverrides;
  }

}
