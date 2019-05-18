<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ChangeFieldInfo.
 *
 * Changes on multiple value fields can't be done via hooks yet.
 *
 * @see https://www.drupal.org/node/1592814
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "change_field_info",
 *   label = @Translation("Change entity form field"),
 *   group = @Translation("Entity"),
 *   description = @Translation("Change a form field: Make required/optional/read-only/hidden/dependent/change field options values."),
 *   reactsOnIds = {"form_field_alter"},
 *   isContextDependent = TRUE,
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   hasTargetField = FALSE,
 * )
 */
class ChangeFieldInfo extends BusinessRulesActionPlugin {

  const MAKE_REQUIRED  = 'make_required';
  const MAKE_OPTIONAL  = 'make_optional';
  const MAKE_READ_ONLY = 'make_read_only';
  const MAKE_HIDDEN    = 'make_hidden';

  /**
   * The available action options.
   *
   * @var array
   */
  protected $actionOptions = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration = [], $plugin_id = 'change_field_info', $plugin_definition = []) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->actionOptions = [
      ''                   => t('-Select-'),
      self::MAKE_REQUIRED  => t('Make field required'),
      self::MAKE_OPTIONAL  => t('Make field optional'),
      self::MAKE_READ_ONLY => t('Make field read only'),
      self::MAKE_HIDDEN    => t('Make field hidden'),
    ];

  }

  /**
   * Add new field on action settings.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state.
   */
  public static function addFieldSubmit(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\business_rules\Entity\Action $action */
    $action   = $form_state->get('action');
    $field    = $form_state->getValue('fields')['new.field'];
    $id       = $field['field'] . '__' . $field['action'];
    $settings = $action->getSettings();

    $availableFields = \Drupal::getContainer()
      ->get('business_rules.util')
      ->getBundleEditableFields($action->getTargetEntityType(), $action->getTargetBundle());

    $settings['fields'][$id] = [
      'id'     => $id,
      'field'  => $field['field'],
      'action' => $field['action'],
    ];

    uasort($settings['fields'], function ($a, $b) use ($availableFields) {
      return ($availableFields[$a['field']] > $availableFields[$b['field']]) ? 1 : -1;
    });

    $action->setSetting('fields', $settings['fields']);
    $action->save();

    $form_state->setRedirect('entity.business_rules_action.edit_form', ['business_rules_action' => $action->id()], ['fragment' => 'field-' . $id]);
  }

  /**
   * Remove one field from the action.
   *
   * @param string $action
   *   The action id.
   * @param string $field
   *   The field id.
   * @param string $method
   *   The method: ajax|nojs.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The response.
   */
  public static function removeField($action, $field, $method) {
    $action = Action::load($action);
    $fields = $action->getSettings('fields');
    unset($fields[$field]);
    $action->setSetting('fields', $fields);
    $action->save();

    if ($method == 'ajax') {
      $response = new AjaxResponse();
      $response->addCommand(new RemoveCommand('#field-' . $field));

      return $response;
    }
    else {
      $url = new Url('entity.business_rules_action.edit_form', ['business_rules_action' => $action->id()]);

      return new RedirectResponse($url->toString());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    if ($item->isNew()) {
      return [];
    }

    $form_state->set('action', $item);

    $settings['fields'] = [
      '#type'       => 'table',
      '#header'     => [
        'field'      => t('Filed'),
        'action'     => t('Action'),
        'operations' => t('Operations'),
      ],
      '#attributes' => ['id' => 'array_variable_fields_table'],
    ];

    $settings['info'] = [
      '#type'   => 'markup',
      '#markup' => t('Multiple value fields cannot be changed to be Required or Optional by this module. Create a new rule as "Entity form validation" to achieve this purpose see this issue on https://www.drupal.org/node/1592814. 
      <br>Hidden fields are removed from the form array, and not rendered. So be careful if you hide a required field because some field widgets can validate it anyway.'),
    ];

    $this->getRows($item, $settings['fields']);

    return $settings;
  }

  /**
   * Get the current fields on the variable array.
   *
   * @param \Drupal\business_rules\ItemInterface $item
   *   The variable.
   * @param array $settings
   *   The current setting to add rows.
   */
  private function getRows(ItemInterface $item, array &$settings) {

    $fields          = $item->getSettings('fields');
    $availableFields = $this->util->getBundleEditableFields($item->getTargetEntityType(), $item->getTargetBundle());

    if (count($fields)) {
      foreach ($fields as $key => $field) {

        $links['remove'] = [
          'title'  => t('Remove'),
          'url'    => Url::fromRoute('business_rules.plugins.action.change_field_info.remove_field', [
            'action' => $item->id(),
            'field'  => $field['id'],
            'method' => 'nojs',
          ],
            [
              'attributes' => [
                'class' => ['use-ajax'],
              ],
            ]
          ),
          'weight' => 1,
        ];

        $settings[$key] = [
          'field'       => [
            '#type'   => 'markup',
            '#markup' => $availableFields[$field['field']],
          ],
          'action'      => [
            '#type'   => 'markup',
            '#markup' => $this->actionOptions[$field['action']],
          ],
          'operations'  => [
            '#type'  => 'operations',
            '#links' => $links,
          ],
          '#attributes' => ['id' => 'field-' . $field['id']],
        ];
      }
    }

    $settings['new.field'] = [
      'field'      => [
        '#type'     => 'select',
        '#required' => FALSE,
        '#options'  => array_merge(['' => t('-Select-')], $availableFields),
      ],
      'action'     => [
        '#type'     => 'select',
        '#required' => FALSE,
        '#options'  => $this->actionOptions,
      ],
      'operations' => [
        '#type'     => 'submit',
        '#value'    => t('Add'),
        '#validate' => [get_class($this) . '::validateAddFieldForm'],
        '#submit'   => [get_class($this) . '::addFieldSubmit'],
      ],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $form_state) {
    unset($form['variables']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    self::validateAddFieldForm($form, $form_state);
  }

  /**
   * Validate the add field operation.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state.
   */
  public static function validateAddFieldForm(array &$form, FormStateInterface $form_state) {
    $field        = $form_state->getValue('fields');
    $field_field  = $field['new.field']['field'];
    $field_action = $field['new.field']['action'];

    if ((empty($field_action) && !empty($field_field)) || (!empty($field_action) && empty($field_field))) {
      $form_state->setErrorByName('fields', t("Please, fill all field data or none of them."));
    }

    // The title field can't be optional.
    if ($field['new.field']['field'] == 'title' && $field['new.field']['action'] == self::MAKE_OPTIONAL) {
      $form_state->setErrorByName('fields', t('The title field cannot be optional.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processSettings(array $settings, ItemInterface $item) {

    if ($item->isNew()) {
      return [];
    }

    if (count($item->getSettings('fields'))) {
      $settings['fields'] += $item->getSettings('fields');
    }

    if ($settings['fields']['new.field']['field'] && $settings['fields']['new.field']['action']) {
      $availableFields               = $this->util->getBundleEditableFields($item->getTargetEntityType(), $item->getTargetBundle());
      $id                            = $settings['fields']['new.field']['field'] . '__' . $settings['fields']['new.field']['action'];
      $settings['fields'][$id]       = $settings['fields']['new.field'];
      $settings['fields'][$id]['id'] = $id;

      uasort($settings['fields'], function ($a, $b) use ($availableFields) {
        return ($availableFields[$a['field']] > $availableFields[$b['field']]) ? 1 : -1;
      });
    }

    unset($settings['fields']['new.field']);

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {
    $fields = $action->getSettings('fields');

    if (!count($fields)) {
      // Nothing to do.
      $result = [
        '#type'   => 'markup',
        '#markup' => t('Nothing to do.'),
      ];

      return $result;
    }

    $element = $event->getArgument('element');
    $context = $event->getArgument('context');
    /** @var \Drupal\Core\Field\FieldItemList $items */
    $items        = $context['items'];
    $element_name = $items->getName();

    // Change the field properties.
    foreach ($fields as $field) {
      if ($field['field'] == $element_name) {
        $this->changeFieldInfo($element, $field);
      }
    }

    $event->setArgument('element', $element);

    foreach ($fields as $field) {
      $debug_message = t('<br>%method: %field', [
        '%field'  => $field['field'],
        '%method' => $this->actionOptions[$field['action']],
      ]);

      $result[] = [
        '#type'   => 'markup',
        '#markup' => $debug_message,
      ];
    }

    return $result;
  }

  /**
   * Change info at the form array.
   */
  protected function changeFieldInfo(array &$element, $action_field) {
    switch ($action_field['action']) {
      case self::MAKE_REQUIRED:
        $element['#required'] = TRUE;
        if (isset($element[0])) {
          $element[0]['#required'] = TRUE;
        }
        if (isset($element['target_id'])) {
          $element['target_id']['#required'] = TRUE;
        }
        break;

      case self::MAKE_OPTIONAL:
        $element['#required'] = FALSE;
        if (isset($element[0])) {
          $element[0]['#required'] = FALSE;
        }
        if (isset($element['target_id'])) {
          $element['target_id']['#required'] = FALSE;
        }
        break;

      case self::MAKE_READ_ONLY:
        $element['#disabled'] = TRUE;
        break;

      case self::MAKE_HIDDEN:
        $element = [];
        break;

    }
  }

}
