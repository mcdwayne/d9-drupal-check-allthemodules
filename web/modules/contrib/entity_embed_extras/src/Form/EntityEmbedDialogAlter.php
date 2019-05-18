<?php

namespace Drupal\entity_embed_extras\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\entity_embed_extras\DialogEntityDisplay\DialogEntityDisplayManager;

/**
 * Dialog customizations.
 */
class EntityEmbedDialogAlter implements ContainerInjectionInterface {

  /**
   * The Dialog Entity Display Plugin manager.
   *
   * @var \Drupal\entity_embed\DialogEntityDisplay\DialogEntityDisplayManager
   */
  protected $DialogEntityDisplayManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_embed_extras.dialog_entity_display')
    );
  }

  /**
   * Constructs a EntityEmbedDialogAlter object.
   *
   * @param \Drupal\entity_embed\DialogEntityDisplay\DialogEntityDisplayManager $dialog_entity_display_manager
   *   The Dialog Entity Display Plugin manager.
   */
  public function __construct(DialogEntityDisplayManager $dialog_entity_display_manager) {
    $this->DialogEntityDisplayManager = $dialog_entity_display_manager;
  }

  /**
   * Alter the EntityEmbedDialog form for gallery button.
   */
  public function formAlter(array &$form, FormStateInterface $form_state) {

    /** @var \Drupal\embed\Entity\EmbedButton $embedButton */
    $embedButton = $form_state->get('embed_button');

    // If an entity exists, add an edit link that opens in a new tab.
    $entity = $form_state->get('entity');

    if ($form_state->get('step') == 'select') {
      $title = $embedButton->getThirdPartySetting('entity_embed_extras', 'select_step_title', 'Select entity to embed');
      $form['#title'] = new TranslatableMarkup($title);

    }
    elseif (!empty($entity) && $form_state->get('step') == 'embed') {
      $title = $embedButton->getThirdPartySetting('entity_embed_extras', 'embed_step_title', 'Edit entity embed');
      $form['#title'] = new TranslatableMarkup($title);
      $backButtonTitle = $embedButton->getThirdPartySetting('entity_embed_extras', 'embed_back_title', 'Select a different entity');
      $form['actions']['back']['#value'] = new TranslatableMarkup($backButtonTitle);

      $pluginId = $embedButton->getThirdPartySetting('entity_embed_extras', 'dialog_entity_display', 'label');
      $settings = $embedButton->getThirdPartySetting('entity_embed_extras', 'dialog_entity_display_settings', []);

      /** @var \Drupal\entity_embed\DialogEntityDisplay\DialogEntityDisplayInterface $plugin */
      $plugin = $this->DialogEntityDisplayManager
        ->createInstance($pluginId, $settings);

      $form['entity'] = $plugin->getFormElement($entity, $form, $form_state);
    }
  }

}
