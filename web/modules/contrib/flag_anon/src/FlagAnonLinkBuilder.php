<?php

namespace Drupal\flag_anon;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagLinkBuilder;
use Drupal\flag\FlagServiceInterface;

/**
 * Provides a lazy builder for flag links.
 */
class FlagAnonLinkBuilder extends FlagLinkBuilder {

  /**
   * URL $_GET parameter name.
   *
   * @var string
   */
  public static $flagGetParam = 'flag_anon';

  /**
   * URL $_GET parameter data delimiter.
   *
   * @var string
   */
  public static $flagGetParamDelimiter = '-';

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current active user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FlagServiceInterface $flag_service, AccountProxyInterface $current_user, ModuleHandlerInterface $module_handler) {
    parent::__construct($entity_type_manager, $flag_service);
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function build($entity_type_id, $entity_id, $flag_id) {
    $flag = $this->flagService->getFlagById($flag_id);

    if ($flag->getThirdPartySetting('flag_anon', 'enabled', 0) && $this->currentUser->isAnonymous()) {

      $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
      $action = $flag->isFlagged($entity) ? 'unflag' : 'flag';
      $access = $flag->actionAccess($action, $this->currentUser, $entity);

      if (!$access->isAllowed()) {
        return $this->buildAnonMessage($flag, $entity, $action);
      }
    }

    return parent::build($entity_type_id, $entity_id, $flag_id);
  }

  /**
   * Build message for anonymous user.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The Flag entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flaggable entity.
   * @param string $action
   *   The action name.
   *
   * @return array
   *   Renter array for anonymous message.
   */
  protected function buildAnonMessage(FlagInterface $flag, EntityInterface $entity, $action) {

    $build = [
      '#theme' => 'flag_anon_message',
      '#label' => '',
      '#message' => '',
      '#attributes' => new Attribute([
        'class' => [
          'flag',
          'flag-anon-message',
          Html::cleanCssIdentifier('flag-' . $flag->id()),
          Html::cleanCssIdentifier('js-flag-' . $flag->id() . '-' . $entity->id()),
          $action === 'unflag' ? 'action-unflag' : 'action-flag',
        ],
      ]),
      '#label_attributes' => new Attribute(['class' => ['label']]),
      '#cache' => [
        'tags' => $flag->getCacheTags(),
        'max-age' => Cache::PERMANENT,
        'contexts' => [
          'user.roles:anonymous',
          'languages:language_interface',
        ],
      ],
    ];

    if ($flag->getThirdPartySetting('flag_anon', 'popup')) {
      $build['#attached']['library'][] = 'core/drupal.dialog.ajax';
    }

    $placeholders = [
      '@register' => $this->getRegisterLink($flag, $entity),
      '@login' => $this->getLoginLink($flag, $entity),
    ];
    $this->moduleHandler->alter('flag_anon_message_placeholders', $placeholders, $flag, $entity);
    $message = new FormattableMarkup($flag->getThirdPartySetting('flag_anon', 'message'), $placeholders);

    switch ($flag->getThirdPartySetting('flag_anon', 'label_display')) {

      case 'original':
        $selector = Html::cleanCssIdentifier('flag-anon-' . $flag->id() . '-' . $entity->id());
        $build['#label'] = $flag->getShortText($action);
        $build['#label_attributes']->setAttribute('data-selector', '.' . $selector);
        $build['#message'] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $message,
          '#attributes' => [
            'title' => $flag->getThirdPartySetting('flag_anon', 'popin_title'),
            'class' => ['flag-anon-message', $selector],
            'style' => 'display:none',
          ],
        ];
        $build['#attached']['library'][] = 'flag_anon/message';
        break;

      default:
        $build['#label'] = $message;
        break;
    }

    return $build;
  }

  /**
   * Generate login link.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The Flag entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flaggable entity.
   *
   * @return \Drupal\Core\GeneratedLink
   *   The link HTML markup.
   */
  protected function getLoginLink(FlagInterface $flag, EntityInterface $entity) {
    $url = Url::fromRoute('user.login');
    $this->setUrlRouteParams($url, $flag, $entity);

    if ($flag->getThirdPartySetting('flag_anon', 'popup')) {
      $url->setOptions([
        'attributes' => [
          'class' => ['use-ajax', 'login-popup-form'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => $flag->getThirdPartySetting('flag_anon', 'popup_login'),
        ],
      ]);
    }

    return Link::fromTextAndUrl(
      $flag->getThirdPartySetting('flag_anon', 'login_label'), $url
    )->toString();
  }

  /**
   * Generate register link.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The Flag entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flaggable entity.
   *
   * @return \Drupal\Core\GeneratedLink
   *   The link HTML markup.
   */
  protected function getRegisterLink(FlagInterface $flag, EntityInterface $entity) {
    $url = Url::fromRoute('user.register');
    $this->setUrlRouteParams($url, $flag, $entity);

    if ($flag->getThirdPartySetting('flag_anon', 'popup')) {
      $url->setOptions([
        'attributes' => [
          'class' => ['use-ajax', 'register-popup-form'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => $flag->getThirdPartySetting('flag_anon', 'popup_register'),
        ],
      ]);
    }

    return Link::fromTextAndUrl(
      $flag->getThirdPartySetting('flag_anon', 'register_label'), $url
    )->toString();
  }

  /**
   * Set Url route parameters.
   *
   * @param \Drupal\Core\Url $url
   *   The Url to interact with.
   * @param \Drupal\flag\FlagInterface $flag
   *   The Flag entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flaggable entity.
   */
  protected function setUrlRouteParams(Url $url, FlagInterface $flag, EntityInterface $entity) {
    $params = $url->getRouteParameters();
    $params[self::$flagGetParam] = implode(self::$flagGetParamDelimiter, [$flag->id(), $entity->id()]);
    $params['destination'] = Url::fromRoute('<current>')->toString();

    $url->setRouteParameters($params);
  }

}
