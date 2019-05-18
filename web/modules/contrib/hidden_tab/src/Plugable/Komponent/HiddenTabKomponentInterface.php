<?php

namespace Drupal\hidden_tab\Plugable\Komponent;

use Drupal\Core\Entity\EntityInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Entity\HiddenTabPlacementInterface;
use Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase;

/**
 * For placing komponents in the page.
 *
 * Komponents can be put into regions. A komponent can be a view, block, ...
 *
 * @see \Drupal\hidden_tab\Entity\HiddenTabPlacementInterface
 */
interface HiddenTabKomponentInterface extends HiddenTabPluginInterfaceBase {

  const PID = 'hidden_tab_komponent';

  /**
   * Such as views, block, ...
   *
   * @return string
   *   Such as views, block, ...
   *
   * @see \Drupal\hidden_tab\Plugable\Komponent\HiddenTabKomponentInterface::komponentTypeLabel()
   */
  public function komponentType(): string;

  /**
   * Such as Views, Blocks, ...
   *
   * @return string
   *   Such as Views, Blocks, ...
   *
   * @see \Drupal\hidden_tab\Plugable\Komponent\HiddenTabKomponentInterface::komponentType()
   */
  public function komponentTypeLabel(): string;

  /**
   * Komponents available by this plugin (such as list of all views).
   *
   * @return array
   *   Id to label array of komponents available by this plugin.
   */
  public function komponents(): array;

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being visited.
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page
   *   The page being visited.
   * @param \Drupal\hidden_tab\Entity\HiddenTabPlacementInterface $placement
   *   The placement to be rendered.
   *
   * @return string
   *   Renderable output.
   */
  public function render(EntityInterface $entity,
                         HiddenTabPageInterface $page,
                         HiddenTabPlacementInterface $placement);

}
