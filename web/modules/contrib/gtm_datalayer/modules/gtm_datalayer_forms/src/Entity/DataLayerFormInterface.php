<?php

namespace Drupal\gtm_datalayer_forms\Entity;

use Drupal\gtm_datalayer\Entity\DataLayerInterface;

/**
 * Provides an interface defining a gtm_datalayer_form entity.
 */
interface DataLayerFormInterface extends DataLayerInterface {

  /**
   * Sets the form ID for the GTM dataLayer.
   *
   * @param string $form
   *   The form ID for the GTM dataLayer.
   *
   * @return $this
   */
  public function setFrom($form);

  /**
   * Returns the form ID for the GTM dataLayer.
   *
   * @return string
   *   The form ID for the GTM dataLayer.
   */
  public function getFrom();

  /**
   * Returns the dataLayer Processor instance.
   *
   * @return \Drupal\gtm_datalayer_forms\Plugin\DataLayerProcessorFormBaseInterface
   *   The GTM dataLayer Processor plugin instance for this GTM dataLayer.
   */
  public function getDataLayerProcessor();

}
