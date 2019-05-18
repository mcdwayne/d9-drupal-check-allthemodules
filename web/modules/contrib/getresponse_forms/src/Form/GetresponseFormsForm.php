<?php

namespace Drupal\getresponse_forms\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\getresponse_forms\FieldManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the GetresponseForms entity edit form.
 *
 * @ingroup getresponse_forms
 */
class GetresponseFormsForm extends EntityForm {

  /**
   * The image effect manager service.
   *
   * @var \Drupal\getresponse_forms\FieldManager
   */
  protected $fieldManager;

  /**
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   */
  public function __construct(QueryFactory $entity_query, FieldManager $field_manager) {
    $this->entityQuery = $entity_query;
    $this->fieldManager = $field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('plugin.manager.getresponse_forms.field')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $signup = $this->entity;

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#size' => 35,
      '#maxlength' => 32,
      '#default_value' => $signup->title,
      '#description' => $this->t('The title for this signup form.'),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $signup->id,
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => array(
        'source' => array('title'),
        'exists' => 'getresponse_forms_load',
      ),
      '#description' => t('A unique machine-readable name for this list. It must only contain lowercase letters, numbers, and underscores.'),
      '#disabled' => !$signup->isNew(),
    );

    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => 'Description',
      '#default_value' => isset($signup->description) ? $signup->description : '',
      '#rows' => 2,
      '#maxlength' => 500,
      '#description' => t('This description will be shown on the signup form below the title. (500 characters or less)'),
    );
    $mode_defaults = array(
      GETRESPONSE_FORMS_BLOCK => array(GETRESPONSE_FORMS_BLOCK),
      GETRESPONSE_FORMS_PAGE => array(GETRESPONSE_FORMS_PAGE),
      GETRESPONSE_FORMS_BOTH => array(GETRESPONSE_FORMS_BLOCK, GETRESPONSE_FORMS_PAGE),
    );
    $form['mode'] = array(
      '#type' => 'checkboxes',
      '#title' => 'Display Mode',
      '#required' => TRUE,
      '#options' => array(
        GETRESPONSE_FORMS_BLOCK => 'Block',
        GETRESPONSE_FORMS_PAGE => 'Page',
      ),
      '#default_value' => !empty($signup->mode) ? $mode_defaults[$signup->mode] : array(),
    );

    $form['settings'] = array(
      '#type' => 'details',
      '#title' => 'Settings',
      '#open' => TRUE,
    );

    $form['settings']['path'] = array(
      '#type' => 'textfield',
      '#title' => 'Page URL',
      '#description' => t('Path to the signup page. ie "newsletter/signup".'),
      '#default_value' => isset($signup->path) ? $signup->path : NULL,
      '#states' => array(
        // Hide unless needed.
        'visible' => array(
          ':input[name="mode[' . GETRESPONSE_FORMS_PAGE . ']"]' => array('checked' => TRUE),
        ),
        'required' => array(
          ':input[name="mode[' . GETRESPONSE_FORMS_PAGE . ']"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['settings']['submit_button'] = array(
      '#type' => 'textfield',
      '#title' => 'Submit Button Label',
      '#required' => 'TRUE',
      '#default_value' => isset($signup->submit_button) ? $signup->submit_button : 'Submit',
    );

    $form['settings']['confirmation_message'] = array(
      '#type' => 'textfield',
      '#title' => 'Confirmation Message',
      '#description' => 'This message will appear after a successful submission of this form. Leave blank for no message, but make sure you configure a destination in that case unless you really want to confuse your site visitors.',
      '#default_value' => isset($signup->confirmation_message) ? $signup->confirmation_message : 'You have been successfully subscribed.',
    );

    $form['settings']['destination'] = array(
      '#type' => 'textfield',
      '#title' => 'Form destination page',
      '#description' => 'Leave blank to stay on the form page.',
      '#default_value' => isset($signup->destination) ? $signup->destination : NULL,
    );

    $form['gr_lists_config'] = array(
      '#type' => 'details',
      '#title' => t('GetResponse List Selection & Configuration'),
      '#open' => TRUE,
    );
    $lists = getresponse_get_lists();
    $options = array();
    foreach ($lists as $gr_list) {
      $options[$gr_list->campaignId] = $gr_list->name;
    }
    $gr_admin_url = Link::fromTextAndUrl('GetResponse', Url::fromUri('https://app.getresponse.com', array('attributes' => array('target' => '_blank', 'rel' => 'noopener noreferrer'))));
    $form['gr_lists_config']['gr_lists'] = array(
      '#type' => 'radios',
      '#title' => t('GetResponse Lists (Campaigns)'),
      '#description' => t('Select the list to which your signup form will submit to. You can create additional lists at @GetResponse.',
        array('@GetResponse' => $gr_admin_url->toString())),
      '#options' => $options,
      '#default_value' => $signup->gr_lists,
      '#required' => TRUE,
    );

    $custom_fields = getresponse_get_custom_fields();

    // Build the list of existing custom fields for this form.
    $form['custom_fields'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'getresponse-custom-field-order-weight',
        ],
      ],
      '#attributes' => [
        'id' => 'getresponse-custom_fields',
      ],
      '#empty' => t('There are currently no custom_fields in this style. Add one by selecting an option below.'),
    ];
    foreach ($this->entity->getFields() as $field) {
      $key = $field->getUuid();
      $form['custom_fields'][$key]['#attributes']['class'][] = 'draggable';
      $form['custom_fields'][$key]['#weight'] = isset($user_input['custom_fields']) ? $user_input['custom_fields'][$key]['weight'] : NULL;
      $form['custom_fields'][$key]['field'] = [
        '#tree' => FALSE,
        'data' => [
          'label' => [
            '#plain_text' => $field->label(),
          ],
        ],
      ];

   /**
    * Still no need for summaries and such yet
      $summary = $field->getSummary();

      if (!empty($summary)) {
        $summary['#prefix'] = ' ';
        $form['custom_fields'][$key]['field']['data']['summary'] = $summary;
      }
    */

      $form['custom_fields'][$key]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $field->name]),
        '#title_display' => 'invisible',
        '#default_value' => $field->getWeight(),
        '#attributes' => [
          'class' => ['getresponse-custom-field-order-weight'],
        ],
      ];

      $links = [];
