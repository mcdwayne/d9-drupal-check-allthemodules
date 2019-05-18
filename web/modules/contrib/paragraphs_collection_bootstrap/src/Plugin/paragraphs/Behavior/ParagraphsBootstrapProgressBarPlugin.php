<?php

namespace Drupal\paragraphs_collection_bootstrap\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Provides a Paragraphs Bootstrap Progress Bar plugin.
 *
 * @ParagraphsBehavior(
 *   id = "pcb_progress_bar",
 *   label = @Translation("Progress Bar"),
 *   description = @Translation("Sets Bootstrap 4 Progress Bar behavior to paragraph."),
 *   weight = 3
 * )
 */
class ParagraphsBootstrapProgressBarPlugin extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    if ($paragraph->hasField('field_pcb_progress_bar_height')) {
      $height = $paragraph->field_pcb_progress_bar_height->value ?: $paragraph->getFieldDefinition('field_pcb_progress_bar_height')->getDefaultValueLiteral()[0]['value'];
    }
    else {
      $height = 16;
    }
    if ($paragraph->hasField('field_pcb_progress_bar_width')) {
      $width = $paragraph->field_pcb_progress_bar_width->value ?: $paragraph->getFieldDefinition('field_pcb_progress_bar_width')->getDefaultValueLiteral()[0]['value'];
      $min = $paragraph->getFieldDefinition('field_pcb_progress_bar_width')->getSetting('min');
      $max = $paragraph->getFieldDefinition('field_pcb_progress_bar_width')->getSetting('max');
    }
    else {
      $width = 25;
      $min = 0;
      $max = 100;
    }
    $build['#attached']['library'] = [
      'bs_lib/progress',
      'paragraphs_collection_bootstrap/progress',
    ];
    $build['#attributes'] = [
      'class' => [
        'progress-bar',
      ],
      'role' => 'progressbar',
      'aria-valuenow' => $width,
      'aria-valuemin' => $min,
      'aria-valuemax' => $max,
      'style' => 'width: ' . $width . '%; height: ' . $height . 'px;',
    ];
    if ($paragraph->getBehaviorSetting($this->getPluginId(), 'striped')) {
      $build['#attributes']['class'][] = 'progress-bar-striped';
    }
    if ($paragraph->getBehaviorSetting($this->getPluginId(), 'animated')) {
      $build['#attributes']['class'][] = 'progress-bar-animated';
    }
    if (!$paragraph->getBehaviorSetting($this->getPluginId(), 'label')) {
      unset($build['field_pcb_progress_bar_width']);
    }
    if ($style = $paragraph->getBehaviorSetting('style', 'style')) {
      $build['#attributes']['class'][] = $style;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form['striped'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Striped'),
      '#description' => $this->t('Apply a stripe via CSS gradient over the progress bar’s background color.'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'striped'),
    ];

    $form['animated'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Animated'),
      '#description' => $this->t('The striped gradient can also be animated. Animate the stripes right to left via CSS3 animations.'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'animated'),
    ];

    $form['label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Add/remove label to your progress bar.'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'label', TRUE),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    return [
      $this->t('Striped: @striped, Animated: @animated, Label: @label', [
        '@striped' => $paragraph->getBehaviorSetting($this->getPluginId(), 'striped') ? 'YES' : 'NO',
        '@animated' => $paragraph->getBehaviorSetting($this->getPluginId(), 'animated') ? 'YES' : 'NO',
        '@label' => $paragraph->getBehaviorSetting($this->getPluginId(), 'label') ? 'YES' : 'NO',
      ]),
    ];
  }

}
