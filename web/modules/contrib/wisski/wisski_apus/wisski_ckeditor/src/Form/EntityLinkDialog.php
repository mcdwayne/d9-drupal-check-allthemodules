<?php

/**
 * @file
 * Contains \Drupal\wisski_ckeditor\Form\EntityLinkDialog.
 */

namespace Drupal\wisski_ckeditor\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\linkit\AttributeCollection;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a linkit dialog for text editors.
 */
class EntityLinkDialog extends FormBase {

  /**
   * The editor storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $editorStorage;

  /**
   * The linkit profile storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $linkitProfileStorage;

  /**
   * The linkit profile.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  protected $linkitProfile;

  /**
   * Constructs a form object for linkit dialog.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $editor_storage
   *   The editor storage service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $linkit_profile_storage
   *   The linkit profile storage service.
   */
  public function __construct(EntityStorageInterface $editor_storage, EntityStorageInterface $linkit_profile_storage) {
    $this->editorStorage = $editor_storage;
    $this->linkitProfileStorage = $linkit_profile_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('editor'),
      $container->get('entity.manager')->getStorage('linkit_profile')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wisski_ckeditor_entity_link_dialog_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\filter\Entity\FilterFormat $filter_format
   *   The filter format for which this dialog corresponds.
   * @param string $search
   *   A search string.
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL, $search = '') {
    
    // TODO: can we get rid of request()? It is discouraged...
    $request = \Drupal::request();
    $string = Unicode::strtolower($request->query->get('q'));
    

    // The default values are set directly from \Drupal::request()->request,
    // provided by the editor plugin opening the dialog.
    $user_input = $form_state->getUserInput();
    $input = isset($user_input['editor_object']) ? $user_input['editor_object'] : [];

    /** @var \Drupal\editor\EditorInterface $editor */
    $editor = $this->editorStorage->load($filter_format->id());
    $linkit_profile_id = $editor->getSettings()['plugins']['wisski_quick_entity_picker']['linkit_profile'];
    $this->linkitProfile = $this->linkitProfileStorage->load($linkit_profile_id);

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#attached']['library'][] = 'wisski_ckeditor/quick_entity_picker';
    $form['#prefix'] = '<div id="quick-entity-picker-editor-dialog-form">';
    $form['#suffix'] = '</div>';

    // Everything under the "attributes" key is merged directly into the
    // generated link tag's attributes.
    $form['attributes']['href'] = [
      '#title' => '',
      '#type' => 'linkit',
//      '#default_value' => isset($input['href']) ? $input['href'] : '',
      '#default_value' => $search, #isset($input['href']) ? $input['href'] : '',
      '#description' => $this->t('Start typing to find content or paste a URL.'),
      '#autocomplete_route_name' => 'linkit.autocomplete',
      '#autocomplete_route_parameters' => [
        'linkit_profile_id' => $linkit_profile_id
      ],
      '#weight' => 0,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $attributes = array_filter($form_state->getValue('attributes'));
    $form_state->setValue('attributes', $attributes);
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
      $response->addCommand(new HtmlCommand('#linkit-editor-dialog-form', $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

}
