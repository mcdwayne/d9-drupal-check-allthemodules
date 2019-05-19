<?php

namespace Drupal\tikitoki\Plugin\views\row;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\field\FieldHandlerInterface;
use Drupal\views\Plugin\views\row\RowPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin which displays fields as raw data.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "tikitoki_fields",
 *   title = @Translation("Fields"),
 *   help = @Translation("Use fields as row data."),
 *   display_types = {"tikitoki"}
 * )
 */
class TikiTokiFields extends RowPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;
  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    static $defaults = ['default' => ''];
    $options = parent::defineOptions();

    $options['start_date_field'] = $defaults;
    $options['end_date_field']   = $defaults;
    $options['title_field']      = $defaults;
    $options['text_field']       = $defaults;
    $options['full_text_field']  = $defaults;
    $options['category_field']   = $defaults;
    $options['link_field']       = $defaults;
    $options['media']            = $defaults;
    $options['color_field']      = $defaults;

    return $options;
  }

  /**
   * Build list of date fields.
   *
   * @param array $view_fields_labels
   *   All fields list.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $definitions
   *   Field storage definitions.
   *
   * @return array
   *   List of date fields only.
   */
  protected function buildDateFieldsList(array $view_fields_labels, $definitions) {
    $fields = [];
    // @TODO: figure out what types we need to use here.
    static $allowed_types = [
      'changed',
      'created',
      'datetime',
      'daterange',
    ];
    foreach ($view_fields_labels as $field_name => $field) {
      if (isset($field_name) && !empty($field_name) && isset($definitions[$field_name])) {
        $definition = $definitions[$field_name];
        if (in_array($definition->getType(), $allowed_types)) {
          $fields[$field_name] = $field;
        }
      }
    }
    return $fields;
  }

  /**
   * Build list of entity reference fields(taxonomy).
   *
   * @param array $view_fields_labels
   *   All fields list.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $definitions
   *   Field storage definitions.
   *
   * @return array
   *   List of entity reference fields(taxonomy) fields only.
   */
  protected function buildTaxonomyFieldsList(array $view_fields_labels, $definitions) {
    $fields = [];
    foreach ($view_fields_labels as $field_name => $field) {
      if (isset($field_name) && !empty($field_name) && isset($definitions[$field_name])) {
        $definition = $definitions[$field_name];
        if ($definition->getType() === 'entity_reference'
          && $definition->getSetting('target_type') === 'taxonomy_term'
        ) {
          $fields[$field_name] = $field;
        }
      }
    }
    return $fields;
  }

  /**
   * Build list of media fields.
   *
   * @param array $view_fields_labels
   *   All fields list.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $definitions
   *   Field storage definitions.
   *
   * @return array
   *   List of media fields only.
   */
  protected function buildMediaFieldsList(array $view_fields_labels, $definitions) {
    $fields = [];
    // @TODO: Add more media field types.
    static $allowed_types = ['image'];
    foreach ($view_fields_labels as $field_name => $field) {
      if (isset($field_name) && !empty($field_name) && isset($definitions[$field_name])) {
        $definition = $definitions[$field_name];
        if (in_array($definition->getType(), $allowed_types)) {
          $fields[$field_name] = $field;
        }
      }
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Prepare some data for fields building.
    $entity_type_id = $this->view
      ->getBaseEntityType()->id();
    $definitions = $this->entityFieldManager
      ->getFieldStorageDefinitions($entity_type_id);

    $initial_labels     = ['' => $this->t('- None -')];
    $view_fields_labels = $this->displayHandler->getFieldLabels();
    $view_fields_labels = array_merge($initial_labels, $view_fields_labels);
    $date_fields        = $initial_labels + $this->buildDateFieldsList($view_fields_labels, $definitions);
    $taxonomy_fields    = $initial_labels + $this->buildTaxonomyFieldsList($view_fields_labels, $definitions);
    $media_fields       = $initial_labels + $this->buildMediaFieldsList($view_fields_labels, $definitions);

    $form['title_field'] = [
      '#type'           => 'select',
      '#title'          => $this->t('Title field'),
      '#description'    => $this->t('The field that is going to be used as title item for each row.'),
      '#options'        => $view_fields_labels,
      '#default_value'  => $this->options['title_field'],
      '#required'       => TRUE,
    ];
    $form['link_field'] = [
      '#type'           => 'select',
      '#title'          => $this->t('Link field'),
      '#description'    => $this->t('The field that is going to be used as the externalLink item for each row. This must be a drupal absolute path.'),
      '#options'        => $view_fields_labels,
      '#default_value'  => $this->options['link_field'],
      '#required'       => FALSE,
    ];
    $form['text_field'] = [
      '#type'           => 'select',
      '#title'          => $this->t('Text field'),
      '#description'    => $this->t('The field that is going to be used as text item for each row.'),
      '#options'        => $view_fields_labels,
      '#default_value'  => $this->options['text_field'],
      '#required'       => TRUE,
    ];
    $form['full_text_field'] = [
      '#type'           => 'select',
      '#title'          => $this->t('Full text field'),
      '#description'    => $this->t('The field that is going to be used as fullText item for each row.'),
      '#options'        => $view_fields_labels,
      '#default_value'  => $this->options['full_text_field'],
      '#required'       => FALSE,
    ];
    $form['category_field'] = [
      '#type'           => 'select',
      '#title'          => $this->t('Category field'),
      '#description'    => $this->t('The field that is going to be used as the category item for each row.'),
      '#options'        => $taxonomy_fields,
      '#default_value'  => $this->options['category_field'],
      '#required'       => FALSE,
    ];
    $form['start_date_field'] = [
      '#type'           => 'select',
      '#title'          => $this->t('Start date field'),
      '#description'    => $this->t('The field that is going to be used as the startDate item for each row. It needs to be Y-m-d h:m:i format.'),
      '#options'        => $date_fields,
      '#default_value'  => $this->options['start_date_field'],
      '#required'       => TRUE,
    ];
    $form['end_date_field'] = [
      '#type'           => 'select',
      '#title'          => $this->t('End date field'),
      '#description'    => $this->t('The field that is going to be used as the endDate item for each row. It needs to be Y-m-d h:m:i format.'),
      '#options'        => $date_fields,
      '#default_value'  => $this->options['end_date_field'],
      '#required'       => TRUE,
    ];
    $form['media'] = [
      '#type'           => 'select',
      '#title'          => $this->t('Media'),
      '#description'    => $this->t('The field that is going to be used as the media items for each row.'),
      '#options'        => $media_fields,
      '#default_value'  => $this->options['media'],
      '#required'       => FALSE,
    ];
    $form['color_field'] = [
      '#type'           => 'select',
      '#title'          => $this->t('Color'),
      '#description'    => $this->t('The field that is going to be used as the color for categories.'),
      '#options'        => $view_fields_labels,
      '#default_value'  => $this->options['color_field'],
      '#required'       => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    $required_options = ['title_field', 'text_field', 'start_date_field', 'end_date_field'];
    $required_message = $this->t('Row style plugin requires specifying which views fields to use for Tiki Toki timeline item.');
    foreach ($required_options as $required_option) {
      if (empty($this->options[$required_option])) {
        $errors[] = $required_message;
        break;
      }
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    static $namespace = '\Drupal\tikitoki\FieldProcessor';
    $fields_map = [
      'ownerId'          => '',
      'ownerName'        => '',
      'title_field'      => "$namespace\\TitleFieldProcessor",
      'start_date_field' => "$namespace\\StartDateFieldProcessor",
      'end_date_field'   => "$namespace\\EndDateFieldProcessor",
      'text_field'       => "$namespace\\TextFieldProcessor",
      'full_text_field'  => "$namespace\\FullTextFieldProcessor",
      'category_field'   => "$namespace\\CategoryFieldProcessor",
      'link_field'       => "$namespace\\LinkFieldProcessor",
      'media'            => "$namespace\\MediaFieldProcessor",
    ];

    $item = [
      'id'        => $row->_entity->id(),
      'ownerId'   => '',
      'ownerName' => '',
    ];
    foreach ($fields_map as $source_key => $class) {
      $field = NULL;
      if (isset($this->options[$source_key])) {
        $field_id = $this->options[$source_key];
        if (isset($this->view->field[$field_id])) {
          $field = $this->view->field[$field_id];
        }
      }

      if (!empty($class)) {
        // Apply the default value for field.
        $destination_id = $class::getDestinationId();
        if ($destination_id && $field instanceof FieldHandlerInterface) {
          $item[$destination_id] = $source_key === 'media' ? [] : '';
          /** @var \Drupal\tikitoki\FieldProcessor\FieldProcessorInterface $processor */
          $processor = new $class($field, $row);
          $item[$destination_id] = $processor->getValue();
        }
      }
    }

    return $item;
  }

}
