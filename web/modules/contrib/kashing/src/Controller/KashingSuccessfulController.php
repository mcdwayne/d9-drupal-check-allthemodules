<?php

namespace Drupal\kashing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Successful payment page.
 */
class KashingSuccessfulController extends ControllerBase {

  private $config;
  protected $currentUser;

  /**
   * Cunstruct.
   */
  public function __construct($config, $currentUser) {
    $this->config = $config->get('kashing.settings');
    $this->currentUser = $currentUser;
  }

  /**
   * Success page element.
   *
   * @return array
   *   Success page content
   */
  public function page() {
    return [
      '#type' => 'markup',
      '#markup' => $this->successPage(),
    ];
  }

  /**
   * Generate a succes page HTML.
   */
  public function successPage() {

    $successPage = $this->config->get('successPage') ? $this->config->get('successPage') : '';

    $output = $successPage;

    $is_admin = $this->currentUser->hasRole('administrator');

    // Check if this is indeed a return from a gateway.
    if (isset($_GET['kTransactionID']) && $is_admin) {

      $text_info = $this->t('Kashing payment successful!');
      $text_details = $this->t('Transaction details');
      $text_id = $this->t('Transaction ID');
      $text_code = $this->t('Response Code');
      $text_reason = $this->t('Reason Code');
      $text_notice = $this->t('This notice is displayed to site administrators only.');

      // Display some extra information for an admin user.
      $output .= '<div class="kashing-frontend-notice kashing-success">';
      $output .= '<p><strong>' . $text_info . '</strong></p><p>' . $text_details . ':</p><ul>';
      $output .= '<li>' . $text_id . ': <strong>' . Html::escape($_GET['kTransactionID']) . '</strong></li>';
      if ($_GET['kResponse']) {
        $output .= '<li>' . $text_code . ': <strong>' . Html::escape($_GET['kResponse']) . '</strong></li>';
      }
      if ($_GET['kReason']) {
        $output .= '<li>' . $text_reason . ': <strong>' . Html::escape($_GET['kReason']) . '</strong></li>';
      }
      $output .= '</ul><p>' . $text_notice . '</p>';
      $output .= '</div>';

    }

    // Return the shortcode content.
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
