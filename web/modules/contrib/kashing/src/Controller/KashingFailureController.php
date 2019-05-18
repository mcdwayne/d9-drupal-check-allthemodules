<?php

namespace Drupal\kashing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Html;
use Drupal\kashing\Entity\KashingAPI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Failure page class.
 */
class KashingFailureController extends ControllerBase {

  private $config;
  protected $currentUser;

  /**
   * Construct function.
   */
  public function __construct($config, $currentUser) {
    $this->config = $config->get('kashing.settings');
    $this->currentUser = $currentUser;
  }

  /**
   * Page rendering function.
   *
   * @return array
   *   Page content
   */
  public function page() {
    return [
      '#type' => 'markup',
      '#markup' => $this->failurePage(),
    ];
  }

  /**
   * Page html string.
   */
  public function failurePage() {

    $failure_page = $this->config->get('failure_page') ? $this->config->get('failure_page') : '';

    $output = $failure_page;

    $is_admin = $this->currentUser->hasRole('administrator');

    // Proceed only if parameter is available in $_GET.
    if (isset($_GET['kTransactionID'])) {

      $transaction_id = $_GET['kTransactionID'];
      $kashing_api = new KashingAPI();

      $transaction_details = $kashing_api->apiGetTransactionErrorDetails($transaction_id);

      // Regular user output.
      if (isset($transaction_details['gatewaymessage'])) {
        $extra_class = (isset($transaction_details['nogateway'])) ? ' no-gateway-message' : '';
        $output = '<div class="kashing-transaction-details kashing-gateway-message' . $extra_class . '"><p>';
        $output .= Html::escape($transaction_details['gatewaymessage']);
        $output .= '</p></div>';
      }

      $text_info = $this->t('Kashing payment failed.');
      $text_details = $this->t('Transaction details');
      $text_id = $this->t('Transaction ID');
      $text_response = $this->t('Response Code');
      $text_reason = $this->t('Reason Code');
      $text_gateway = $this->t('Gateway Message');
      $text_notice = $this->t('This notice is displayed to site administrators only.');

      // Extra details for admin users.
      if ($is_admin) {
        $output .= '<div class="kashing-frontend-notice kashing-errors">';
        $output .= '<p><strong>' . $text_info . '</strong></p><p>' . $text_details . ':</p><ul>';
        $output .= '<li>' . $text_id . ': <strong>' . Html::escape($_GET['kTransactionID']) . '</strong></li>';
        if ($_GET['kResponse']) {
          $output .= '<li>' . $text_response . ': <strong>' . Html::escape($_GET['kResponse']) . '</strong></li>';
        }
        if ($_GET['kReason']) {
          $output .= '<li>' . $text_reason . ': <strong>' . Html::escape($_GET['kReason']) . '</strong></li>';
        }
        if (isset($transaction_details['gatewaymessage'])) {
          $output .= '<li>' . $text_gateway . ': <strong>' . Html::escape($transaction_details['gatewaymessage']) . '</strong></li>';
        }
        $output .= '</ul><p>' . $text_notice . '</p>';
        $output .= '</div>';
      }
    }
    elseif (isset($_GET['kError'])) {
      if ($is_admin) {

        $text_info = $this->t('Transaction details');
        $text_failed = $this->t('Kashing payment failed.');
        $text_response = $this->t('Response Code');
        $text_reason = $this->t('Reason Code');
        $text_error = $this->t('Error');
        $text_notice = $this->t('This notice is displayed to site administrators only.');

        $output .= '<div class="kashing-frontend-notice kashing-errors">';
        $output .= '<p><strong>' . $text_failed . '</strong></p><p>' . $text_info . ':</p><ul>';
        if ($_GET['kResponse']) {
          $output .= '<li>' . $text_response . ': <strong>' . Html::escape($_GET['kResponse']) . '</strong></li>';
        }
        if ($_GET['kReason']) {
          $output .= '<li>' . $text_reason . ': <strong>' . Html::escape($_GET['kReason']) . '</strong></li>';
        }
        if (isset($_GET['kError'])) {
          $output .= '<li>' . $text_error . ': <strong>' . Html::escape($_GET['kError']) . '</strong></li>';
        }
        $output .= '</ul><p>' . $text_notice . '</p>';
        $output .= '</div>';
      }
    }

    return $output;
  }

  /**
   * Create function.
   */
  public static function create(ContainerInterface $container) {

    $uid = $container->get('current_user')->id();

    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')->getStorage('user')->load($uid)
    );

  }

}
