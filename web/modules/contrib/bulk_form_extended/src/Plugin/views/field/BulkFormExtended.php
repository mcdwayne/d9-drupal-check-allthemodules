<?php

namespace Drupal\bulk_form_extended\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\BulkForm;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Extends the 'bulk_form_extended' plugin class.
 *
 * @ViewsField("bulk_form_extended")
 */
class BulkFormExtended extends BulkForm {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a new BulkForm object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, MessengerInterface $messenger, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $language_manager, $messenger);

    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('messenger'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['select_all']                        = ['default' => 0];
    $options['checkbox_label']                    = ['default' => 0];
    $options['checkbox_label_value']              = ['default' => $this->t('Update this item')];
    $options['replace_single_action_with_button'] = ['default' => 1];
    $options['custom_empty_select_message']       = ['default' => 0];
    $options['custom_empty_select_message_value'] = ['default' => $this->t('No items selected.')];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // @todo find a better way to find out what entitys are in this view
    $base_tables = $this->view->getBaseTables();

    $token_types = [];

    foreach ($base_tables as $base_table => $value) {
      $token_types[] = str_replace('_field_data', '', $base_table);
    }

    $form['bulk_form_extended'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Extended options'),
      '#weight' => 10,
    ];

    $form['select_all'] = [
      '#fieldset' => 'bulk_form_extended',
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show "Select All" button?'),
      '#description'   => $this->t('Show a button which allows you to select all the items on the list. If all items are already selected, the button will become a "Deselect All" button.'),
      '#default_value' => $this->options['select_all'],
    ];

    $form['custom_empty_select_message'] = [
      '#fieldset' => 'bulk_form_extended',
      '#type'          => 'checkbox',
      '#title'         => $this->t('Display a custom empty select message?'),
      '#default_value' => $this->options['custom_empty_select_message'],
    ];

    $form['custom_empty_select_message_value'] = [
      '#fieldset' => 'bulk_form_extended',
      '#type'          => 'textfield',
      '#title'         => $this->t('Custom empty select message'),
      '#description'   => $this->t('The message to return on an empty select.'),
      '#default_value' => $this->options['custom_empty_select_message_value'],
      '#states' => [
        'invisible' => [
          ':input[name="options[custom_empty_select_message]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['checkbox_label'] = [
      '#fieldset' => 'bulk_form_extended',
      '#type'          => 'checkbox',
      '#title'         => $this->t('Create a label for individual checkboxes.'),
      '#default_value' => $this->options['checkbox_label'],
    ];

    $form['checkbox_label_value'] = [
      '#fieldset' => 'bulk_form_extended',
      '#type'          => 'textfield',
      '#title'         => $this->t('Checkbox Label'),
      '#description'   => $this->t('The label for the checkboxes. This field supports tokens.'),
      '#default_value' => $this->options['checkbox_label_value'],
      '#states' => [
        'invisible' => [
          ':input[name="options[checkbox_label]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['token_help'] = [
      '#fieldset' => 'bulk_form_extended',
      '#theme' => 'token_tree_link',
      '#token_types' => $token_types,
    ];

    $form['replace_single_action_with_button'] = [
      '#fieldset' => 'bulk_form_extended',
      '#type'          => 'checkbox',
      '#title'         => $this->t('Replace the dropdown field with a single button if only one action is available.'),
      '#default_value' => $this->options['replace_single_action_with_button'],
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(&$form, FormStateInterface $form_state) {
    // Make sure we do not accidentally cache this form.
    // @todo Evaluate this again in https://www.drupal.org/node/2503009.
    $form['#cache']['max-age'] = 0;

    // Add the tableselect javascript.
    $form['#attached']['library'][] = 'core/drupal.tableselect';
    $use_revision = array_key_exists('revision', $this->view->getQuery()->getEntityTableInfo());

    // Only add the bulk form options and buttons if there are results.
    if (!empty($this->view->result)) {
      // Render checkboxes for all rows.
      $form[$this->options['id']]['#tree'] = TRUE;

      foreach ($this->view->result as $row_index => $row) {
        $entity = $this->getEntityTranslation($this->getEntity($row), $row);

        $checkbox_label = $this->options['checkbox_label_value'];
        $checkbox_diplay = 'hidden';

        if ($this->options['checkbox_label']) {

          $token_data = [];
          $token_data[$row->_entity->getEntityTypeId()] = $row->_entity;

          foreach ($row->_relationship_entities as $field => $related_entity) {
            $token_data[$related_entity->getEntityTypeId()] = $related_entity;
          }

          if ($this->options['checkbox_label_value']) {
            $checkbox_label = $this->token->replace($this->options['checkbox_label_value'], $token_data);
            $checkbox_diplay = 'after';
          }
        }

        $form[$this->options['id']][$row_index] = [
          '#type'          => 'checkbox',
          '#title'         => $checkbox_label,
          '#title_display' => $checkbox_diplay,
          '#default_value' => !empty($form_state->getValue($this->options['id'])[$row_index]) ? 1 : NULL,
          '#return_value'  => $this->calculateEntityBulkFormKey($entity, $use_revision),
        ];
      }

      // Ensure a consistent container for filters/operations in the
      // view header.
      $form['header'] = [
        '#type' => 'container',
        '#weight' => -100,
      ];

      // Build the bulk operations action widget for the header.
      // Allow themes to apply .container-inline on this separate container.
      $form['header'][$this->options['id']] = [
        '#type' => 'container',
      ];

      $form['header'][$this->options['id']]['action'] = [
        '#type' => 'select',
        '#title' => $this->options['action_title'],
        '#options' => $this->getBulkOptions(),
      ];

      // Replaces the dropdown header with a single button.
      if ($this->options['replace_single_action_with_button']) {
        $action_array = $this->getBulkOptions();
        if (count($action_array) == 1) {
          $form['actions']['submit']['#value'] = reset($action_array);

          $form['header'][$this->options['id']]['action'] = [
            '#type'  => 'hidden',
            '#value' => key($action_array),
          ];
        }
      }

      // Add the select all button.
      if ($this->options['select_all']) {
        $form['#attached']['library'][] = 'bulk_form_extended/select_all';

        $form['actions'][$this->options['id']]['select_all'] = [
          '#type'       => 'button',
          '#value'      => $this->t('Select All'),
          '#attributes' => [
            'class' => ['js-bulk-form-extended-select-all'],
            'data-deselect' => [$this->t('Deselect All')],
            'data-select' => [$this->t('Select All')],
          ],
        ];
      }

      // Duplicate the form actions into the action container in the header.
      $form['header'][$this->options['id']]['actions'] = $form['actions'];
    }
    else {
      // Remove the default actions build array.
      unset($form['actions']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    if ($this->options['custom_empty_select_message']) {
      return $this->options['custom_empty_select_message_value'];
    }

    return parent::emptySelectedMessage();
  }

}
