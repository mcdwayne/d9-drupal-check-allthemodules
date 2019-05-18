<?php

namespace Drupal\access_filter\Form;

use Drupal\access_filter\Plugin\AccessFilterPluginManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\access_filter\Entity\Filter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;

/**
 * Provides edit filter form.
 */
class EditForm extends EntityForm {

  /**
   * The access filter condition plugin manager.
   *
   * @var \Drupal\access_filter\Plugin\AccessFilterPluginManagerInterface
   */
  protected $conditionPluginManager;

  /**
   * The access filter rule plugin manager.
   *
   * @var \Drupal\access_filter\Plugin\AccessFilterPluginManagerInterface
   */
  protected $rulePluginManager;

  /**
   * Constructs a new EditForm object.
   *
   * @param \Drupal\access_filter\Plugin\AccessFilterPluginManagerInterface $condition_plugin_manager
   *   The access filter condition plugin manager.
   * @param \Drupal\access_filter\Plugin\AccessFilterPluginManagerInterface $rule_plugin_manager
   *   The access filter rule plugin manager.
   */
  public function __construct(AccessFilterPluginManagerInterface $condition_plugin_manager, AccessFilterPluginManagerInterface $rule_plugin_manager) {
    $this->conditionPluginManager = $condition_plugin_manager;
    $this->rulePluginManager = $rule_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.access_filter.condition'),
      $container->get('plugin.manager.access_filter.rule')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var Filter $filter */
    $filter = $this->entity;
    $filter->parse();

    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => t('General'),
    ];
    $form['general']['status'] = [
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#default_value' => $filter->status(),
    ];
    $form['general']['name'] = [
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#default_value' => $filter->label(),
      '#max_length' => 255,
    ];
    $form['general']['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $filter->id(),
      '#machine_name' => [
        'source' => ['general', 'name'],
        'exists' => [Filter::class, 'load'],
      ],
      '#disabled' => !$filter->isNew(),
    ];

    $form['conditions'] = [
      '#type' => 'fieldset',
      '#title' => t('Conditions'),
    ];
    $form['conditions']['conditions'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Conditions'),
      '#title_display' => 'invisible',
      '#description' => $this->t('Write in YAML format like below...') . '<br>' . $this->t('All conditions can be negated by specifying "negate: 1".') . $this->renderPluginDescription($this->conditionPluginManager->getDefinitions()),
      '#attributes' => ['data-yaml-editor' => 'true'],
      '#default_value' => $filter->conditions,
    ];

    $form['rules'] = [
      '#type' => 'fieldset',
      '#title' => t('Rules'),
    ];
    $form['rules']['rules'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Rules'),
      '#title_display' => 'invisible',
      '#description' => $this->t('Write in YAML format like below...') . $this->renderPluginDescription($this->rulePluginManager->getDefinitions()),
      '#attributes' => ['data-yaml-editor' => 'true'],
      '#default_value' => $filter->rules,
    ];

    $form['response'] = [
      '#type' => 'fieldset',
      '#title' => t('Response'),
      '#tree' => TRUE,
    ];
    $form['response']['code'] = [
      '#type' => 'select',
      '#title' => t('Response code'),
      '#options' => [
        200 => '200 OK',
        301 => '301 Moved Permanently',
        302 => '302 Moved Temporarily',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        410 => '410 Gone',
        500 => '500 Internal Server Error',
        503 => '503 Service Unavailable',
      ],
      '#default_value' => $filter->parsedResponse['code'],
    ];
    $form['response']['redirect_url'] = [
      '#type' => 'textfield',
      '#title' => t('Redirect URL'),
      '#description' => t('Affects only response code 301, 302.'),
      '#default_value' => $filter->parsedResponse['redirect_url'],
    ];
    $form['response']['body'] = [
      '#type' => 'textarea',
      '#title' => t('Response body'),
      '#description' => t('Affects only response code except 301, 302.'),
      '#default_value' => $filter->parsedResponse['body'],
    ];

    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    $form['#attached']['library'][] = 'access_filter/common';

