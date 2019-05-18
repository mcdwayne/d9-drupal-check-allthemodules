<?php

namespace Drupal\prefetcher;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\prefetcher\Entity\PrefetcherUriInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for prefetcher crawlers.
 *
 * A crawler provides methods for accessing and parsing websites.
 *
 * @see plugin_api
 */
interface CrawlerInterface extends ContainerFactoryPluginInterface, PluginInspectionInterface, PluginFormInterface {
  /**
   * Crawls a given url.
   *
   * @param PrefetcherUriInterface $prefetcher_uri prefetcher entity
   */
  public function crawl(PrefetcherUriInterface $prefetcher_uri);

  /**
   * Crawls a given url.
   *
   * @param PrefetcherUriInterface[] $prefetcher_uris prefetcher entities
   */
  public function crawlMultiple(array $prefetcher_uris);

  /**
   * Verifies that the Crawler is set up correctly.
   *
   * @return bool
   *   TRUE if the crawler is available on this machine, FALSE otherwise.
   */
  public static function isAvailable();

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $ajax_settings
   *
   * @return mixed
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $ajax_settings = []);

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state);

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state);

}
