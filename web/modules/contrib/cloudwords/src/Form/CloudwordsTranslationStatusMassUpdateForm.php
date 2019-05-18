<?php

namespace Drupal\cloudwords\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class CloudwordsTranslationStatusMassUpdateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudwords_translation_status_mass_update_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $nids_list = NULL) {
    $nids = array_map('_cloudwords_nodes_set_status_nid', explode(',', $nids_list));

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

    $statuses = cloudwords_exists_options_list();
    $status_opts = [NULL => $this->t('Select one')];

    foreach ($statuses as $key => $value) {
      $status_opts[$key] = $value;
    }

    $form['status'] = [
      '#title' => $this->t('status'),
      '#description' => $this->t('Select the status for<br /><i>' . implode("<br />", $node_titles) . '</i>'),
      '#type' => 'select',
      '#options' => $status_opts,
    ];

    $question = $this->t('Select a status.');
    $path = 'admin/content';

    $form_state->set(['nodes_to_update'], $nodes);
    return confirm_form($form, $question, $path);

  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $nodes = $form_state->get(['nodes_to_update']);
    $status = $form_state->getValue(['status']);

    foreach ($nodes as $node) {
      _cloudwords_translation_status_mass_update_single_node_operation($node, $status);
    }

    $form_state->set(['redirect'], 'admin/content');
  }

}
