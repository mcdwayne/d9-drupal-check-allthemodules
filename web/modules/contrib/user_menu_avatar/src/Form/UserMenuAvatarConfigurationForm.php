<?php

/**
 * Drupal\user_menu_avatar\Form\UserMenuAvatarConfigurationForm.
 */

namespace Drupal\user_menu_avatar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines our form class.
 */
class UserMenuAvatarConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_menu_avatar_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'user_menu_avatar.user_menu_avatar_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('user_menu_avatar.user_menu_avatar_settings');

    $avatar_shape_options = [
      'circle' => t('Circle'),
      'square' => t('Square'),
    ];

    $avatar_yes_no_options = [
      'yes' => t('Yes'),
      'no' => t('No'),
    ];

    $form['user_avatar_heading'] = [
      '#type' => 'item',
      '#markup' => t('<h2>Available User Menu Avatar Settings</h2>'),
      '#weight' => -10,
    ];

    $form['link_text_name_wrapper'] = [
      '#type' => 'fieldset',
      '#weight' => 1,
      '#title' => 'Link Settings',
      '#attributes' => [
        'class' => [
          'link-text-name-wrapper',
        ],
      ],
    ];

    $form['link_text_name_wrapper']['link_text_name'] = [
      '#type' => 'textfield',
      '#title' => t('Set link text'),
      '#required' => TRUE,
      '#description' => t('Set the text of the menu link to be replaced. Case sensitive. "My account" link is default.'),
      '#maxlength' => 140,
      '#size' => 60,
      '#default_value' => $config->get('link_text_name') ?: 'My account',
    ];

    $form['show_menu_avatar_wrapper'] = [
      '#type' => 'fieldset',
      '#weight' => 2,
      '#title' => 'Image Settings',
      '#attributes' => [
        'class' => [
          'show-menu-avatar',
        ],
      ],
    ];

    $form['show_menu_avatar_wrapper']['show_menu_avatar'] = [
      '#type' => 'radios',
      '#title' => t('Show Avatar'),
      '#required' => TRUE,
      '#options' => $avatar_yes_no_options,
      '#description' => t('Choose to show the avatar.'),
      '#default_value' => $config->get('show_menu_avatar') ?: 'yes',
    ];

    $form['show_menu_avatar_wrapper']['avatar_shape'] = [
      '#type' => 'radios',
      '#title' => t('User Menu Avatar Shape'),
      '#required' => TRUE,
      '#options' => $avatar_shape_options,
      '#description' => t('Choose the shape of the avatar image in the user menu.'),
      '#default_value' => $config->get('avatar_shape') ?: 'circle',
      '#states' => [
        'visible' => [
          ':input[name="show_menu_avatar"]' => ['value' => 'yes'],
        ],
      ],
    ];

    $form['show_menu_avatar_wrapper']['avatar_size'] = [
      '#type' => 'textfield',
      '#attributes' => [
        ' type' => 'number',
      ],
      '#title' => t('User Menu Avatar Size (px)'),
      '#required' => TRUE,
      '#description' => t('The size of the User Menu Avatar in "pixels". Applies to both width and height. Numeric value only.'),
      '#maxlength' => 3,
      '#size' => 30,
      '#default_value' => $config->get('avatar_size') ?: '50',
      '#states' => [
        'visible' => [
          ':input[name="show_menu_avatar"]' => ['value' => 'yes'],
        ],
      ],
    ];

    $form['show_menu_avatar_wrapper']['avatar_image_field'] = [
      '#type' => 'textfield',
      '#title' => t('Image field name'),
      '#required' => TRUE,
      '#description' => t('Will default to "user_picture" unless another field name is entered.'),
      '#maxlength' => 140,
      '#size' => 60,
      '#default_value' => $config->get('avatar_image_field') ?: 'user_picture',
      '#states' => [
        'visible' => [
          ':input[name="show_menu_avatar"]' => ['value' => 'yes'],
        ],
      ],
    ];

    $form['show_menu_name_wrapper'] = [
      '#type' => 'fieldset',
      '#weight' => 4,
      '#title' => 'Name Settings',
      '#attributes' => [
        'class' => [
          'show-name-avatar',
        ],
      ],
    ];

    $form['show_menu_name_wrapper']['show_menu_name'] = [
      '#type' => 'radios',
      '#title' => t('Show Name'),
      '#required' => TRUE,
      '#options' => $avatar_yes_no_options,
      '#description' => t('Choose to show the user name. Defaults to "username" value.'),
      '#default_value' => $config->get('show_menu_name') ?: 'yes',
    ];

    $form['show_menu_name_wrapper']['avatar_custom_name_field'] = [
      '#type' => 'textfield',
      '#title' => t('Custom name field name'),
      '#required' => FALSE,
      '#description' => t('Use a custom field for the user menu name. Leave blank to use default "username" value.'),
      '#maxlength' => 140,
      '#size' => 60,
      '#default_value' => $config->get('avatar_custom_name_field') ?: '',
      '#states' => [
        'visible' => [
          ':input[name="show_menu_name"]' => ['value' => 'yes'],
        ],
      ],
    ];

    $form['form_info'] = [
      '#type' => 'item',
      '#weight' => 10,
      '#markup' => t('<p>User Menu Avatar uses Background-image CSS to position the user picture. The "width" and "height" are set by inline styles on the span element. The "border-radius" only applies if you choose shape circle.</p>'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->configFactory->getEditable('user_menu_avatar.user_menu_avatar_settings')
      ->set('avatar_shape', $values['avatar_shape'])
      ->set('link_text_name', $values['link_text_name'])
      ->set('avatar_size', $values['avatar_size'])
      ->set('avatar_image_field', $values['avatar_image_field'])
      ->set('show_menu_avatar', $values['show_menu_avatar'])
      ->set('show_menu_name', $values['show_menu_name'])
      ->set('avatar_custom_name_field', $values['avatar_custom_name_field'])
      ->save();

    parent::submitForm($form, $form_state);

  }

}
