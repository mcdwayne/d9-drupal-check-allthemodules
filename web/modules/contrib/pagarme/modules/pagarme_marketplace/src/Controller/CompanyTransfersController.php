<?php
namespace Drupal\pagarme_marketplace\Controller;
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
class CompanyTransfersController extends ControllerBase {
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
  public function renderCompanyTransfers() {
    $current_request = $this->request->getCurrentRequest();
    $current_path = parse_url($current_request->getRequestUri());
    $parameters = $current_request->query->all();
    $destination = $this->getDestinationArray();
    $page = (!empty($parameters['page'])) ? $parameters['page'] : 1;
    $per_page = 10;
    $pagarme_sdk = new PagarmeSdk($this->route_match->getParameter('company'));
    $transfers = $pagarme_sdk->pagarme->transfer()->getList($page, $per_page);

    $header = [
      $this->t('Transfer ID'),
      $this->t('Amount'),
      $this->t('Type'),
      $this->t('Status'),
      $this->t('Tax'),
      $this->t('Operations'),
    ];

    $rows = [];
    foreach ($transfers as $transfer) {
      $row = [];
      $row['id'] = $transfer->getId();
      $row['amount'] = PagarmeMarketplaceUtility::currencyAmountFormat($transfer->getAmount(), 'integer');
      $row['type'] = $transfer->getType();
      $status = $transfer->getStatus();
      $row['status'] = ($status == 'pending_transfer') ? t('pending transfer') : $status;
      $row['fee'] = PagarmeMarketplaceUtility::currencyAmountFormat($transfer->getFee(), 'integer');
      $link = [];
      $operations = [
        '#theme' => 'links',
        '#links' => $link,
        '#attributes' => ['class' => ['links', 'inline', 'nowrap']],
      ];
      $row['operations'] = render($operations);
      $rows[] = $row;
    }

    $table['transfers'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No transfers exists'),
    ];

    $table['pager'] = [
      '#markup' => PagarmeMarketplaceUtility::renderPager($current_path['path'], $parameters),
    ];

    return $table;
  }
}
