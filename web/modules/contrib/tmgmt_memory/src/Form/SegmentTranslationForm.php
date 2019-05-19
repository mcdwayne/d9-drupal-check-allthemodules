<?php

namespace Drupal\tmgmt_memory\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Form controller for the job edit forms.
 *
 * @ingroup tmgmt_memory_segment_translation
 */
class SegmentTranslationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\tmgmt_memory\SegmentTranslationInterface $segment_translation */
    $segment_translation = $this->entity;

    $form['info'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tmgmt-memory-info', 'clearfix']],
      '#weight' => 0,
      '#tree' => TRUE,
    ];

    $source = $segment_translation->getSource();
    $form['info']['source_language'] = array(
      '#title' => t('Source language'),
      '#type' => 'item',
      '#markup' => $source->getLanguage() ? $source->getLanguage()->getName() : $source->getLangcode(),
      '#prefix' => '<div id="tmgmt-ui-source-language" class="tmgmt-ui-source-language tmgmt-ui-info-item">',
      '#suffix' => '</div>',
      '#value' => $source->getLangcode(),
    );

    $target = $segment_translation->getTarget();
    $form['info']['target_language'] = array(
      '#title' => t('Target language'),
      '#type' => 'item',
      '#markup' => $target->getLanguage() ? $target->getLanguage()->getName() : $target->getLangcode(),
      '#prefix' => '<div id="tmgmt-ui-target-language" class="tmgmt-ui-target-language tmgmt-ui-info-item">',
      '#suffix' => '</div>',
      '#value' => $target->getLangcode(),
    );

    $form['state'] = [
      '#type' => 'checkbox',
      '#title' => t('State'),
      '#description' => t('Enable or disable the segment translation.'),
      '#default_value' => $segment_translation->getState(),
    ];

    if ($view = Views::getView('tmgmt_memory_usage_translations')) {
      $block = $view->preview('block_1', [$source->id(), $target->id()]);
      $form['items'] = [
        '#type' => 'item',
        '#title' => $view->getTitle(),
        '#prefix' => '<div class="tmgmt-memory-usages">',
        '#markup' => \Drupal::service('renderer')->render($block),
        '#attributes' => ['class' => ['tmgmt-memory-usages']],
        '#suffix' => '</div>',
        '#weight' => 10,
      ];
    }

    $form['#attached']['library'][] = 'tmgmt/admin';

    return $form;
  }

}
