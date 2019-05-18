<?php

namespace Drupal\atm\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AtmAbstractForm.
 */
abstract class AtmAbstractForm extends FormBase {

  /**
   * Provides helper for ATM.
   *
   * @var \Drupal\atm\Helper\AtmApiHelper
   */
  protected $atmApiHelper;

  /**
   * Client for API.
   *
   * @var \Drupal\atm\AtmHttpClient
   */
  protected $atmHttpClient;

  /**
   * Default theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandler
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('atm.helper'),
      $container->get('atm.http_client'),
      $container->get('theme_handler')
    );
  }

  /**
   * Return AtmApiHelper.
   *
   * @return \Drupal\atm\Helper\AtmApiHelper
   *   Return AtmApiHelper.
   */
  protected function getHelper() {
    return $this->atmApiHelper;
  }

  /**
   * Return AtmHttpClient.
   *
   * @return \Drupal\atm\AtmHttpClient
   *   Return AtmHttpClient.
   */
  protected function getAtmHttpClient() {
    return $this->atmHttpClient;
  }

  /**
   * Return Default theme handler.
   *
   * @return \Drupal\Core\Extension\ThemeHandler
   *   Default theme handler.
   */
  public function getThemeHandler() {
    return $this->themeHandler;
  }

  /**
   * Return lazy_builder for status message.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   Message text.
   *
   * @return array
   *   Return lazy_builder.
   */
  protected function getStatusMessage(TranslatableMarkup $message) {
    return $this->getMessage('status', $message);
  }

  /**
   * Return lazy_builder for warning message.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   Message text.
   *
   * @return array
   *   Return lazy_builder.
   */
  protected function getWarningMessage(TranslatableMarkup $message) {
    return $this->getMessage('warning', $message);
  }

  /**
   * Return lazy_builder for error message.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   Message text.
   *
   * @return array
   *   Return lazy_builder.
   */
  protected function getErrorMessage(TranslatableMarkup $message) {
    return $this->getMessage('error', $message);
  }

  /**
   * Return lazy_builder.
   *
   * @param string $type
   *   Type of message.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   Message.
   *
   * @return array
   *   Return lazy_builder.
   */
  private function getMessage($type, TranslatableMarkup $message) {
    $return = [
      '#theme' => 'status_messages',
      '#message_list' => [],
    ];

    $return['#message_list'][$type][] = $message;

    return $return;
  }

  /**
   * Convert `--` to `.` in element name.
   *
   * @param string $elementName
   *   Element name.
   *
   * @return mixed
   *   Element name.
   */
  protected function prepareElementName($elementName) {
    return str_replace('--', '.', $elementName);
  }

  /**
   * Get enabled theme on frontend.
   *
   * @return \Drupal\Core\Extension\Extension|mixed
   *   Return enabled theme.
   */
  protected function getDefaultTheme() {
    return $this->themeHandler->getTheme($this->themeHandler->getDefault());
  }

  /**
   * Return dialog options.
   */
  protected function getModalDialogOptions() {
    return [
      'maxWidth' => '90%',
      'classes' => [
        "ui-dialog" => "highlight atm-dialog",
      ],
      'dialogClass' => 'atm-dialog',
    ];
  }

}
