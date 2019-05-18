<?php

namespace Drupal\entity_embed_linkit;

use Drupal\Component\Serialization\Json;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\editor\EditorInterface;
use Drupal\linkit\AttributeCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Integrate the linkit module with entity embed dialog.
 */
class LinkitEntityEmbedDialog implements ContainerInjectionInterface {

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
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * Construct LinkitEntityEmbedDialog service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationManager $string_translation
   *   The translation manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, TranslationManager $string_translation) {
    $this->linkitProfileStorage = $entityTypeManager->getStorage('linkit_profile');
    $this->translationManager = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * Alter form to integrate with the linkit module.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\editor\EditorInterface $editor
   *   The editor.
   */
  public function alterForm(array &$form, FormStateInterface $form_state, EditorInterface $editor) {
    // The default values are set directly from \Drupal::request()->request,
    // provided by the editor plugin opening the dialog.
    $user_input = $form_state->getUserInput();
    if (!empty($user_input['editor_object']['data-entity-embed-display-settings'])) {
      $user_input['editor_object']['data-entity-embed-display-settings'] = Json::decode($user_input['editor_object']['data-entity-embed-display-settings']);

      if (isset($user_input['editor_object']['data-entity-embed-display-settings']['linkit_attributes'])) {
        $input = $user_input['editor_object']['data-entity-embed-display-settings']['linkit_attributes'];
      }
    }
    else {
      $input = [];
    }

    $linkit_profile_id = $editor->getSettings()['plugins']['linkit']['linkit_profile'];
    $this->linkitProfile = $this->linkitProfileStorage->load($linkit_profile_id);

    $form['link_url']['#type'] = 'linkit';
    $form['link_url']['#autocomplete_route_name'] = 'linkit.autocomplete';
    $form['link_url']['#autocomplete_route_parameters'] = [
      'linkit_profile_id' => $linkit_profile_id,
    ];

    $this->addAttributes($form, $form_state, $this->linkitProfile->getAttributes(), $input);

    $form['#element_validate'][] = [self::class, 'validateForm'];
  }

  /**
   * Validate the linkit attributes.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateForm(array &$form, FormStateInterface $form_state) {
    $parents = [
      'attributes',
      'data-entity-embed-display-settings',
      'linkit_attributes',
    ];
    if ($attributes = array_filter($form_state->getValue($parents))) {
      $form_state->setValue($parents, $attributes);
    }
  }

  /**
   * Adds the attributes enabled on the current profile.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\linkit\AttributeCollection $attributes
   *   A collection of attributes for the current profile.
   * @param array $input
   *   An array with the attribute values from the editor.
   */
  private function addAttributes(array &$form, FormStateInterface &$form_state, AttributeCollection $attributes, array $input) {
    if ($attributes->count()) {
      $form['linkit_attributes'] = [
        '#type' => 'container',
        '#title' => $this->translationManager->translate('Attributes'),
        '#weight' => '10',
      ];

      /** @var \Drupal\linkit\AttributeInterface $plugin */
      foreach ($attributes as $plugin) {
        $plugin_name = $plugin->getHtmlName();

        $default_value = isset($input[$plugin_name]) ? $input[$plugin_name] : '';
        $form['linkit_attributes'][$plugin_name] = $plugin->buildFormElement($default_value);
        $form['linkit_attributes'][$plugin_name]['#tree'] = TRUE;
      }
    }
  }

}
