<?php

namespace Drupal\cloudwords\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\area\AreaPluginBase;

/**
 * Views area CreateProjectButtonArea handler.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("cloudwords_create_project_button_area")
 */
class CreateProjectButtonArea extends AreaPluginBase {
  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    return $options;
  }
  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }
  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      $output = [];
      $uid = \Drupal::currentUser()->id();
      $count = cloudwords_project_user_count($uid);
      $output['fieldset'] = [
        '#type' => 'fieldset',
      ];
      $output['fieldset']['link'] = [
        '#title' => $this->t('Create Project'),
        '#type' => 'link',
        '#url' => Url::fromUri('internal:/admin/cloudwords/create-project'),
        '#attributes' => [
          'class' => ['cloudwords-button']
        ],
      ];
      $text = \Drupal::translation()->formatPlural($count, '1 asset in project', '@count assets in project.', ['@count' => $count]);
      $output['fieldset']['text'] = [
        '#type' => 'markup',
        '#markup' => $text,
        '#prefix' => '<div class="cloudwords-item-count">',
        '#suffix' => '</div>',
      ];
      $output['#attached'] = ['library' =>  ['cloudwords/cloudwords']];

      return $output;
    }
    return [];
  }
}