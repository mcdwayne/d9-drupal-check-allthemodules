<?php

namespace Drupal\cloudwords\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class CloudwordsNodesSetLanguageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudwords_nodes_set_language_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $nids_list = NULL) {
    $nids = array_map('_cloudwords_nodes_set_language_filter_nid', explode(',', $nids_list));

    $node_titles = [];
    $nodes = [];
    foreach ($nids as $key => $nid) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      if ($node) {
        $nodes[] = $node;
        $node_titles[] = \Drupal\Component\Utility\Html::escape($node->title);
      }
      else {
        unset($nids[$key]);
      }
    }

    $lang_opts = [\Drupal\Core\Language\Language::LANGCODE_NOT_SPECIFIED => 'Language neutral'];

    foreach(\Drupal::languageManager()->getlanguages() as $k => $v){
      $lang_opts[$k] = $v->getName();
    }

    $form['language'] = [
      '#title' => $this->t('Language'),
      '#description' => $this->t('Select the language for<br /><i>' . implode("<br />", $node_titles) . '</i>'),
      '#type' => 'select',
      '#options' => $lang_opts,
    ];

    $question = $this->t('Select a language.');
    $path = 'admin/content';

    $form_state->set(['nodes_to_update'], $nodes);
    return confirm_form($form, $question, $path);
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $nodes = $form_state->get(['nodes_to_update']);
    $language = $form_state->getValue(['language']);

    foreach ($nodes as $node) {
      _cloudwords_nodes_set_language_single_node_operation($node, $language);
    }

    $form_state->set(['redirect'], 'admin/content');
  }

}
