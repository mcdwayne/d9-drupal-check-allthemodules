<?php
/**
 * © 2016 Valiton GmbH
 */

namespace Drupal\advertising_products_generic\Form;

use Drupal\advertising_products\Form\AdvertisingProductForm;
use Drupal\Core\Form\FormStateInterface;

class AdvertisingProductFormGeneric extends AdvertisingProductForm  {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\advertising_products\Entity\AdvertisingProduct */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    return $form;
  }

}