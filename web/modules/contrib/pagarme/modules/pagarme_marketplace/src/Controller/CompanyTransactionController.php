<?php
namespace Drupal\pagarme_marketplace\Controller;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;
use Drupal\pagarme\Pagarme\PagarmeSdk;
use Drupal\pagarme\Helpers\PagarmeUtility;
use Drupal\pagarme_marketplace\Helpers\PagarmeMarketplaceUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
/**
 * Class CompanyTransactionController.
 *
 * @package Drupal\pagarme_marketplace\Controller
 */
class CompanyTransactionController extends ControllerBase {
  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;
  /**
   * Drupal Routing Match.
   *
   * @var Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $route_match;
  /**
   * Symfony\Component\HttpFoundation\RequestStack.
   *
   * @var object
   */
  protected $request;
  /**
   * The entity manager.
   *
   * @var Drupal\Core\Datetime\DateFormatter
   */
  protected $date_formatter;
  /**
   * The entity manager.
   *
   * @var Drupal\Core\Datetime\DateFormatter
   */
  protected $current_user;
  /**
   * CompanyDetailController constructor.
   *
   * @param Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The Drupal Core Route Match Class.
   */
  public function __construct(
    Connection $database, 
    CurrentRouteMatch $route_match, 
    RequestStack $request, 
    DateFormatter $date_formatter, 
    AccountProxy $current_user
  ) {
    $this->database = $database;
    $this->route_match = $route_match;
    $this->request = $request;
    $this->date_formatter = $date_formatter;
    $this->current_user = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('date.formatter'),
      $container->get('current_user')
    );
  }
  /**
   * Public Render Method detailRender.
   *
   * @return Return an array for markup render. Example: ['#markup' => $yourMarkup]
   */
  public function renderCompanyTransaction() {
    $current_request = $this->request->getCurrentRequest();
    $current_path = parse_url($current_request->getRequestUri());
    $parameters = $current_request->query->all();
    $destination = $this->getDestinationArray();
    $page = (!empty($parameters['page'])) ? $parameters['page'] : 1;
    $per_page = 10;
    $pagarme_sdk = new PagarmeSdk($this->route_match->getParameter('company'));
    $transactions = $pagarme_sdk->pagarme->transaction()->getList($page, $per_page);
    $header = [
      $this->t('Transaction ID'),
      $this->t('Status'),
      $this->t('Date'),
      $this->t('Payment method'),
      $this->t('Amount'),
      $this->t('Captured amount'),
      $this->t('Reversed amount'),
      $this->t('Operations'),
    ];

    $rows = [];
    $status_readable_name = PagarmeUtility::statusReadableName();
    foreach ($transactions as $transaction) {
      $row = [];
      $row['id'] = $transaction->getId();
      $row['status'] = $status_readable_name[$transaction->getStatus()];
      $date_created = $transaction->getDateCreated()->getTimestamp();
      $row['date_created'] = $this->date_formatter->format($date_created, 'short');
      $payment_method = $transaction->getPaymentMethod();
      $payment_method = ($payment_method == 'credit_card') ? $this->t('credit card') : $this->t('billet');
      $row['payment_method'] = $payment_method;
      $row['amount'] = PagarmeMarketplaceUtility::currencyAmountFormat($transaction->getAmount(), 'integer');
      $row['paid_mount'] = PagarmeMarketplaceUtility::currencyAmountFormat($transaction->getPaidAmount(), 'integer');
      $row['refunded_amount'] = PagarmeMarketplaceUtility::currencyAmountFormat($transaction->getRefundedAmount(), 'integer');
      //Check if user has permission to get a refund
      $link = [];
      if ($this->current_user->hasPermission('transaction effect refund') && $transaction->getStatus() == 'paid') {
        $link['refund'] = [
          'title' => $this->t('Refund'),
          'url' => Url::fromRoute(
            'pagarme.payment_refund', 
            ['transaction_id' => $transaction->getId()]
          ),
          'query' => $destination,
        ];
      }
      $operations = [
        '#theme' => 'links',
        '#links' => $link,
        '#attributes' => ['class' => ['links', 'inline', 'nowrap']],
      ];
      $row['operations'] = render($operations);
      $rows[] = $row;
    }

    $table['transactions'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('There are no transactions.'),
    ];
    $table['pager'] = [
      '#markup' => PagarmeMarketplaceUtility::renderPager($current_path['path'], $parameters),
    ];
    return $table;
  }
}
