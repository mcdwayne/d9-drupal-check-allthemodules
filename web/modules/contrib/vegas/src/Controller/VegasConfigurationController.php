<?php

namespace Drupal\vegas\Controller;

class VegasConfigurationController
{
  public function configure() {
    $form = \Drupal::formBuilder()
      ->getForm('Drupal\vegas\Form\FormVegasConfiguration');
    return $form;
  }
}
