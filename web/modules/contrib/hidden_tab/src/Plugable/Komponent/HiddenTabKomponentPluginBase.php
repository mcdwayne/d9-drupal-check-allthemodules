<?php

namespace Drupal\hidden_tab\Plugable\Komponent;

use Drupal\hidden_tab\Plugable\HiddenTabPluginBase;

/**
 * Base class for hidden_tab_komponent plugins.
 */
abstract class HiddenTabKomponentPluginBase extends HiddenTabPluginBase implements HiddenTabKomponentInterface {

  /**
   * See komponentType().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\Komponent\HiddenTabKomponentInterface::komponentType()
   */
  protected $komponentType;

  /**
   * See komponentTypeLabel().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\Komponent\HiddenTabKomponentInterface::komponentTypeLabel()
   */
  protected $komponentTypeLabel;

  /**
   * {@inheritdoc}
   */
  public function komponentType(): string {
    return $this->komponentType;
  }

  /**
   * {@inheritdoc}
   */
  public function komponentTypeLabel(): string {
    return $this->komponentTypeLabel;
  }

}
