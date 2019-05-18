<?php

namespace Drupal\migrate_d2d_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\NodeType;

/**
 * Simple wizard step form.
 */
class ContentSelectForm extends DrupalMigrateForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_d2d_content_select_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Start clean in case we came here via Previous.
    $cached_values = $form_state->getTemporaryValue('wizard');
    $form_state->setTemporaryValue('wizard', $cached_values);

    $type_count = $this->connection($form_state)->select('node_type', 't')
      ->fields('t', ['type', 'name'])
      ->countQuery()
      ->execute()
      ->fetchField();
    if (!$type_count) {
      $form['description'] = [
        '#markup' => $this->t('There is no node data to be migrated from the source database.'),
      ];
      return $form;
    }
    $form['#tree'] = TRUE;
    $form['description'] = [
      '#markup' => $this->t('For each content type on the source site, choose the destination site content type to import its content. You may also choose not to import a given content type, or to create a content type based on the source configuration.'),
    ];

    $base_options = [
      '-1' => t('--Do not import--'),
      '0' => t('--Create content type--'),
    ];
    $node_options = [];
    $local_types = NodeType::loadMultiple();
    foreach ($local_types as $type => $info) {
      $node_options[$type] = $info->get('name');
    }
    asort($node_options);

    $result = $this->connection($form_state)->select('node_type', 't')
      ->fields('t', ['type', 'name'])
      ->orderBy('name')
      ->execute();
    foreach ($result as $row) {
      $options = $base_options + $node_options;
      // If we have a match on type name, default the mapping to that match
      // and remove the option to create a new type of that name.
      if (isset($node_options[$row->type])) {
        $default_value = $row->type;
        unset($options['0']);
      }
      else {
        $default_value = '-1';
      }
      $node_counts[$row->type] = $this->connection($form_state)->select('node', 'n')
        ->condition('type', $row->type)
        ->countQuery()
        ->execute()
        ->fetchField();
      // But, always default to do-not-import if there are no nodes.
      if ($node_counts[$row->type] == 0) {
        $default_value = '-1';
      }
      if ($node_counts[$row->type] > 0) {
        $title = $this->t('@name (@count)', ['@name' => $row->name,
          '@count' => $this->getStringTranslation()->formatPlural($node_counts[$row->type], '1 node', '@count nodes')]);
        $form['content_types'][$row->type] = array(
          '#type' => 'select',
          '#title' => $title,
          '#options' => $options,
          '#default_value' => $default_value,
        );

      }
    }

    // Build select list from destination formats.
    $base_options = [
      '-1' => t('--Do not import--'),
      '0' => t('--Create format--'),
    ];

    // Destination formats
    $format_options = [];
    foreach (filter_formats() as $format_id => $format) {
      $format_options[$format_id] = $format->get('name');
    }
    if ($cached_values['version'] == '7') {
      $table = 'filter_format';
    }
    else {
      $table = 'filter_formats';
    }
    $result = $this->connection($form_state)->select($table, 'f')
      ->fields('f', ['format', 'name'])
      ->execute();
    $form['format_overview'] = [
      '#markup' => $this->t('For each text format on the legacy site, choose whether to ignore that format, or to assign a different format to content with that legacy format.'),
    ];
    foreach ($result as $row) {
      $options = $base_options + $format_options;
      // If we have a match on format name, default the mapping to that match.
      if ($match = $this->caseArraySearch($row->name, $format_options)) {
        $default_value = $match;
        unset($options['0']);
      }
      else {
        $default_value = '-1';
      }
      $form['formats'][$row->format] = [
        '#type' => 'select',
        '#title' => $row->name,
        '#options' => $options,
        '#default_value' => $default_value,
      ];
    }

    return $form;
  }

  /**
   * Case-insensitively search for a value in an array.
   *
   * @param $value
   * @param $array
   */
  protected function caseArraySearch($needle, $haystack) {
    foreach ($haystack as $key => $value) {
      if (!strcasecmp($needle, $value)) {
        return $key;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $cached_values['content_types'] = $form_state->getValue('content_types');
    $cached_values['formats'] = $form_state->getValue('formats');
    $form_state->setTemporaryValue('wizard', $cached_values);
  }

}
