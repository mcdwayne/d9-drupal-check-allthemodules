<?php

namespace Drupal\sendinblue\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sendinblue\SendinblueManager;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Form controller for the content_entity_example entity edit forms.
 *
 * @ingroup content_entity_example
 */
class SignupForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\sendinblue\Entity\Signup */
    $form = parent::buildForm($form, $form_state);
    $signup = $this->entity;
    $settings = (!$signup->settings->first()) ? [] : $signup->settings->first()
      ->getValue();

    $form_state->set('signup', $signup);
    // Form Field for organization.
    $form['#attributes'] = [
      'class' => ['container-fluid'],
      'id' => 'wrap',
    ];
    $form['wrap_left'] = [
      '#prefix' => '<div id="wrap-left" class="col-md-9">',
      '#suffix' => '</div>',
    ];
    $form['wrap_left']['form'] = [
      '#type' => 'fieldset',
      '#title' => t('Form'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['wrap_left']['form']['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#description' => t('The title for this signup form.'),
      '#size' => 35,
      '#maxlength' => 32,
      '#default_value' => $signup->title->value,
      '#required' => TRUE,
      '#attributes' => ['style' => 'width:200px;'],
    ];
    // Machine-readable list name.
    $status = isset($signup->status->value) && $signup->mcsId->value && (($signup->status->value) || ($signup->status->value));
    $form['wrap_left']['form']['name'] = [
      '#type' => 'machine_name',
      '#default_value' => $signup->name->value,
      '#maxlength' => 32,
      '#disabled' => $status,
      '#description' => t('A unique machine-readable name for this list. It must only contain lowercase letters, numbers, and underscores.'),
      '#attributes' => ['style' => 'width:200px;'],
    ];

    $form['wrap_left']['form']['description'] = [
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#default_value' => isset($settings['description']) ? $settings['description'] : '',
      '#rows' => 2,
      '#maxlength' => 500,
      '#description' => t('This description will be shown on the signup form below the title. (500 characters or less)'),
    ];

    $mode_defaults = [
      SENDINBLUE_SIGNUP_BLOCK => [SENDINBLUE_SIGNUP_BLOCK],
      SENDINBLUE_SIGNUP_PAGE => [SENDINBLUE_SIGNUP_PAGE],
      SENDINBLUE_SIGNUP_BOTH => [
        SENDINBLUE_SIGNUP_BLOCK,
        SENDINBLUE_SIGNUP_PAGE,
      ],
    ];

    $form['wrap_left']['form']['mode'] = [
      '#type' => 'checkboxes',
      '#title' => t('Display Mode') . $signup->mode->value,
      '#required' => TRUE,
      '#options' => [
        SENDINBLUE_SIGNUP_BLOCK => t('Block'),
        SENDINBLUE_SIGNUP_PAGE => t('Page'),
      ],
      '#default_value' => !empty($signup->mode->value) ? $mode_defaults[$signup->mode->value] : [],
    ];

    $form['wrap_left']['form']['path'] = [
      '#type' => 'textfield',
      '#title' => t('Page URL'),
      '#description' => t('Path to the signup page. ie "newsletter/signup".'),
      '#default_value' => isset($settings['path']) ? $settings['path'] : NULL,
      '#states' => [
        // Hide unless needed.
        'visible' => [
          ':input[name="mode[' . SENDINBLUE_SIGNUP_PAGE . ']"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="mode[' . SENDINBLUE_SIGNUP_PAGE . ']"]' => ['checked' => TRUE],
        ],
      ],
      '#attributes' => ['style' => 'width:400px;'],
    ];

    // Fields for organization.
    $form['wrap_left']['fields'] = [
      '#type' => 'fieldset',
      '#title' => t('Fields'),
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $attributes = SendinblueManager::getAttributeLists();
    $form['wrap_left']['fields']['mergefields']['EMAIL']['check'] = [
      '#type' => 'checkbox',
      '#title' => t('Email'),
      '#default_value' => TRUE,
      '#disabled' => TRUE,
    ];
    $form['wrap_left']['fields']['mergefields']['EMAIL']['label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#tree' => TRUE,
      '#default_value' => isset($settings['fields']['mergefields']['EMAIL']['label']) ? $settings['fields']['mergefields']['EMAIL']['label'] : t('Email'),
      '#required' => TRUE,
      '#attributes' => ['style' => 'width:200px;'],
      '#prefix' => '<div class="sendinblue_sub_field">',
      '#suffix' => '</div>',
    ];
    $form['wrap_left']['fields']['mergefields']['EMAIL']['required'] = [
      '#type' => 'checkbox',
      '#title' => t('Required?'),
      '#tree' => TRUE,
      '#default_value' => TRUE,
      '#disabled' => TRUE,
      '#prefix' => '<div class="sendinblue_sub_field">',
      '#suffix' => '</div>',
    ];


    foreach ($attributes as $attribute) {
      $settings_attribute =
        (isset($settings['fields']['mergefields'][$attribute['name']])) ?
          $settings['fields']['mergefields'][$attribute['name']] : NULL;

      $form['wrap_left']['fields']['mergefields'][$attribute['name']]['check'] = [
        '#type' => 'checkbox',
        '#title' => $attribute['name'],
        '#default_value' => isset($settings_attribute['check']) ? $settings_attribute['check'] : FALSE,
      ];
      $form['wrap_left']['fields']['mergefields'][$attribute['name']]['label'] = [
        '#type' => 'textfield',
        '#title' => t('Label'),
        '#tree' => TRUE,
        '#default_value' => isset($settings_attribute['label']) ? $settings_attribute['label'] : $attribute['name'],
        '#required' => TRUE,
        '#states' => [
          // Hide unless needed.
          'visible' => [
            ':input[name="fields[mergefields][' . $attribute['name'] . '][check]"]' => ['checked' => TRUE],
          ],
          'required' => [
            ':input[name="mode[' . $attribute['name'] . '][check]"]' => ['checked' => TRUE],
          ],
        ],
        '#attributes' => ['style' => 'width:200px;'],
        '#prefix' => '<div class="sendinblue_sub_field">',
        '#suffix' => '</div>',
      ];
      $form['wrap_left']['fields']['mergefields'][$attribute['name']]['required'] = [
        '#type' => 'checkbox',
        '#title' => t('Required?'),
        '#tree' => TRUE,
        '#default_value' => isset($settings['fields']['mergefields'][$attribute['name']]['required']) ? $settings_attribute['required'] : FALSE,
        '#states' => [
          // Hide unless needed.
          'visible' => [
            ':input[name="fields[mergefields][' . $attribute['name'] . '][check]"]' => ['checked' => TRUE],
          ],
          'required' => [
            ':input[name="mode[' . $attribute['name'] . '][check]"]' => ['checked' => TRUE],
          ],
        ],
        '#prefix' => '<div class="sendinblue_sub_field">',
        '#suffix' => '</div>',
      ];
    }

    $form['wrap_left']['fields']['submit_button'] = [
      '#type' => 'textfield',
      '#title' => t('Submit Button Label'),
      '#required' => 'TRUE',
      '#default_value' => isset($settings['fields']['submit_button']) ? $settings['fields']['submit_button'] : t('Submit'),
      '#attributes' => ['style' => 'width:200px;'],
      '#tree' => TRUE,
    ];

    // Fields Field for organization.
    $form['wrap_left']['subscription'] = [
      '#type' => 'fieldset',
      '#title' => t('Subscription'),
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['wrap_left']['subscription']['settings'] = [
      '#type' => 'fieldset',
      '#title' => t('Settings'),
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $sendinblue_lists = SendinblueManager::getLists();
    $options = [];
    foreach ($sendinblue_lists as $mc_list) {
      $options[$mc_list['id']] = $mc_list['name'];
    }
    $form['wrap_left']['subscription']['settings']['list'] = [
      '#type' => 'select',
      '#title' => t('List where subscribers are saved'),
      '#options' => $options,
      '#default_value' => isset($settings['subscription']['settings']['list']) ? $settings['subscription']['settings']['list'] : '',
      '#description' => t('Select the list where you want to add your new subscribers'),
      '#attributes' => ['style' => 'width:200px;'],
    ];
    $form['wrap_left']['subscription']['settings']['redirect_url'] = [
      '#type' => 'textfield',
      '#title' => t('URL redirection'),
      '#required' => FALSE,
      '#default_value' => isset($settings['subscription']['settings']['redirect_url']) ? $settings['subscription']['settings']['redirect_url'] : '',
      '#description' => t('Redirect to this URL after subscription'),
      '#attributes' => ['style' => 'width:400px;'],
    ];
    $form['wrap_left']['subscription']['settings']['email_confirmation'] = [
      '#type' => 'checkbox',
      '#title' => t('Email confirmation'),
      '#required' => FALSE,
      '#default_value' => isset($settings['subscription']['settings']['email_confirmation']) ? $settings['subscription']['settings']['email_confirmation'] : '',
      '#description' => t('You can choose to send a confirmation email. You will be able to set up the template & sender that will be sent to your new subscribers'),
    ];
    $sendinblue_templates = SendinblueManager::getTemplateList();
    $options = [];
    foreach ($sendinblue_templates as $mc_template) {
      $options[$mc_template['id']] = $mc_template['name'];
    }
    $form['wrap_left']['subscription']['settings']['template'] = [
      '#type' => 'select',
      '#title' => t('Select Template'),
      '#options' => $options,
      '#default_value' => isset($settings['subscription']['settings']['template']) ? $settings['subscription']['settings']['template'] : '-1',
      '#description' => t('Select the template that will be sent to your new subscribers. You can create new template at @SendinBlue.',
        ['@SendinBlue' => Link::fromTextAndUrl(t('SendinBlue'), Url::fromUri('https://my.sendinblue.com/camp/listing/?utm_source=drupal_plugin&utm_medium=plugin&utm_campaign=module_link#temp_active_m'))->toString()]),
      '#states' => [
        // Hide unless needed.
        'visible' => [
          ':input[name="subscription[settings][email_confirmation]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="subscription[settings][email_confirmation]"]' => ['checked' => TRUE],
        ],
      ],
      '#attributes' => ['style' => 'width:200px;'],
    ];
    $form['wrap_left']['subscription']['messages'] = [
      '#type' => 'fieldset',
      '#title' => t('Messages'),
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['wrap_left']['subscription']['messages']['success'] = [
      '#type' => 'textfield',
      '#title' => t('Success message'),
      '#required' => FALSE,
      '#default_value' => isset($settings['subscription']['messages']['success']) ? $settings['subscription']['messages']['success'] : t('Thank you, you have successfully registered!'),
      '#description' => t('Set up the success message that will appear when one of your visitors successfully signs up'),
      '#attributes' => ['style' => 'width:400px;'],
    ];
    $form['wrap_left']['subscription']['messages']['general'] = [
      '#type' => 'textfield',
      '#title' => t('General error message'),
      '#required' => FALSE,
      '#default_value' => isset($settings['subscription']['messages']['general']) ? $settings['subscription']['messages']['general'] : t('Something wrong occured'),
      '#description' => t('Set up the message that will appear when an error occurs during the subscription process'),
      '#attributes' => ['style' => 'width:400px;'],
    ];
    $form['wrap_left']['subscription']['messages']['existing'] = [
      '#type' => 'textfield',
      '#title' => t('Existing subscribers'),
      '#required' => FALSE,
      '#default_value' => isset($settings['subscription']['messages']['existing']) ? $settings['subscription']['messages']['existing'] : 'You have already registered',
      '#description' => t('Set up the message that will appear when a subscriber is already in your database'),
      '#attributes' => ['style' => 'width:400px;'],
    ];
    $form['wrap_left']['subscription']['messages']['invalid'] = [
      '#type' => 'textfield',
      '#title' => t('Existing subscribers'),
      '#required' => FALSE,
      '#default_value' => isset($settings['subscription']['messages']['invalid']) ? $settings['subscription']['messages']['invalid'] : 'Your email address is invalid',
      '#description' => t('Set up the message that will appear when the email address used to sign up is not valid'),
      '#attributes' => ['style' => 'width:400px;'],
    ];
    // $markup = SendinblueManager::generateSidebar();
    $form['wrap_right'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="wrap-right-side" class="col-md-3">',
    // '#markup' => $markup,.
      '#suffix' => '</div><div class="clearfix"></div>',
      '#tree' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Button-level validation handlers are highly discouraged for entity forms,
   * as they will prevent entity validation from running. If the entity is going
   * to be saved during the form submission, this method should be manually
   * invoked from the button-level validation handler, otherwise an exception
   * will be thrown.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->buildEntity($form, $form_state);

    $path = $form_state->getValue(['path']);
    $redirect_url = $form_state->getValue([
      'subscription',
      'settings',
      'redirect_url',
    ]);

    if (!empty($path) && UrlHelper::isExternal($path)) {
      $form_state->setErrorByName('path', 'The path "' . $path . '" for Display Mode is invalid. You have to put a valid internal path.');
      return;
    }

    if (!empty($redirect_url) && UrlHelper::isExternal($redirect_url)) {
      $form_state->setErrorByName('redirect_url', 'The redirect_url "' . $redirect_url . '" for SUBSCRIPTION is invalid. You have to put a valid internal path.');
      return;
    }

    return $entity;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue(['mode', SENDINBLUE_SIGNUP_PAGE])) {
      $form_state->setValue(['path'], '');
    }

    $signup_mode = array_sum($form_state->getValue(['mode']));
    $signup_path = $form_state->getValue(['path']);
    $form_state->setValue(['mode'], $signup_mode);

    $this->entity->title = $form_state->getValue(['title']);
    $this->entity->name = $form_state->getValue(['name']);
    $this->entity->mode = $signup_mode;
    $this->entity->settings =
      [
        'description' => $form_state->getValue(['description']),
        'path' => $signup_path,
        'fields' => $form_state->getValue(['fields']),
        'subscription' => $form_state->getValue(['subscription']),
      ];

    if (isset($signup_path)) {
      // We have a new (or removed) path. Rebuild menus.
      \Drupal::service('router.builder')->rebuild();
    }
    drupal_set_message(t('Signup form @name has been saved.',
      ['@name' => $form_state->getValue(['title'])]));
    $form_state->setRedirect('admin/config/system/sendinblue/signup');

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.sendinblue_signup_form.collection');
    $entity = $this->getEntity();
    $entity->save();
  }

}
