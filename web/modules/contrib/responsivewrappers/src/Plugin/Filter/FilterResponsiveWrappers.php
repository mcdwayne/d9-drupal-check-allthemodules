<?php

namespace Drupal\responsivewrappers\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Check the content and add responsive classes and wrappers.
 *
 * @Filter(
 *   id = "filter_bootstrap_responsive_wrapper",
 *   title = @Translation("Responsive wrappers filter"),
 *   description = @Translation("Scans content to add responsive wrappers and classes to make it responsive."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE
 * )
 */
class FilterResponsiveWrappers extends FilterBase {

  /**
   * Implements filter processor.
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if ($this->settings['responsive_iframe'] || $this->settings['responsive_table'] || $this->settings['responsive_image']) {
      // Module base config.
      $config = \Drupal::config('responsivewrappers.settings');

      $dom = Html::load($text);

      // Responsive video wrapper.
      if ($this->settings['responsive_iframe']) {

        $iframes = $dom->getElementsByTagName('iframe');
        $video_pattern = $this->settings['responsive_iframe_pattern'];

        foreach ($iframes as $iframe) {
          // Video detection pattern.
          $iframe_src = $iframe->getAttribute('src');
          if (preg_match($video_pattern, $iframe_src)) {

            // Video wrapper.
            $video_wrapper_class = ($iframe->parentNode->tagName === 'div') ? $iframe->parentNode->getAttribute('class') : '';
            // If exists, replace video-embed-field functionality.
            if (strpos($video_wrapper_class, 'video-embed-field-responsive-video') !== FALSE) {
              $video_wrapper_class = str_replace('video-embed-field-responsive-video', 'embed-responsive embed-responsive-16by9', $video_wrapper_class);
              $iframe->parentNode->setAttribute('class', $video_wrapper_class);
            }
            elseif (strpos($video_wrapper_class, 'embed-responsive') === FALSE) {
              // If not exists create the wrapper.
              $video_wrapper = $dom->createElement('div');
              $video_wrapper->setAttribute('class', 'embed-responsive embed-responsive-16by9');
              $iframe = $iframe->parentNode->replaceChild($video_wrapper, $iframe);
              $video_wrapper->appendChild($iframe);
            }

            // Video class.
            $video_class = $iframe->getAttribute('class');
            if (empty($video_class) || !in_array('embed-responsive-item', explode(' ', $video_class))) {
              $iframe->setAttribute('class', trim($video_class . ' embed-responsive-item'));
            }
          }
        }
      }

      // Responsive table wrapper and class.
      if ($this->settings['responsive_table']) {
        $tables = $dom->getElementsByTagName('table');
        foreach ($tables as $table) {

          // Table wrapper.
          $table_wrapper_class = ($table->parentNode->tagName === 'div') ? $table->parentNode->getAttribute('class') : '';
          if (strpos($table_wrapper_class, 'table-responsive') === FALSE) {
            $table_wrapper = $dom->createElement('div');
            $table_wrapper->setAttribute('class', 'table-responsive');
            $table = $table->parentNode->replaceChild($table_wrapper, $table);
            $table_wrapper->appendChild($table);
          }

          // Table class.
          $table_class = $table->getAttribute('class');
          if (empty($table_class) || !in_array('table', explode(' ', $table_class))) {
            $table->setAttribute('class', trim($table_class . ' table'));
          }
        }
      }

      // Responsive image class.
      if ($this->settings['responsive_image']) {
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $image) {
          // Image class.
          $image_class = $image->getAttribute('class');
          $responsive_class = (4 === $config->get('version')) ? 'img-fluid' : 'img-responsive';

          if (strpos($image_class, $responsive_class) === FALSE) {
            $image->setAttribute('class', trim($image_class . ' ' . $responsive_class));
          }
        }
      }

      $result->setProcessedText(Html::serialize($dom));

      // Attach responsive CSS if needed.
      if ($config->get('add_css')) {
        if (4 === $config->get('version')) {
          $result->setAttachments(['library' => ['responsivewrappers/responsivewrappers_v4']]);
        }
        else {
          $result->setAttachments(['library' => ['responsivewrappers/responsivewrappers_v3']]);
        }
      }
    }

    return $result;
  }

  /**
   * Implements settings filter form.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['responsive_iframe'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Responsive video'),
      '#default_value' => isset($this->settings['responsive_iframe']) ? $this->settings['responsive_iframe'] : '',
      '#description' => $this->t('Add responsive wrapper for videos (16/9 aspect ratio).'),
    ];
    $form['responsive_iframe_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Source video pattern detection'),
      '#default_value' => isset($this->settings['responsive_iframe_pattern']) ? $this->settings['responsive_iframe_pattern'] : '#.*(youtube.|vimeo.).*#ui',
      '#description' => $this->t('Regular expresion for source video detection. This pattern evaluates scr iframe attribute.'),
    ];
    $form['responsive_table'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Responsive tables'),
      '#default_value' => isset($this->settings['responsive_table']) ? $this->settings['responsive_table'] : '',
      '#description' => $this->t('Add responsive wrapper for tables.'),
    ];
    $form['responsive_image'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Responsive images'),
      '#default_value' => isset($this->settings['responsive_image']) ? $this->settings['responsive_image'] : '',
      '#description' => $this->t('Add responsive class for images.'),
    ];

    return $form;
  }

}