/**
  * NOTE:  We don't currently have a need for configurable fields so keep this in our back pocket.
      $is_configurable = $field instanceof ConfigurableImageEffectInterface;
      if ($is_configurable) {
        $links['edit'] = [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('getresponse.field_edit_form', [
            'image_style' => $this->entity->id(),
            'image_field' => $key,
          ]),
        ];
      }
  */
      $links['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('getresponse.field_delete', [
          'getresponse_forms' => $this->entity->id(),
          'field' => $key,
        ]),
      ];
      $form['custom_fields'][$key]['operations'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];
    }

    // Build the new field addition form and add it to the field list.
    $new_field_options = [];
    $custom_fields = $this->fieldManager->getDefinitions();
    foreach ($custom_fields as $field => $definition) {
      $new_field_options[$field] = $definition['label'];
    }
    $form['custom_fields']['new'] = [
      '#tree' => FALSE,
      '#weight' => isset($user_input['weight']) ? $user_input['weight'] : NULL,
      '#attributes' => ['class' => ['draggable']],
    ];
    $form['custom_fields']['new']['field'] = [
      'data' => [
        'new' => [
          '#type' => 'select',
          '#title' => $this->t('field'),
          '#title_display' => 'invisible',
          '#options' => $new_field_options,
          '#empty_option' => $this->t('Select a new field'),
        ],
        [
          'add' => [
            '#type' => 'submit',
            '#value' => $this->t('Add'),
            '#validate' => ['::fieldValidate'],
            '#submit' => ['::submitForm', '::fieldSave'],
          ],
        ],
      ],
      '#prefix' => '<div class="custom-field-new">',
      '#suffix' => '</div>',
    ];

    $form['custom_fields']['new']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight for new field'),
      '#title_display' => 'invisible',
      '#default_value' => count($this->custom_fields) + 1,
      '#attributes' => ['class' => ['getresponse-custom-field-order-weight']],
    ];
    $form['custom_fields']['new']['operations'] = [
      'data' => [],
    ];



    $form['notification_settings'] = [
      '#type' => 'details',
      '#title' => t('Send notification of signups'),
      '#open' => FALSE,
    ];
    $form['notification_settings']['notification_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('E-mail'),
      '#size' => 35,
      '#maxlength' => 100,
      '#default_value' => $signup->notification_email,
      '#description' => $this->t('E-mail address to send administrative notification of signups to.'),
    ];

    return $form;
  }

  /**
   * Validate handler for custom field.
   */
  public function fieldValidate($form, FormStateInterface $form_state) {
    if (!$form_state->getValue('new')) {
      $form_state->setErrorByName('new', $this->t('Select a custom field to add.'));
    }
  }

  /**
   * Submit handler for custom field.
   */
  public function fieldSave($form, FormStateInterface $form_state) {
    $this->save($form, $form_state);

    // Check if this field has any configuration options.
    $field = $this->fieldManager->getDefinition($form_state->getValue('new'));

    // Load the configuration form for this option.
    if (is_subclass_of($effect['class'], '\Drupal\image\ConfigurableImageEffectInterface')) {
      $form_state->setRedirect(
        'image.effect_add_form',
        [
          'image_style' => $this->entity->id(),
          'image_effect' => $form_state->getValue('new'),
        ],
        ['query' => ['weight' => $form_state->getValue('weight')]]
      );
    }
    // If there's no form, immediately add the field.
    else {
      $field = [
        'id' => isset($field['plugin_id']) ? $field['plugin_id'] : $field['id'],
        'data' => [],
        'weight' => $form_state->getValue('weight'),
      ];
      $field_id = $this->entity->addField($field);
      $this->entity->save();
      if (!empty($field_id)) {
        drupal_set_message($this->t('The field was successfully added.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Update field weights.
    if (!$form_state->isValueEmpty('custom_fields')) {
      $this->updateFieldWeights($form_state->getValue('custom_fields'));
    }

    // $form_state->setRedirect('getresponse_forms.admin');

    parent::submitForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $mode = $form_state->getValue('mode');

    /* @var $signup \Drupal\getresponse_forms\Entity\GetresponseForms */
    $signup = $this->getEntity();
    $signup->mode = array_sum($mode);

    // Clear path value if mode doesn't include signup page.
    if (!isset($mode[GETRESPONSE_FORMS_PAGE])) {
      $signup->path = '';
    }

    $signup->save();

    // Update field weights.
    if (!$form_state->isValueEmpty('custom_fields')) {
      $this->updateFieldWeights($form_state->getValue('custom_fields'));
    }


    // drupal_set_message(var_export($signup, TRUE));

    \Drupal::service('router.builder')->setRebuildNeeded();

    drupal_set_message($this->t('@name form saved', ['@name' => $form_state->getValue('title')]));
  }



  public function exist($id) {
    $entity = $this->entityQuery->get('getresponse_forms')
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }


  /**
    * Updates field weights.
    *
    * @param array $fields
    *   Associative array with fields having uuid as keys and array
    *   with field data as values.
    */
   protected function updateFieldWeights(array $fields) {
     foreach ($fields as $uuid => $field_data) {
       if ($this->entity->getFields()->has($uuid)) {
         $this->entity->getField($uuid)->setWeight($field_data['weight']);
       }
     }
     $this->entity->save();
   }

}
