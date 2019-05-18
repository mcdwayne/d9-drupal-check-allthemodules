<?php

namespace Drupal\just_giving;
use Drupal\just_giving\JustGivingAccount;
use Drupal\just_giving\justGivingPage;

/**
 * Class PageCreate.
 */
class JustGivingRequest implements JustGivingRequestInterface {

  /**
   * Drupal\just_giving\JustGivingAccount definition.
   *
   * @var \Drupal\just_giving\JustGivingAccount
   */
  protected $justGivingAccount;
  /**
   * Drupal\just_giving\justGivingPage definition.
   *
   * @var \Drupal\just_giving\justGivingPage
   */
  protected $justGivingPage;

  protected $userAccount;


  /**
   * Constructs a new PageCreate object.
   */
  public function __construct(JustGivingAccountInterface $just_giving_account, justGivingPageInterface $just_giving_page) {
    $this->justGivingAccount = $just_giving_account;
    $this->justGivingPage = $just_giving_page;
  }


  /**
   * @param $form
   * @param $form_state
   *
   * @return mixed|string
   */
  public function createFundraisingPage($form_state) {

    $checkExists = $this->justGivingAccount->checkAccountExists($form_state->getValue('email'));

    $userInfo = [
      'username' => $form_state->getValue('email'),
      'password' => $form_state->getValue('password'),
    ];

    if ($checkExists) {
      $this->userAccount = $this->justGivingAccount->retrieveAccount(
        $form_state->getValue('email'),
        $form_state->getValue('password')
      );
      $userInfo = $userInfo + [
        'first_name' => $this->userAccount->firstName,
        'last_name' => $this->userAccount->lastName,
      ];
    }
    elseif (!$checkExists) {
      $this->userAccount = $this->justGivingAccount->signupUser($form_state);
      $userInfo = $userInfo + [
        'first_name' => $form_state->getValue('first_name'),
        'last_name' => $form_state->getValue('last_name'),
      ];
    }

    $node = \Drupal::routeMatch()->getParameter('node');
    $this->justGivingPage->setPageInfo($node);
    $this->justGivingPage->setUserInfo($userInfo);

    $registerPage = $this->justGivingPage->registerFundraisingPage();

    return $registerPage;
  }

}
