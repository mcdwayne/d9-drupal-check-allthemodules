<?php

namespace Drupal\text_rotator_filter\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a base filter for Text Rotator filter.
 *
 * @Filter(
 *   id = "filter_text_rotator",
 *   module = "text_rotator_filter",
 *   title = @Translation("Text Rotator filter"),
 *   description = @Translation("Enables simple text rotator filter using <code>[rotate]foo|bar[/rotate]</code> syntax"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   cache = FALSE,
 *   weight = 0
 * )
 */
class TextRotatorFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'animation' => 'dissolve',
      'speed' => 2000,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['animation'] = [
      '#type' => 'select',
      '#title' => $this->t('Animation style'),
      '#default_value' => $this->settings['animation'],
      '#options' => [
        'dissolve' => $this->t('Dissolve'),
        'fade' => $this->t('Fade'),
        'flip' => $this->t('Flip'),
        'flipUp' => $this->t('flipUp'),
        'flipCube' => $this->t('flipCube'),
        'flipCubeUp' => $this->t('flipCubeUp'),
        'spin' => $this->t('Spin'),
      ],
      '#description' => $this->t('Pick an animation to rotate though words.'),
    ];
    $form['speed'] = [
      '#type' => 'number',
      '#title' => $this->t('Animation speed'),
      '#default_value' => $this->settings['speed'],
      '#description' => $this->t('Number of milliseconds between rotations.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    if (!isset($configuration['settings']['animation'])) {
      $configuration['settings']['animation'] = 'dissolve';
      $configuration['settings']['speed'] = 3000;
    }
    parent::setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $pattern = "/(\[rotate\])(.*?)(\[\/rotate\])/";
    $count = 0;
    $text = preg_replace_callback($pattern, function($matches) use (&$count) {
      $count++;
      return '<span class="filter-rotate">' . $matches[2] . '</span>';
    }, $text, $limit = -1, $count);
    $result = new FilterProcessResult($text);
    if ($count) {
      $result->addAttachments([
        'library' => [
          'text_rotator_filter/text_rotator_filter',
        ],
      ]);
      $result->addAttachments([
        'drupalSettings' => [
          'text_rotator_filter' => [
            'animation' => $this->settings['animation'],
            'speed' => $this->settings['speed'],
          ]
        ],
      ]);
    }
    return $result;
  }
}
