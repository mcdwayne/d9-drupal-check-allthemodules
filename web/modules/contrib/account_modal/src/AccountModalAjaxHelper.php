<?php

namespace Drupal\account_modal;

use Drupal\account_modal\AjaxCommand\RefreshPageCommand;
use Drupal\block\Entity\Block;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * A helper class for creating Ajax responses for Account Modal.
 */
class AccountModalAjaxHelper {

  /**
   * @param $pageId
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function ajaxCallback($pageId, array $form, FormStateInterface $formState) {
    $response = new AjaxResponse();
    $messages = \Drupal::messenger()->all();

    if (!isset($messages['error'])) {
      $response->addCommand(new CloseModalDialogCommand());

      if ($pageId === 'login') {
        \Drupal::messenger()->addMessage('You have been successfully logged in. Please wait a moment.');
        $response->addCommand(self::redirectCommand($formState));
      }
      elseif ($pageId === 'register') {
        \Drupal::messenger()->addMessage('You have successfully created an account. Please wait a moment.');

        /** @var \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler */
        $moduleHandler = \Drupal::service('module_handler');
        $profileIsInstalled = $moduleHandler->moduleExists('profile');
        $config = \Drupal::config('account_modal.settings');

        $command = ($config->get('create_profile_after_registration') && $profileIsInstalled)
          ? self::newProfileCommand($formState)
          : self::redirectCommand($formState);
        $response->addCommand($command);
      }
    }

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $messagesElement = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'account-modal-messages',
      ],
      'messages' => ['#type' => 'status_messages'],
    ];

    $response->addCommand(new RemoveCommand('.account-modal-messages'));
    $response->addCommand(new AppendCommand(
      '#account_modal_' . $pageId . '_wrapper',
      $renderer->renderRoot($messagesElement)
    ));

    return $response;
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *
   * @return \Drupal\account_modal\AjaxCommand\RefreshPageCommand|\Drupal\Core\Ajax\RedirectCommand
   */
  public static function redirectCommand(FormStateInterface $formState) {
    global $base_url;

    $config = \Drupal::config('account_modal.settings');

    return $config->get('reload_on_success')
      ? new RefreshPageCommand()
      : new RedirectCommand($base_url . $formState->getRedirect());
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *
   * @return \Drupal\user\UserInterface|null
   */
  public static function getUidFromFormState(FormStateInterface $formState) {
    $values = &$formState->getValues();
    $uid = NULL;

    if (isset($values['uid'])) {
      $uid = $values['uid'];
    }

    return $uid;
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *
   * @return \Drupal\Core\Ajax\OpenModalDialogCommand
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function newProfileCommand(FormStateInterface $formState) {
    $config = \Drupal::config('account_modal.settings');
    $uid = self::getUidFromFormState($formState);
    $profile = \Drupal::entityTypeManager()->getStorage('profile')->create([
      'uid' => $uid,
      'type' => $config->get('profile_type') ?: 'customer',
      'is_default' => TRUE,
    ]);

    /** @var \Drupal\Core\Entity\EntityFormBuilderInterface $entityFormBuilder */
    $entityFormBuilder = \Drupal::service('entity.form_builder');
    $form = $entityFormBuilder->getForm($profile, 'add', [
      'uid' => $uid,
      'created' => \Drupal::time()->getRequestTime(),
    ]);

    return new OpenModalDialogCommand('Create a Profile', $form);
  }

  /**
   * @param array $form
   */
  public static function hideFieldDescriptions(array &$form) {
    foreach ($form as $key => $element) {
      if (!is_array($element) || strpos($key, '#') === 0) {
        continue;
      }

      unset($form[$key]['#description']);
      self::hideFieldDescriptions($form[$key]);
    }
  }

  /**
   * @param array $form
   *
   * @throws \Exception
   */
  public static function injectBlocks(array &$form) {
    $header_blocks = self::getBlocks('header_blocks');

    if (!empty($header_blocks)) {
      $form['header_blocks'] = [
        '#type' => 'container',
        '#weight' => -100,
        '#attributes' => [
          'class' => ['account-modal-header'],
        ],
      ];

      $form['header_blocks'] += self::renderBlocks($header_blocks);
    }

    $footer_blocks = self::getBlocks('footer_blocks');

    if (!empty($footer_blocks)) {
      $form['footer_blocks'] = [
        '#type' => 'container',
        '#weight' => 200,
        '#attributes' => [
          'class' => ['account-modal-footer'],
        ],
      ];

      $form['footer_blocks'] += self::renderBlocks($footer_blocks);
    }
  }

  /**
   * @param array $blocks
   *
   * @return array
   * @throws \Exception
   */
  public static function renderBlocks(array $blocks) {
    $out = [];
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('block');

    foreach ($blocks as $id) {
      $id = trim($id);

      if (empty($id)) {
        continue;
      }

      /** @var \Drupal\Core\Render\RendererInterface $renderer */
      $renderer = \Drupal::service('renderer');
      $block = Block::load($id);
      $blockView = $view_builder->view($block);

      $out[$id] = [
        '#markup' => $renderer->render($blockView),
      ];
    }

    return $out;
  }

  /**
   * @param $key
   *
   * @return array|array[]|false|mixed|null|string[]
   */
  public static function getBlocks($key) {
    $config = \Drupal::config('account_modal.settings');
    $blocks = $config->get($key);
    return preg_split("/\r\n|\n|\r/", $blocks);
  }

}
