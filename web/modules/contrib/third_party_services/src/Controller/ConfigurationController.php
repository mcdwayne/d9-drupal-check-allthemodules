<?php

namespace Drupal\third_party_services\Controller;

use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Drupal\third_party_services\MediatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Render the "ConfigurationForm" as page and a modal window.
 */
class ConfigurationController extends ControllerBase {

  /**
   * Instance of the "MODULE.mediator" service.
   *
   * @var \Drupal\third_party_services\MediatorInterface
   */
  protected $mediator;

  /**
   * ConfigurationController constructor.
   *
   * @param \Drupal\third_party_services\MediatorInterface $mediator
   *   Instance of the "MODULE.mediator" service.
   */
  public function __construct(MediatorInterface $mediator) {
    $this->mediator = $mediator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static($container->get('third_party_services.mediator'));
  }

  /**
   * Checks whether user is allowed to manage the configuration.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   Requested user.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access result.
   */
  public function access(AccountInterface $account, AccountInterface $user): AccessResult {
    // Bypass access for super-admin and allow users to modify own settings.
    return AccessResult::allowedIf(in_array($account->id(), [1, $user->id()]));
  }

  /**
   * Controller for "/user/{user}/third-party-services".
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   HTTP request.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   An account of user for personalizing configuration.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   Render-like array or AJAX response.
   */
  public function form(Request $request, AccountInterface $user) {
    $form = $this->mediator->getConfigurationForm($user);

    if ($request->request->get(AjaxResponseSubscriber::AJAX_REQUEST_PARAMETER)) {
      $dialog_options = (array) $request->query->get('dialog_options');
      $dialog_options['title'] = $dialog_options['title'] ?? $this->t('Services');
      $dialog_options['dialogClass'] = $dialog_options['dialogClass'] ?? [];
      $dialog_options['dialogClass'][] = 'third-party-services--configuration-form--modal-window';

      if (!empty($dialog_options['addCancelButton'])) {
        $form['actions']['cancel'] = [
          '#url' => Url::fromRoute('<front>'),
          '#type' => 'link',
          '#title' => $this->t('Cancel'),
          '#attributes' => [
            'class' => ['button', 'button-cancel'],
          ],
        ];
      }

      // Ensure that necessary library will be loaded on the page.
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

      $response = new AjaxResponse();
      $response->addCommand(new OpenModalDialogCommand($dialog_options['title'], $form, $dialog_options));

      return $response;
    }

    return $form;
  }

}
