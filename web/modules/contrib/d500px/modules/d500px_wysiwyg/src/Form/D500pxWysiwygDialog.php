<?php

namespace Drupal\d500px_wysiwyg\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\d500px\D500pxPhotos;

/**
 * A class for providing 500px photo dialog.
 */
class D500pxWysiwygDialog extends FormBase {

  /**
   * Settings constructor.
   *
   * @param \Drupal\d500px_wysiwyg\Form\ConfigFactoryInterface $config_factory
   *   Config Factory.
   * @param \Drupal\d500px\D500pxPhotos $d500pxphotos
   *   Drupal 500px Photos class.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              D500pxPhotos $d500pxphotos) {
    $this->config = $config_factory;
    $this->d500pxphotos = $d500pxphotos;
    $this->d500pxhelpers = $d500pxphotos->d500pxhelpers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('d500px.D500pxPhotos')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'd500px_wysiwyg_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Editor $editor = NULL) {
    // The default values are set directly from \Drupal::request()->request,
    // provided by the editor plugin opening the dialog.
    $user_input = $form_state->getUserInput();
    $input = isset($user_input['editor_object']) ? $user_input['editor_object'] : [];

    $image_options_available = $this->d500pxhelpers->photoGetSizes();

    foreach ($image_options_available as $image_option_key => $value) {
      $image_options[$image_option_key] = $value['width'] . 'x' . $value['height'];
    }

    $form['#tree'] = TRUE;
    // Ensure relevant dialog libraries are attached.
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';

    $form['#prefix'] = '<div id="d500px-wysiwyg-dialog-form">';
    $form['#suffix'] = '</div>';

    $form['d500px_photo']['photoid'] = [
      '#title' => $this->t('Photo ID'),
      '#type' => 'textfield',
      '#default_value' => isset($input['photoid']) ? $input['photoid'] : '',
      '#maxlength' => 12,
      '#required' => TRUE,
    ];

    $form['d500px_photo']['imagesize'] = [
      '#title' => $this->t('Thumbnail Size'),
      '#type' => 'select',
      '#options' => $image_options,
      '#default_value' => isset($input['imagesize']) ? $input['imagesize'] : '',
      '#description'        => $this->t('The photo size to be displayed.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#d500px-wysiwyg-dialog-form', $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

}
