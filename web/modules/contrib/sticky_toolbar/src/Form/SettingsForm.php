<?php

namespace Drupal\sticky_toolbar\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Asset\CssCollectionOptimizer;
use Drupal\Core\Asset\JsCollectionOptimizer;
use Drupal\Core\Messenger\Messenger;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures Sticky Toolbar settings for this user.
 */
class SettingsForm extends FormBase {

  /**
   * Message type default.
   *
   * Can be status/warning/error.
   */
  const DISPLAY_MESSAGE_TYPE_DEFAULT = 'status';

  /**
   * Current user's data.
   *
   * @var Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Current user.
   *
   * @var Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Cached css.
   *
   * @var Drupal\Core\Asset\CssCollectionOptimizer
   */
  protected $cachedCss;

  /**
   * Cached js.
   *
   * @var Drupal\Core\Asset\JsCollectionOptimizer
   */
  protected $cachedJs;

  /**
   * Messenger.
   *
   * @var Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('user.data'),
      $container->get('current_user'),
      $container->get('asset.css.collection_optimizer'),
      $container->get('asset.js.collection_optimizer'),
      $container->get('messenger')
    );
  }

  /**
   * Constructs the SettingsForm.
   *
   * @param Drupal\user\UserDataInterface $userData
   *   User data.
   * @param Drupal\Core\Session\AccountInterface $account
   *   Current account.
   * @param Drupal\Core\Asset\CssCollectionOptimizer $cachedCss
   *   Cached css files.
   * @param Drupal\Core\Asset\JsCollectionOptimizer $cachedJs
   *   Cached js files.
   * @param Drupal\Core\Messenger\Messenger $messenger
   *   Messenger.
   */
  public function __construct(UserDataInterface $userData, AccountInterface $account, CssCollectionOptimizer $cachedCss, JsCollectionOptimizer $cachedJs, Messenger $messenger) {
    $this->userData = $userData;
    $this->user = $account;
    $this->cachedCss = $cachedCss;
    $this->cachedJs = $cachedJs;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sticky_toolbar_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $sticky = $this->getSetting();

    $form['is_sticky'] = [
      '#type' => 'checkbox',
      '#title' => 'Make toolbar sticky',
      '#default_value' => $sticky,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit_button',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Add error handling.
   */
  public function submitForm(array &$form, FormStateInterface $form_state, $displayMessageType = self::DISPLAY_MESSAGE_TYPE_DEFAULT) {
    $sticky = $form_state->getValue('is_sticky');

    if (is_int($sticky)) {
      $this->setSetting($sticky);
    }

    $form_state->setRedirect('sticky_toolbar.admin_settings');
    $message = 'Your toolbar settings have been updated.';
    $this->messenger->addMessage($this->t($message), $displayMessageType);
  }

  /**
   * Gets the sticky setting.
   *
   * @return int
   *   The integer determining the sticky setting.
   */
  protected function getSetting() {
    $userSettingData = $this->userData->get('sticky_toolbar', $this->user->id(), 'sticky');
    $setting = 1;

    if ($userSettingData !== NULL) {
      $setting = $userSettingData;
    }

    return $setting;
  }

  /**
   * Sets the sticky setting.
   *
   * @param int $setting
   *   The integer determining the sticky setting.
   */
  protected function setSetting($setting) {
    $this->userData->set('sticky_toolbar', $this->user->id(), 'sticky', $setting);

    // Flush asset file caches.
    $this->cachedCss->deleteAll();
    $this->cachedJs->deleteAll();
    _drupal_flush_css_js();
  }

}
