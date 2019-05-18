<?php

namespace Drupal\flexiform\FormComponent;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\flexiform\FlexiformEntityFormDisplay;

/**
 * Defines an interface for pulling service dependencies into form components.
 */
interface ContainerFactoryFormComponentInterface {

  /**
   * Creates an instance of the form componenent.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the componenet.
   * @param string $name
   *   The component name.
   * @param array $options
   *   The component options.
   * @param \Drupal\flexiform\FlexiformEntityFormDisplay $form_display
   *   The flexiform entity form display.
   */
  public static function create(ContainerInterface $container, $name, array $options, FlexiformEntityFormDisplay $form_display);

}
