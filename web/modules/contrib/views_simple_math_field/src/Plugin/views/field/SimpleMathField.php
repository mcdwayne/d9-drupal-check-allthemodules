<?php

namespace Drupal\views_simple_math_field\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @file
 * Defines Drupal\views_simple_math_field\Plugin\views\field\SimpleMathField.
 */

/**
 * Field handler to complete mathematical operation.
 *
 * @ingroup views_field_handlers
 * @ViewsField("field_views_simple_math_field")
 */
class SimpleMathField extends NumericField implements ContainerFactoryPluginInterface {

  protected $entityTypeManager;

  /**
   * SimpleMathField constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Sets the initial field data at zero.
   */
  public function query() {
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Give this field and alias
    $this->field_alias = 'field_views_simple_math_field';

    // Fix undefined warning in Drupal 8.5.3
    $options['fieldset_one']['default'] = NULL;
    $options['fieldset_two']['default'] = NULL;

    $options['fieldset_one']['data_field_one'] = ['default' => NULL];
    $options['fieldset_two']['data_field_two'] = ['default' => NULL];
    $options['operation'] = ['default' => NULL];
    $options['fieldset_one']['constant_one'] = ['default' => NULL];
    $options['fieldset_two']['constant_two'] = ['default' => NULL];
    $options['percentage'] = ['default' => NULL];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $fieldDelta = preg_replace('[\D]', '', $this->options['id']);
    $fieldList = $this->displayHandler->getFieldLabels();
    foreach ($fieldList as $key => $value) {
      if ($this->field_alias === $key && $fieldDelta < preg_replace('[\D]', '', $key)) {
        unset($fieldList[$key]);
      }
    }
    unset($fieldList[$this->options['id']]);
    $fieldList['const'] = t('Enter a constant');
    $form['fieldset_one'] = [
      '#type' => 'fieldset',
      '#title' => t('Select the field representing the first value.'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#weight' => -10,
      '#required' => TRUE,
    ];
    $form['fieldset_one']['data_field_one'] = [
      '#type' => 'radios',
      '#title' => t('Data Field One'),
      '#options' => $fieldList,
      '#default_value' => $this->options['fieldset_one']['data_field_one'],
      '#weight' => -10,
    ];
    $form['fieldset_one']['constant_one'] = [
      '#type' => 'textfield',
      '#title' => t('Constant Value'),
      '#default_value' => $this->options['fieldset_one']['constant_one'],
      '#states' => [
        'visible' => [
          ':input[name="options[fieldset_one][data_field_one]"]' => ['value' => 'const'],
        ],
      ],
      '#weight' => -9,
    ];
    $form['fieldset_two'] = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => t('Select the field representing the second value.'),
      '#weight' => -8,
      '#required' => TRUE,
    ];
    $form['fieldset_two']['data_field_two'] = [
      '#type' => 'radios',
      '#title' => t('Data Field Two'),
      '#options' => $fieldList,
      '#default_value' => $this->options['fieldset_two']['data_field_two'],
      '#weight' => -8,
    ];
    $form['fieldset_two']['constant_two'] = [
      '#type' => 'textfield',
      '#title' => t('Constant Value'),
      '#default_value' => $this->options['fieldset_two']['constant_two'],
      '#states' => [
        'visible' => [
          ':input[name="options[fieldset_two][data_field_two]"]' => ['value' => 'const'],
        ],
      ],
      '#weight' => -7,
    ];
    $form['operation'] = [
      '#type' => 'radios',
      '#title' => t('Operation'),
      '#options' => [
        '+' => t('Add'),
        '-' => t('Subtract'),
        '*' => t('Multiply'),
        '/' => t('Divide'),
        '%' => t('Modulo'),
        '**' => t('Power'),
      ],
      '#default_value' => $this->options['operation'],
      '#description' => t('Choose your operation.'),
      '#weight' => -6,
      '#required' => TRUE,
    ];
    $form['percentage'] = [
      '#type' => 'checkbox',
      '#title' => t('Convert to percent'),
      '#default_value' => $this->options['percentage'],
      '#description' => t('Multiplies the result by 100'),
      '#weight' => -5,
      '#states' => [
        'visible' => [
          ':input[name="options[operation]"]' => ['value' => '/'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Get the value of a simple math field.
   *
   * @param ResultRow $values
   *    Row results.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The current row entity.
   * @param bool $rendering
   *   Whether we're trying to get data from a render view.
   * @param bool $fieldOne
   *   Whether we are fetching field one's value.
   *
   * @return mixed
   *   The field value.
   *
   * @throws \Exception
   */
  protected function getFieldValue(ResultRow $values, $entity, $rendering, $fieldOne) {
    if (!empty($fieldOne)) {
      $field = $this->options['fieldset_one']['data_field_one'];
    }
    else {
      $field = $this->options['fieldset_two']['data_field_two'];
    }

    $data = NULL;

    if ($field == 'const') {
      if (!empty($fieldOne)) {
        $data = $this->options['fieldset_one']['constant_one'];
      }
      else {
        $data = $this->options['fieldset_two']['constant_two'];
      }
    }
    else {
      // Get the value of a field that comes from a relationship.
      if ($this->displayHandler->getHandler('field', $field)->options['relationship'] != 'none') {
        // Get the relationship id.
        $relationship = $this->displayHandler->getHandler('field', $field)->options['relationship'];

        // Get the entity type of the relationship.
        $relationshipEntityType = $this->displayHandler->getHandler('field', $field)
          ->getEntityType();

        $relationship_entities = $values->_relationship_entities;

        // First check the referenced entity.
        if (isset($relationship_entities[$relationship])) {
          // Get the id of the relationship entity.
          $entityId = $relationship_entities[$relationship]->id();

          // Get the data of the relationship entity.
          $relationshipEntity = $this->entityTypeManager
            ->getStorage($relationshipEntityType)
            ->load($entityId);

          // Use the relationship entity to fetch the field value.
          $entity = $relationshipEntity;
        }
      }

      if (!empty($rendering)) {
        if (isset($entity) && $entity->hasField($field)) {
          $data = $entity->get($field)->getValue()[0]['value'];
        }
      }
      else {
        // Allow for field rewrites.
        if ($this->displayHandler->getHandler('field', $field)->options['alter']['alter_text'] == 1) {
          $text = $this->displayHandler->getHandler('field', $field)->options['alter']['text'];
          if (is_numeric($text) == TRUE) {
            $data = $text;
          }
          else {
            // @todo: should alert user.
            if (isset($entity) && $entity->hasField($field)) {
              $data = $entity->get($field)->value;
            }
          }
        }
        else {
          if (isset($entity) && $entity->hasField($field)) {
            $data = $entity->get($field)->value;
          }
        }
      }

      // Fallback to fetching the data from the database alias.
      if (isset($this->view->field[$field])) {
        if ($this->field_alias == 'field_views_simple_math_field') {
          if (!empty($this->options['separator'])) {
            // Was using:
            // $data = $this->view->field[$field]->advancedRender($values);
            // but it was causing issues for some manual tests.
            $data = $this->view->field[$field]->getValue($values);
            $separator = $this->options['separator'];
            if (strpos($data, $separator)) {
              $data = str_replace($separator, '', $data);
            }
          }
          else {
            // Was using:
            // $data = $this->view->field[$field]->advancedRender($values);
            // but it was causing issues for some manual tests.
            $data = $this->view->field[$field]->getValue($values);
            if (strpos($data, ',')) {
              $data = str_replace(',', '', $data);
            }
            if (strpos($data, ' ')) {
              $data = str_replace(' ', '', $data);
            }
          }
        }
        else {
          $data = $values->{$field};
        }
      }

    }

    if (!isset($data)) {
      // There's no value. Default to 0.
      $data = 0;
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    parent::getValue($values, $field);

    $operation = $this->options['operation'];
    $entity = $this->getEntity($values);

    $dataFieldOneValue = $this->getFieldValue($values, $entity, FALSE, TRUE);
    $dataFieldTwoValue = $this->getFieldValue($values, $entity, FALSE, FALSE);

    if ($operation === '+') {
      $value = $dataFieldOneValue + $dataFieldTwoValue;
    }
    elseif ($operation === '-') {
      $value = $dataFieldOneValue - $dataFieldTwoValue;
    }
    elseif ($operation === '*') {
      $value = $dataFieldOneValue * $dataFieldTwoValue;
    }
    elseif ($operation === '/') {
      $value = $dataFieldOneValue / $dataFieldTwoValue;
      if ($this->options['percentage'] === 1) {
        $value = $value * 100;
      }
    }
    elseif ($operation === '%') {
      $value = $dataFieldOneValue % $dataFieldTwoValue;
    }
    elseif ($operation === '**') {
      $value = pow($dataFieldOneValue, $dataFieldTwoValue);
    }
    else {
      $value = NULL;
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

}
