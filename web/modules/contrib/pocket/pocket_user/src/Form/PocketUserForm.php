<?php

namespace Drupal\pocket_user\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\pocket\AccessToken;
use Drupal\pocket\Client\PocketClientFactoryInterface;
use Drupal\pocket_user\PocketUserManager;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PocketUserForm extends FormBase {

  /**
   * @var \Drupal\pocket\Client\PocketClientFactoryInterface
   */
  protected $clientFactory;

  /**
   * @var \Drupal\pocket_user\PocketUserManager
   */
  protected $manager;

  /**
   * PocketUserForm constructor.
   *
   * @param \Drupal\pocket\Client\PocketClientFactoryInterface $clientFactory
   * @param \Drupal\pocket_user\PocketUserManager              $manager
   */
  public function __construct(
    PocketClientFactoryInterface $clientFactory,
    PocketUserManager $manager
  ) {
    $this->clientFactory = $clientFactory;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('pocket.client'),
      $container->get('pocket_user.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'user_pocket_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    UserInterface $user = NULL
  ): array {
    \assert($user instanceof UserInterface);
    $this->buildConnectionForm($form, $user);
    return $form;
  }

  private function buildConnectionForm(array &$form, UserInterface $user) {
    $form['#user'] = $user;
    if ($access = $this->manager->getUserAccess($user->id())) {
      $form['access']['#markup'] = $this->t(
        'You have connected the Pocket account %name.',
        ['%name' => $access->getUsername()]
      );
      $form['disconnect'] = [
        '#type'        => 'submit',
        '#submit'      => ['::disconnect'],
        '#value'       => $this->t('Disconnect'),
        '#description' => $this->t(
          'You may also want to revoke access in your <a href=":url">Pocket settings</a>.',
          [
            ':url' => 'https://getpocket.com/connected_applications',
          ]
        ),
      ];
    }
    elseif ($this->clientFactory->hasKey()) {
      $form['connect'] = [
        '#type'   => 'submit',
        '#submit' => ['::connect'],
        '#value'  => $this->t('Connect to Pocket'),
      ];
    }
    else {
      $form['error']['#markup'] = $this->t(
        'This site does not have an API key for Pocket yet.'
      );
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Connect a Pocket account.
   *
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function connect(array &$form, FormStateInterface $form_state) {
    /** @var UserInterface $user */
    $user = $form['#user'];

    $client = $this->clientFactory->getAuthClient();
    try {
      $url = $client->authorize([$this, 'callback'], ['user' => $user->id()]);
      $form_state->setResponse(new TrustedRedirectResponse($url->toString()));
    } catch (\Exception $exception) {
      drupal_set_message($this->t('Failed to connect account: %message', [
        '%message' => $exception->getMessage(),
      ]));
    } catch (GuzzleException $exception) {
      drupal_set_message($this->t('Failed to connect account: %message', [
        '%message' => $exception->getMessage(),
      ]));
    }
  }

  public function disconnect(array &$form) {
    $user = $form['#user'];
    \assert($user instanceof UserInterface);

    if ($this->manager->deleteUserAccess($user->id())) {
      drupal_set_message(
        $this->t('You have disconnected your Pocket account.')
      );
    }
  }

  /**
   * Handle the callback and redirect back to the form.
   *
   * @param \Drupal\pocket\AccessToken $token
   *
   * @return \Drupal\Core\Url
   */
  public function callback(AccessToken $token): Url {
    $user = User::load($token->getState()['user']);
    \assert($user instanceof UserInterface);
    $this->manager->setUserAccess($user->id(), $token);
    drupal_set_message(
      $this->t(
        'You have connected your Pocket account "%user".',
        [
          '%user' => $token->getUsername(),
        ]
      )
    );
    return Url::fromRoute('pocket_user.form', ['user' => $user->id()]);
  }

}
