<?php

namespace Drupal\fitbit_views\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Avatar views field plugin.
 *
 * @ViewsField("fitbit_avatar")
 */
class Avatar extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['avatar_size'] = ['default' => 'avatar'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['avatar_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Image size'),
      '#options' => [
        'avatar' => $this->t('Default (100px)'),
        'avatar150' => $this->t('Medium (150px)'),
      ],
      '#default_value' => $this->options['avatar_size'],
      '#description' => $this->t('Choose the size avatar you would like to use.'),
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $avatar = $this->getValue($values);
    if ($avatar) {
      return [
        '#theme' => 'image',
        '#uri' => $avatar[$this->options['avatar_size']],
        '#alt' => $this->t('Avatar'),
      ];
    }
  }
}
