<?php

namespace Drupal\web_page_archive_puppeteer_render\Plugin\CaptureUtility;

use Drupal\Core\Form\FormStateInterface;
use Drupal\web_page_archive\Plugin\ConfigurableCaptureUtilityBase;
use Drupal\web_page_archive_puppeteer_render\Plugin\CaptureResponse\PuppeteerRenderResponse;

/**
 * Puppeteer render capture utility.
 *
 * @CaptureUtility(
 *   id = "web_page_archive_puppeteer_render_capture",
 *   label = @Translation("Puppeteer render capture utility", context = "Web Page Archive"),
 *   description = @Translation("Puppeteer render capture utility.", context = "Web Page Archive")
 * )
 */
class PuppeteerRenderUtlity extends ConfigurableCaptureUtilityBase {

  /**
   * Most recent response.
   *
   * @var string|null
   */
  private $response = NULL;

  /**
   * {@inheritdoc}
   */
  public function capture(array $data = []) {

    // Handle missing URLs.
    if (!isset($data['url'])) {
      throw new \Exception('Capture URL is required');
    }

    $width = (int) $this->configuration['width'];
    $height = (int) $this->configuration['height'];
    $url = $this->configuration['url'];

    // Determine file locations.
    $filename = $this->getFileName($data, 'png');

    $request = "$url?url=" . $data['url'] . "&variant=screenshot&width=$width&height=$height";

    // Save file and set our response.
    \Drupal::httpClient()->request('GET', $request, ['sink' => $filename]);

    $this->response = new PuppeteerRenderResponse($filename, $data['url']);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = \Drupal::configFactory()->get('web_page_archive_puppeteer_render.capture.settings');
    return [
      'height' => $config->get('defaults.height'),
      'width' => $config->get('defaults.width'),
      'url' => $config->get('defaults.url'),
      'image_type' => $config->get('defaults.image_type'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#description' => $this->t('Capture width.'),
      '#default_value' => $this->configuration['width'],
    ];
    $form['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#description' => $this->t('Capture height.'),
      '#default_value' => $this->configuration['height'],
    ];
    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#description' => $this->t('URL to Puppeteer render instance'),
      '#default_value' => $this->configuration['url'],
    ];
    $image_types = [
      'png' => 'png',
      'jpeg' => 'jpeg',
    ];
    $form['image_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Image type'),
      '#options' => $image_types,
      '#empty_option' => $this->t('Select an image type'),
      '#default_value' => $this->configuration['image_type'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $fields = [
      'width',
      'image_type',
      'height',
      'url',
    ];

    foreach ($fields as $field) {
      $this->configuration[$field] = $form_state->getValue($field);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildSystemSettingsForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->get('web_page_archive_puppeteer_render.capture.settings');

    $form['magick_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ImageMagick path'),
      '#description' => $this->t('Full path to magick binary on your system. (e.g. /usr/local/bin/magick): Requires ImageMagick 7.'),
      '#default_value' => $config->get('system.magick_path'),
    ];
    $form['magick_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ImageMagick highlight color'),
      '#description' => $this->t('The color used to identify pixel discrepancies. (e.g. #fff000)'),
      '#default_value' => $config->get('system.magick_color'),
    ];
    $image_types = [
      'png' => 'png',
      'jpeg' => 'jpeg',
    ];
    $form['image_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Image type'),
      '#options' => $image_types,
      '#empty_option' => $this->t('Select an image type'),
      '#default_value' => $this->configuration['image_type'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanupRevision($revision_id) {
    PuppeteerRenderResponse::cleanupRevision($revision_id);
  }

}
