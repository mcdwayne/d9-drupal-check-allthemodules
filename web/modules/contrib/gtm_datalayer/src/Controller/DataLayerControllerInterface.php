<?php

namespace Drupal\gtm_datalayer\Controller;

/**
 * Defines the interface for GTM dataLayer Controller.
 */
interface DataLayerControllerInterface {

  /**
   * Returns the GTM dataLayer module configured indicator.
   *
   * @return bool
   *   TRUE if the GTM dataLayer module is configured.
   */
  public function isConfigured();

  /**
   * Returns the GTM dataLayer module debug enabled indicator.
   *
   * @return bool
   *   TRUE if the GTM dataLayer module debug is enabled.
   */
  public function isDebugEnabled();

  /**
   * Returns the GTM dataLayer module enabled indicator.
   *
   * @return bool
   *   TRUE if the GTM dataLayer module is enabled.
   */
  public function isEnabled();

  /**
   * Builds Google Tag Manager (noscript) code.
   *
   * @param array $page_top
   *   A renderable array representing the top of the page.
   */
  public function buildGtmNoScript(array &$page_top);

  /**
   * Builds Google Tag Manager (script) code and attach needed libraries.
   *
   * @param array &$attachments
   *   An array that you can add attachments to.
   */
  public function buildGtmScripts(array &$attachments);

}