    return $form;
  }

  /**
   * Renders HTML containing plugin description and examples.
   *
   * @param array $definitions
   *   The array of plugin definitions.
   *
   * @return string
   *   A HTML markup.
   */
  private function renderPluginDescription(array $definitions) {
    $markup = '<ul class="plugins">';
    foreach ($definitions as $id => $definition) {
      $plugin_markup = '<li>';
      $plugin_markup .= $id;
      if (!empty($definition['description'])) {
        $plugin_markup .= ': ' . $definition['description'];
      }
      if (!empty($definition['examples'])) {
        $plugin_markup .= '<div class="plugin-examples">' . implode('<br>', $definition['examples']) . '</div>';
      }
      $plugin_markup .= '</li>';
      $markup .= $plugin_markup;
    }
    $markup .= '</ul>';
    return $markup;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $parser = new Parser();

    // Validate configuration for conditions.
    $conditions = [];
    try {
      $conditions = $parser->parse($form_state->getValue('conditions'));
    }
    catch (\Exception $ex) {
      $form_state->setErrorByName('conditions', $this->t('Invalid YAML format for conditions. @exception', ['@exception' => $ex->getMessage()]));
    }

    $condition_plugins = $this->conditionPluginManager->getDefinitions();
    $condition_errors = [];
    foreach ($conditions as $i => $condition) {
      $line_errors = [];

      if (isset($condition['type']) && strlen($condition['type'])) {
        $plugin_id = $condition['type'];
        if (isset($condition_plugins[$plugin_id])) {
          $instance = $this->conditionPluginManager->createInstance($plugin_id, $condition);
          $line_errors += array_values($instance->validateConfiguration($condition));
        }
        else {
          $line_errors[] = $this->t('Type @type is invalid.', [
            '@type' => $plugin_id,
          ]);
        }
      }
      else {
        $line_errors[] = $this->t("'@property' is required.", ['@property' => 'type']);
      }

      if (!empty($line_errors)) {
        $condition_errors[$i] = $line_errors;
      }
    }

    if (!empty($condition_errors)) {
      $form_state->setErrorByName('conditions', '');
      drupal_set_message($this->t('There are configuration errors in conditions.'), 'error', TRUE);

      foreach ($condition_errors as $i => $line_errors) {
        drupal_set_message($this->t('Line @line:', ['@line' => $i + 1]), 'error', TRUE);

        foreach ($line_errors as $error) {
          drupal_set_message('- ' . $error, 'error', TRUE);
        }
      }
    }

    // Validate configuration for rules.
    $rules = [];
    try {
      $rules = $parser->parse($form_state->getValue('rules'));
    }
    catch (\Exception $ex) {
      $form_state->setErrorByName('rules', $this->t('Invalid YAML format for rules. @exception', ['@exception' => $ex->getMessage()]));
    }

    $rule_plugins = $this->rulePluginManager->getDefinitions();
    $rule_errors = [];
    foreach ($rules as $i => $rule) {
      $line_errors = [];

      if (isset($rule['type']) && strlen($rule['type'])) {
        $plugin_id = $rule['type'];
        if (isset($rule_plugins[$plugin_id])) {
          $instance = $this->rulePluginManager->createInstance($plugin_id, $rule);

          if (!isset($rule['action']) || !strlen($rule['action'])) {
            $line_errors[] = $this->t("'@property' is required.", ['@property' => 'action']);
          }
          elseif (!in_array($rule['action'], ['deny', 'allow'])) {
            $line_errors[] = $this->t("'action' should be 'deny' or 'allow'.");
          }
          $line_errors += array_values($instance->validateConfiguration($rule));
        }
        else {
          $line_errors[] = $this->t('Type @type is invalid.', [
            '@type' => $plugin_id,
          ]);
        }
      }
      else {
        $line_errors[] = $this->t("'@property' is required.", ['@property' => 'type']);
      }

      if (!empty($line_errors)) {
        $rule_errors[$i] = $line_errors;
      }
    }

    if (!empty($rule_errors)) {
      $form_state->setErrorByName('rules', '');
      drupal_set_message($this->t('There are configuration errors in rules.'), 'error', TRUE);

      foreach ($rule_errors as $i => $line_errors) {
        drupal_set_message($this->t('Line @line:', ['@line' => $i + 1]), 'error', TRUE);

        foreach ($line_errors as $error) {
          drupal_set_message('- ' . $error, 'error', TRUE);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\access_filter\Entity\Filter $filter */
    $filter = $this->entity;

    $dumper = new Dumper();
    $filter->response = $dumper->dump($form_state->getValue('response'), FALSE);

    $status = $filter->save();
    if ($status) {
      drupal_set_message($this->t('Filter %label has been saved.', [
        '%label' => $filter->name,
      ]));
    }
    else {
      drupal_set_message($this->t('Filter %label was not saved.', [
        '%label' => $filter->name,
      ]));
    }

    $form_state->setRedirect('entity.access_filter.collection');
  }

}
