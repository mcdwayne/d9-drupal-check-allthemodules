<?php

namespace Drupal\cloudfront_purger\Plugin\Purge\Purger;

use Drupal\cloudfront_purger\CloudFrontInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Utility\Error;
use Drupal\purge\Plugin\Purge\Invalidation\EverythingInvalidation;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Invalidation\PathInvalidation;
use Drupal\purge\Plugin\Purge\Invalidation\WildcardPathInvalidation;
use Drupal\purge\Plugin\Purge\Purger\PurgerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CloudFront Purger implementation.
 *
 * @PurgePurger(
 *   id = "cloudfront",
 *   label = @Translation("CloudFront Purger"),
 *   configform = "\Drupal\cloudfront_purger\Form\CloudFrontPurgerConfigForm",
 *   cooldown_time = 0.0,
 *   description = @Translation("Uses AWS CloudFront API for creating invalidations."),
 *   multi_instance = FALSE,
 *   types = {"path", "wildcardpath", "everything"},
 * )
 */
class CloudFrontPurger extends PurgerBase {

  /**
   * The CloudFront invalidator.
   *
   * @var \Drupal\cloudfront_purger\CloudFrontInvalidatorInterface
   */
  protected $invalidator;

  /**
   * The settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * CloudFrontPurger constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\cloudfront_purger\CloudFrontInvalidatorInterface $invalidator
   *   The CloudFront invalidator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CloudFrontInvalidatorInterface $invalidator, ConfigFactoryInterface $config_factory) {
    $this->invalidator = $invalidator;
    $this->settings = $config_factory->get('cloudfront_purger.settings');

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
      $container->get('cloudfront_purger.invalidator'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(array $invalidations) {
    $paths = [];
    /* @var \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface $invalidation */
    foreach ($invalidations as $invalidation) {
      if ($invalidation instanceof EverythingInvalidation) {
        // Reset paths if we are invalidating everything.
        $paths = ['/*'];
        break;
      }
      elseif ($invalidation instanceof PathInvalidation || $invalidation instanceof WildcardPathInvalidation) {
        if (is_string($expression = $invalidation->getExpression())) {
          // Ensure we always have a leading slash.
          $paths[] = htmlentities('/' . ltrim($expression, ' /'));
        }
      }
      else {
        $invalidation->setState(InvalidationInterface::NOT_SUPPORTED);
      }
    }

    // Exit early if there are no paths.
    if (empty($paths)) {
      $this->logger()->info('No paths found to purge');
      return;
    }

    try {
      $distribution_id = $this->settings->get('distribution_id');
      $this->invalidator->invalidate($paths, $distribution_id);
      $this->setStates($invalidations, InvalidationInterface::SUCCEEDED);
    }
    catch (\Exception $e) {
      $this->logger()->error('%type: @message in %function (line %line of %file)', Error::decodeException($e));
      $this->setStates($invalidations, InvalidationInterface::FAILED);
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

  /**
   * Bulk updates invalidation states.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[] $invalidations
   *   The invalidations.
   * @param int $state
   *   The invalidation state to set.
   */
  protected function setStates(array $invalidations, $state) {
    foreach ($invalidations as $invalidation) {
      $invalidation->setState($state);
    }
  }

}
