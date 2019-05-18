<?php

namespace Drupal\edgecast\Plugin\Purge\Purger;

use Drupal\edgecast\EdgeCastApi;
use Drupal\purge\Plugin\Purge\Invalidation\EverythingInvalidation;
use Drupal\purge\Plugin\Purge\Invalidation\PathInvalidation;
use Drupal\purge\Plugin\Purge\Invalidation\WildcardPathInvalidation;
use Drupal\purge\Plugin\Purge\Purger\PurgerBase;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\edgecast\Exceptions\FailedRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CloudFront Purger implementation.
 *
 * @PurgePurger(
 *   id = "edgecast",
 *   label = @Translation("Edge Cast Purger"),
 *   cooldown_time = 3.0,
 *   description = @Translation("Uses Edge Cast API for creating invalidations."),
 *   multi_instance = FALSE,
 *   types = {"path", "wildcardpath"},
 * )
 */
class EdgeCastPurger extends PurgerBase {

  /**
   * The CloudFront invalidator.
   *
   * @var \Drupal\edgecast\EdgeCastApi
   */
  protected $invalidator;

  /**
   * EdgeCastPurger constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\edgecast\EdgeCastApi $invalidator
   *   The EdgeCastApi invalidator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EdgeCastApi $invalidator) {
    $this->invalidator = $invalidator;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('edgecast.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(array $invalidations) {
    $paths = [];
    foreach ($invalidations as $key => $invalidation) {
      // Set default value as failed.
      $invalidations[$key]->setState(InvalidationInterface::FAILED);

      if ($invalidation instanceof EverythingInvalidation) {
        $paths[$key] = '/*';
        break;
      }

      if ($invalidation instanceof PathInvalidation || $invalidation instanceof WildcardPathInvalidation) {
        if (is_string($expression = $invalidation->getExpression())) {
          $paths[$key] = '/' . ltrim($expression, ' /');
        }
      }
      else {
        $invalidation->setState(InvalidationInterface::NOT_SUPPORTED);
      }
    }

    if (empty($paths)) {
      $this->logger()->info('No paths found to purge.');
      return;
    }

    // Purge paths.
    $fails = [];
    $cleared = [];
    foreach ($paths as $key => $path) {
      try {
        $result = $this->invalidator->purgePath($path);
        if ($result === TRUE) {
          $invalidations[$key]->setState(InvalidationInterface::SUCCEEDED);
          $cleared[] = $path;
        }
        else {
          $fails[] = $path;
        }
      }
      catch (FailedRequest $e) {
        // Stop make request on API if 400 error is given.
        $this->logger()->critical($this->t('API Exceeded Request Limit and Stop because of it.'));
        break;
      }
    }

    if (!empty($fails)) {
      $this->logger()->critical($this->t('The following items had failed: !items', ['!items' => implode('<br>', $fails)]));
    }

    if (!empty($cleared)) {
      $this->logger()->info($this->t('The following items had been cleared: !items', ['!items' => implode('<br>', $cleared)]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasRuntimeMeasurement() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeHint() {
    return 4.0;
  }
}