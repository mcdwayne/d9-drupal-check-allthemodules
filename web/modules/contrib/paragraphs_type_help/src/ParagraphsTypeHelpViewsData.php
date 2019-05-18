<?php

namespace Drupal\paragraphs_type_help;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the paragraphs_type_help entity type.
 */
class ParagraphsTypeHelpViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $field_tables = [
      'paragraphs_type_help_field_data',
      'paragraphs_type_help_field_revision',
    ];
    foreach ($field_tables as $field_table) {
      // Published status.
      if (isset($data[$field_table]['status'])) {
        $data[$field_table]['status']['filter']['label'] = $this->t('Published status');
        $data[$field_table]['status']['filter']['type'] = 'yes-no';
        // Use status = 1 instead of status <> 0 in WHERE statement.
        $data[$field_table]['status']['filter']['use_equal'] = TRUE;
      }

      // Paragraph Type reference.
      if (isset($data[$field_table]['host_bundle'])) {
        $data[$field_table]['host_bundle']['filter'] = [
          'id' => 'in_operator',
          'allow empty' => TRUE,
          'options callback' => 'paragraphs_type_help_paragraph_type_options',
        ];
      }
    }

    return $data;
  }

}
