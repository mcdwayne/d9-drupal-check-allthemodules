<?php

namespace Drupal\most_viewed;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Configure statistics settings for this site.
 *
 * @internal
 */
class MVSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mv_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['most_viewed.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('most_viewed.settings');

    // Prepare a list of node bundles.
    $node_types = [];
    $bundles = NodeType::loadMultiple();
    foreach ($bundles as $key => $bundle_info) {
      $node_types[$key] = $bundle_info->label();
    }
    // @todo Don't use t(), should user $this->t()
    $form['most_viewed_node_types'] = [
      '#type' => 'checkboxes',
      '#title' => t('Node types'),
      '#description' => t('The node bundles that should be watched.'),
      '#options' => $node_types,
      '#default_value' =>  $config->get('most_viewed_node_types'),
    ];

    // @todo array() to []
    $term_types = array();
    $bundles = Vocabulary::loadMultiple();
    foreach ($bundles as $key => $bundle_info) {
      // @todo добавить документирующий комментарий чтобы щторм понимал метод label
      $term_types[$key] = $bundle_info->label();
    }
    $form['most_viewed_taxonomy_term_types'] = [
      '#type' => 'checkboxes',
      '#title' => t('Term types'),
      '#description' => t('The taxonomy term bundles that should be watched.'),
      '#options' => $term_types,
      '#default_value' => $config->get('most_viewed_taxonomy_term_types'),
    ];

    $form['most_viewed_user_types'] = [
      '#type' => 'checkboxes',
      '#title' => t('User types'),
      '#description' => t('The user bundles that should be watched.'),
      '#options' => ['user' => t('User')],
      '#default_value' => $config->get('most_viewed_user_types'),
    ];

    $def_value = $config->get('most_viewed_hits_max_life_time');
    if (empty($def_value)) {
      $def_value = 30;
    }
    $form['most_viewed_hits_max_life_time'] = [
      '#type' => 'textfield',
      '#title' => t('Hits stored time (days)'),
      '#description' => t('How long we store hits in database (in days), 30 days by default'),
      '#default_value' => $def_value,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('most_viewed.settings')
      ->set('most_viewed_node_types', $form_state->getValue('most_viewed_node_types'))
      ->set('most_viewed_taxonomy_term_types', $form_state->getValue('most_viewed_taxonomy_term_types'))
      ->set('most_viewed_user_types', $form_state->getValue('most_viewed_user_types'))
      ->set('most_viewed_hits_max_life_time', $form_state->getValue('most_viewed_hits_max_life_time'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
