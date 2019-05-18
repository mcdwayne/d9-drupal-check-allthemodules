<?php

namespace Drupal\just_giving\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\just_giving\JustGivingAccount;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AccountController.
 */
class JustGivingAccountController extends ControllerBase {

  /**
   * Drupal\just_giving\JustGivingClient definition.
   *
   * @var \Drupal\just_giving\JustGivingClient
   */
  protected $justGivingAccount;

  /**
   * Constructs a new AccountController object.
   */
  public function __construct(JustGivingAccount $just_giving_account) {
    $this->justGivingAccount = $just_giving_account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('just_giving.account')
    );
  }

  /**
   * Hello.
   *
   * @param $name
   *
   * @return array Return Hello string.*   Return Hello string.
   */
  public function accountCreate($name) {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: hello with parameter(s): $name'),
    ];
  }

}
