<?php

namespace Drupal\coupon_for_role\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\coupon_for_role\CouponForRoleCouponManager;
use Drupal\coupon_for_role\Exception\CouponAlreadyUsedException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Coupon for role form.
 */
class RedeemForm extends FormBase {

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Coupon manager.
   *
   * @var \Drupal\coupon_for_role\CouponForRoleCouponManager
   */
  protected $couponManager;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a RedeemForm object.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory, CouponForRoleCouponManager $coupon_manager, AccountProxyInterface $current_user) {
    $this->loggerFactory = $logger_factory;
    $this->couponManager = $coupon_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory'),
      $container->get('coupon_for.role.coupon_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'coupon_for_role_redeem';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['coupon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Coupon code'),
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Redeem'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $code_data = $this->couponManager->getCodeDataBycode($form_state->getValue('coupon'));
    if (!$code_data) {
      $form_state->setError($form['coupon'], $this->t('You have entered an invalid code'));
    }
    if (!$code_data['status']) {
      $form_state->setError($form['coupon'], $this->t('This coupon code is already used'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $code = $form_state->getValue('coupon');
    $this->loggerFactory->get('coupon_for_role')->info('Coupon @code will be redeemed', [
      '@code' => $code,
    ]);
    try {
      $this->couponManager->redeemCoupon($code, $this->currentUser);
      $form_state->setRedirect('user.page');
    }
    catch (CouponAlreadyUsedException $e) {
      // Should really not be possible, since we check this in the validation.
      drupal_set_message(t('The code you have entered has already been used.'));
      watchdog_exception('coupon_for_role', $e);
    }
    catch (\Exception $e) {
      watchdog_exception('coupon_for_role', $e);
      drupal_set_message(t('There was an error redeeming the code, even if the code was valid. If the problem persist, please contact the site administrator'));
    }
  }

}
