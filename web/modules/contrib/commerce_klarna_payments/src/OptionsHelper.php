<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments;

use Drupal\commerce_klarna_payments\Klarna\Data\Payment\OptionsInterface;
use Drupal\commerce_klarna_payments\Klarna\Request\Payment\Options;
use Drupal\commerce_klarna_payments\Plugin\Commerce\PaymentGateway\Klarna;

/**
 * Provides a helper trait for Klarna's options setting.
 */
trait OptionsHelper {

  /**
   * The payment plugin.
   *
   * @var \Drupal\commerce_klarna_payments\Plugin\Commerce\PaymentGateway\Klarna
   */
  protected $plugin;

  /**
   * Gets the plugin configuration.
   *
   * @return array
   *   The configuration.
   */
  protected function getPluginConfiguration() : array {
    if ($this instanceof Klarna) {
      return $this->getConfiguration();
    }
    if (!$this->plugin) {
      throw new \LogicException('$this->plugin is not defined.');
    }
    return $this->plugin->getConfiguration();
  }

  /**
   * List of default options.
   *
   * @return array
   *   The default options.
   */
  protected function getDefaultOptions() : array {
    return [
      'color_button' => $this->t('Button color'),
      'color_button_text' => $this->t('Button text color'),
      'color_checkbox' => $this->t('Checkbox color'),
      'color_checkbox_checkmark' => $this->t('Checkbox checkmark color'),
      'color_header' => $this->t('Header color'),
      'color_link' => $this->t('Link color'),
      'color_border' => $this->t('Border color'),
      'color_border_selected' => $this->t('Border selected color'),
      'color_text' => $this->t('Text color'),
      'color_details' => $this->t('Details color'),
      'color_text_secondary' => $this->t('Secondary text color'),
      'radius_border' => $this->t('Border radius'),
    ];
  }

  /**
   * Gets the options object.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\Payment\OptionsInterface
   *   The options.
   */
  protected function getOptions() : OptionsInterface {
    return Options::create($this->getPluginConfiguration()['options']);
  }

}
