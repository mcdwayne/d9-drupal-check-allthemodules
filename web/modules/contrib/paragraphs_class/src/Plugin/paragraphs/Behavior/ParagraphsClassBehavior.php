<?php

namespace Drupal\paragraphs_class\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Custom paragraph wrapper class.
 *
 * @ParagraphsBehavior(
 *   id = "paragraphs_class_paragraph_class",
 *   label = @Translation("Paragraphs wrapper class"),
 *   description = @Translation("Allows to set wrapper class for paragraphs."),
 *   weight = 0,
 * )
 */
class ParagraphsClassBehavior extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    $class = $paragraph->getBehaviorSetting($this->getPluginId(), 'wrapper_class');
    $build['#attributes']['class'][] = $class;

  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form['wrapper_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wrapper class'),
      '#description' => $this->t('Wrapper HTML class'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'wrapper_class'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    return [$this->t('Wrapper class element')];
  }

}
