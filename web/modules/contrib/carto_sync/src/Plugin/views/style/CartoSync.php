<?php

namespace Drupal\carto_sync\Plugin\views\style;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\ResultRow;
use League\Csv\Writer;

/**
 * Defines a style plugin for CARTO Sync.
 *
 * @ViewsStyle(
 *   id = "carto_sync",
 *   theme = "views_view_unformatted",
 *   title = @Translation("CartoSync"),
 *   help = @Translation("CartoSync."),
 *   display_types = {"carto_sync"}
 * )
 */
class CartoSync extends StylePluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase::$usesRowPlugin.
   */
  protected $usesRowPlugin = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase::$usesFields.
   */
  protected $usesFields = TRUE;

  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase::$usesRowClass.
   */
  protected $usesRowClass = FALSE;

  /**
   * Overrides Drupal\views\Plugin\views\style\StylePluginBase::$usesGrouping.
   */
  protected $usesGrouping = FALSE;

  /**
   * Indicates the character used to delimit fields. Defaults to ",".
   *
   * @var string
   */
  protected $delimiter = ',';

  /**
   * Indicates the character used for field enclosure. Defaults to '"'.
   *
   * @var string
   */
  protected $enclosure = '"';

  /**
   * Indicates the character used for escaping. Defaults to "\".
   *
   * @var string
   */
  protected $escapeChar = '\\';

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Note: views UI registers this theme handler on our behalf. Your module
    // will have to register your theme handlers if you do stuff like this.
    $form['#theme'] = 'carto_sync_style_plugin_table';

    // Create an array of allowed columns from the data we know:
    $field_names = $this->displayHandler->getFieldLabels();

    $columns = $this->sanitizeColumns($this->options['columns']);
    $geo_columns = $this->getColumnsOfType('geofield');
    $int_columns = $this->getColumnsOfType('integer');

    foreach ($columns as $field => $column) {

      // Markup for the field name.
      $form['info'][$field]['name'] = [
        '#markup' => $field_names[$field],
      ];

      if (isset($this->options['primary_key'])) {
        $default = $this->options['primary_key'];
        if (!isset($columns[$default])) {
          $default = -1;
        }
      }
      else {
        $default = -1;
      }

      if (isset($this->options['the_geom'])) {
        $the_geom = $this->options['the_geom'];
        if (!isset($columns[$the_geom])) {
          $the_geom = -1;
        }
      }
      else {
        $the_geom = -1;
      }

      $form['info'][$field]['field_name'] = [
        '#title' => $this->t('Separator for @field', ['@field' => $field]),
        '#title_display' => 'invisible',
        '#type' => 'textfield',
        '#size' => 10,
        '#default_value' => isset($this->options['info'][$field]['field_name']) ? $this->options['info'][$field]['field_name'] : $field,
        '#required' => TRUE,
      ];

      if (in_array($field, $int_columns)) {
        // Provide an ID so we can have such things.
        $radio_id = Html::getUniqueId('edit-default-' . $field);
        $form['primary_key'][$field] = [
          '#title' => $this->t('Default sort for @field', ['@field' => $field]),
          '#title_display' => 'invisible',
          '#type' => 'radio',
          '#return_value' => $field,
          '#parents' => ['style_options', 'primary_key'],
          '#id' => $radio_id,
          // Because 'radio' doesn't fully support '#id' =(.
          '#attributes' => ['id' => $radio_id],
          '#default_value' => $default,
        ];
      }

      if (in_array($field, $geo_columns)) {
        // Provide an ID so we can have such things.
        $radio_id = Html::getUniqueId('edit-the-geom-' . $field);
        $form['the_geom'][$field] = [
          '#title' => $this->t('Default sort for @field', ['@field' => $field]),
          '#title_display' => 'invisible',
          '#type' => 'radio',
          '#return_value' => $field,
          '#parents' => ['style_options', 'the_geom'],
          '#id' => $radio_id,
          // Because 'radio' doesn't fully support '#id' =(.
          '#attributes' => ['id' => $radio_id],
          '#default_value' => $the_geom,
        ];
      }
    }

    $form['description_markup'] = [
      '#markup' => '<div class="js-form-item form-item description">' . $this->t('Select the CARTO column names to use. The Primary Key column is forced to be named cartob_id and has to be an integer. The Geometry one is forced to be named the_geom') . '</div>',
    ];

    return $form;

  }

  /**
   * Normalize a list of columns based upon the fields that are
   * available. This compares the fields stored in the style handler
   * to the list of fields actually in the view, removing fields that
   * have been removed and adding new fields in their own column.
   *
   * - Each field must be in a column.
   * - Each column must be based upon a field, and that field
   *   is somewhere in the column.
   * - Any fields not currently represented must be added.
   * - Columns must be re-ordered to match the fields.
   *
   * @param array $columns
   *   An array of all fields; the key is the id of the field and the
   *   value is the id of the column the field should be in.
   * @param array|null $fields
   *   The fields to use for the columns. If not provided, they will
   *   be requested from the current display. The running render should
   *   send the fields through, as they may be different than what the
   *   display has listed due to access control or other changes.
   *
   * @return array
   *   An array of all the sanitized columns.
   */
  public function sanitizeColumns($columns, $fields = NULL) {
    $sanitized = [];
    if ($fields === NULL) {
      $fields = $this->displayHandler->getOption('fields');
    }
    // Preconfigure the sanitized array so that the order is retained.
    foreach ($fields as $field => $info) {
      // Set to itself so that if it isn't touched, it gets column
      // status automatically.
      $sanitized[$field] = $field;
    }

    foreach ($columns as $field => $column) {
      // first, make sure the field still exists.
      if (!isset($sanitized[$field])) {
        continue;
      }

      // If the field is the column, mark it so, or the column
      // it's set to is a column, that's ok.
      if ($field == $column || $columns[$column] == $column && !empty($sanitized[$column])) {
        $sanitized[$field] = $column;
      }
      // Since we set the field to itself initially, ignoring
      // the condition is ok; the field will get its column
      // status back.
    }

    return $sanitized;
  }

  /**
   * Returns the list of columns in the view.
   *
   * @param string $field_type
   *   The field type we are tyring to get data.
   *
   * @return string[]
   *   Field names of the requested field type.
   */
  protected function getColumnsOfType($field_type) {
    $columns = [];
    foreach ($this->displayHandler->getHandlers('field') as $id => $handler) {
      if (isset($handler->definition['field_name'])) {
        $entity_type_id = $handler->definition['entity_type'];
        $def = \Drupal::entityManager()->getFieldStorageDefinitions($entity_type_id);
        if ($def[$handler->definition['field_name']]->getType() == $field_type) {
          $columns[] = $id;
        }
      }
    }

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $dataset = [];

    // Get the excluded fields array, common for all rows.
    $excluded_fields = $this->getExcludedFields();

    // Render each row.
    foreach ($this->view->result as $i => $row) {
      if ($feature = $this->renderRow($row, $excluded_fields)) {
        $dataset[] = $feature;
      }
    }

    // Instantiate CSV writer with options.
    $csv = Writer::createFromFileObject(new \SplTempFileObject());
    $csv->setDelimiter($this->delimiter);
    $csv->setEnclosure($this->enclosure);
    $csv->setEscape($this->escapeChar);

    // Set data.
    $headers = $this->extractHeaders($dataset);
    $csv->insertOne($headers);

    foreach ($dataset as $row) {
      $csv->insertOne($row);
    }
    $output = $csv->__toString();
    $directory = 'temporary://carto_sync';
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    $path = file_unmanaged_save_data($output, $directory . '/' . $this->displayHandler->options['dataset_name'] . '.csv', FILE_EXISTS_REPLACE);

    $real_path = \Drupal::service('file_system')->realpath($path);
    $service = \Drupal::service('carto_sync.api');
    return $service->importDataset($real_path);
  }

  /**
   * Render views fields to GeoJSON.
   *
   * Takes each field from a row object and renders the field as determined by
   * the field's theme.
   *
   * @param \Drupal\views\ResultRow $row
   *   Row object.
   * @param array $excluded_fields
   *   Array containing field keys to be excluded.
   *
   * @return array
   *   Array containing all the raw and rendered fields
   */
  protected function renderRow(ResultRow $row, $excluded_fields) {
    $geophp = \Drupal::service('geofield.geophp');
    $data['cartodb_id'] = $this->view->field[$this->options['primary_key']]->advancedRender($row);
    $geofield = $this->view->style_plugin->getFieldValue($row->index, $this->options['the_geom']);
    if (!empty($geofield)) {
      $geometry = $geophp->load($geofield);
      $data['the_geom'] = $geometry->out('json');
    }
    else {
      return;
    }

    // Fill in attributes that are not:
    // - Coordinate fields,
    // - Name/description (already processed),
    // - Views "excluded" fields.
    foreach (array_keys($this->view->field) as $id) {
      $field = $this->view->field[$id];
      if (!isset($excluded_fields[$id]) && !($field->options['exclude'])) {
        // Allows you to customize the name of the property by setting a label
        // to the field.
        $key = $this->options['info'][$id]['field_name'];
        $value_rendered = $field->advancedRender($row);
        $data[$key] = is_numeric($value_rendered) ? floatval($value_rendered) : $value_rendered;
      }
    }

    return $data;
  }

  /**
   * Retrieves the name field value.
   *
   * @param ResultRow $row
   *   The result row.
   *
   * @return string
   *   The main field value.
   */
  protected function renderNameField(ResultRow $row) {
    return $this->renderMainField($row, 'name_field');
  }

  /**
   * Retrieves the description field value.
   *
   * @param ResultRow $row
   *   The result row.
   *
   * @return string
   *   The main field value.
   */
  protected function renderDescriptionField(ResultRow $row) {
    return $this->renderMainField($row, 'description_field');
  }

  /**
   * Retrieves the main fields values.
   *
   * @param ResultRow $row
   *   The result row.
   * @param string $field_name
   *   The main field name.
   *
   * @return string
   *   The main field value.
   */
  protected function renderMainField(ResultRow $row, $field_name) {
    if ($this->options['data_source'][$field_name]) {
      return $this->view->field[$this->options['data_source'][$field_name]]->advancedRender($row);
    }
    else {
      return '';
    }
  }

  /**
   * Retrieves the list of excluded fields due to style plugin configuration.
   *
   * @return array
   *   List of excluded fields.
   */
  protected function getExcludedFields() {
    $excluded_fields = [
      $this->options['primary_key'],
      $this->options['the_geom'],
    ];

    return array_combine($excluded_fields, $excluded_fields);
  }

  /**
   * Extracts the headers using the first row of values.
   *
   * @param array $data
   *   The array of data to be converted to a CSV.
   *
   * We must make the assumption that each row shares the same set of headers
   * will all other rows. This is inherent in the structure of a CSV.
   *
   * @return array
   *   An array of CSV headesr.
   */
  protected function extractHeaders($data) {
    if (!empty($data)) {
      $first_row = $data[0];
      $headers = array_keys($first_row);

      return $headers;
    }
    else {
      return [];
    }
  }

}
