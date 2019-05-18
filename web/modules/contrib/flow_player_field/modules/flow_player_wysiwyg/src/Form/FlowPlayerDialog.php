<?php

namespace Drupal\flow_player_wysiwyg\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\filter\Entity\FilterFormat;
use Drupal\flow_player_field\ProviderManager;
use Drupal\flow_player_wysiwyg\Ajax\FlowPlayerCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A class for a Flow Player dialog.
 */
class FlowPlayerDialog extends FormBase {

  /**
   * The video provider manager.
   *
   * @var \Drupal\flow_player_field\ProviderManager
   */
  protected $providerManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * FlowPlayerDialog constructor.
   *
   * @param \Drupal\flow_player_field\ProviderManager $provider_manager
   *   The video provider plugin manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(ProviderManager $provider_manager, RendererInterface $renderer) {
    $this->providerManager = $provider_manager;
    $this->render = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('flow_player_field.provider_manager'), $container->get('renderer'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {
    // Add AJAX support.
    $form['#prefix'] = '<div id="flow-player-dialog-form">';
    $form['#suffix'] = '</div>';
    // Ensure relevant dialog libraries are attached.
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#attached']['library'][] = 'flow_player_wysiwyg/flow_player_dialog';

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Insert video'),
      '#submit' => [],
      '#ajax' => [
        'callback' => '::insertVideo',
        'event' => 'click',
        'wrapper' => 'flow-player-dialog-form',
      ],
    ];

    $form['video_id'] = [
      '#attributes' => ['id' => 'video_id'],
      '#type' => 'hidden',
    ];

    $form['player_id'] = [
      '#attributes' => ['id' => 'player_id'],
      '#type' => 'hidden',
    ];

    $form['video_object'] = [
      '#attributes' => ['id' => 'video_object'],
      '#type' => 'hidden',
    ];
    return $form;
  }

  /**
   * Get a value from the widget in the WYSIWYG.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state to extract values from.
   * @param string $key
   *   The key to get from the selected WYSIWYG element.
   *
   * @return string
   *   The default value.
   */
  protected function getUserInput(FormStateInterface $form_state, $key) {
    return isset($form_state->getUserInput()['editor_object'][$key]) ? $form_state->getUserInput()['editor_object'][$key] : '';
  }

  /**
   * Get the values from the submited form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The values from the submited form.
   *
   * @return array
   *   This will render the video in CKEditor.
   */
  protected function getValues(FormStateInterface $form_state) {
    $videoObject = json_decode($form_state->getValue('video_object'), TRUE);
    $videoId = $form_state->getValue('video_id');
    $playerId = $form_state->getValue('player_id');

    return [
      'preview_thumbnail' => $videoObject['images']['thumbnail_url'],
      'video_id' => $videoId,
      'player_id' => $playerId,
      'provider' => 'flowplayer',
      'video' => $videoObject['name'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('video_object') == '') {
      $form_state->setError($form['video_object'], $this->t('Please select a video.'));
      return;
    }
  }

  /**
   * An AJAX submit callback to validate the WYSIWYG modal.
   */
  public function insertVideo(array &$form, FormStateInterface $form_state) {

    $response = new AjaxResponse();
    if (!$form_state->getErrors()) {
      $response->addCommand(new EditorDialogSave($this->getValues($form_state)));
      $response->addCommand(new FlowPlayerCommand('insert'));
      $response->addCommand(new CloseModalDialogCommand());
    }
    else {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];

      $response->addCommand(new FlowPlayerCommand('error'));
      $response->addCommand(new HtmlCommand(NULL, $form));
    }
    return $response;
  }

  /**
   * Get a provider from some input.
   *
   * @param string $input
   *   The input string.
   *
   * @return bool|\Drupal\flow_player_field\ProviderPluginInterface
   *   A video provider or FALSE on failure.
   */
  protected function getProvider($input) {
    return $this->providerManager->loadProviderFromInput($input);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The AJAX commands were already added in the AJAX callback. Do nothing in
    // the submit form.
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flow_player_dialog';
  }

}
