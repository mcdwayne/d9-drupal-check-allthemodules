<?php

namespace Drupal\cloudwords\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler which shows the source language for a configured project.
 *
 * @ViewsField("cloudwords_project_target_languages_field")
 */
class TargetLanguages extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // @todo filter on target languages field
  }

  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $output = [];
    $query = \Drupal::database()->select('cloudwords_project_language', 'cpl')
      ->fields('cpl', ['language'])
      ->condition('cpl.pid', $this->getEntity($values)->getId());

    $results = $query->execute()->fetchCol();

    foreach($results as $result){
      $output[] = \Drupal::languageManager()->getLanguageName($result);
    }

    return implode(', ', $output);
  }

}
