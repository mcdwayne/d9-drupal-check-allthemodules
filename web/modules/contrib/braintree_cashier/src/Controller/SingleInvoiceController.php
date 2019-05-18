<?php

namespace Drupal\braintree_cashier\Controller;

use Braintree\Transaction;
use Dompdf\Dompdf;
use Drupal\braintree_cashier\BraintreeCashierService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\User;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\Parser\DecimalMoneyParser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\braintree_api\BraintreeApiService;
use Drupal\braintree_cashier\BillableUser;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SingleInvoiceController.
 */
class SingleInvoiceController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Logger\LoggerChannel definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * Drupal\braintree_api\BraintreeApiService definition.
   *
   * @var \Drupal\braintree_api\BraintreeApiService
   */
  protected $braintreeApi;

  /**
   * Drupal\braintree_cashier\BillableUser definition.
   *
   * @var \Drupal\braintree_cashier\BillableUser
   */
  protected $billableUser;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The money parser.
   *
   * @var \Money\MoneyParser
   */
  protected $moneyParser;

  /**
   * The money formatter.
   *
   * @var \Money\MoneyFormatter
   */
  protected $moneyFormatter;

  /**
   * The Braintree Cashier Service.
   *
   * @var \Drupal\braintree_cashier\BraintreeCashierService
   */
  protected $bcService;

  /**
   * Constructs a new SingleInvoiceController object.
   */
  public function __construct(LoggerChannel $logger_channel_braintree_cashier, BraintreeApiService $braintree_api_braintree_api, BillableUser $braintree_cashier_billable_user, Renderer $renderer, DateFormatterInterface $dateFormatter, RequestStack $requestStack, ModuleHandlerInterface $moduleHandler, BraintreeCashierService $bcService) {
    $this->logger = $logger_channel_braintree_cashier;
    $this->braintreeApi = $braintree_api_braintree_api;
    $this->billableUser = $braintree_cashier_billable_user;
    $this->renderer = $renderer;
    $this->dateFormatter = $dateFormatter;
    $this->requestStack = $requestStack;
    $this->moduleHandler = $moduleHandler;
    $this->bcService = $bcService;

    // Setup Money.
    $currencies = new ISOCurrencies();
    $this->moneyParser = new DecimalMoneyParser($currencies);
    $numberFormatter = new \NumberFormatter($this->bcService->getLocale(), \NumberFormatter::CURRENCY);
    $this->moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.channel.braintree_cashier'),
      $container->get('braintree_api.braintree_api'),
      $container->get('braintree_cashier.billable_user'),
      $container->get('renderer'),
      $container->get('date.formatter'),
      $container->get('request_stack'),
      $container->get('module_handler'),
      $container->get('braintree_cashier.braintree_cashier_service')
    );
  }

  /**
   * Views a single invoice as HTML.
   *
   * By returning a Response object, this controller bypasses the currently
   * active theme. This allows creating a PDF file download in the download()
   * method.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity being viewed.
   * @param string $invoice
   *   The Braintree transaction ID.
   *
   * @return array|string
   *   A render array.
   *
   * @throws \Exception
   */
  public function view(User $user, $invoice) {

    $build = $this->getRenderArray($user, $invoice);

    return Response::create($this->renderer->render($build));
  }

  /**
   * Downloads a single invoice as a pdf.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity being viewed.
   * @param string $invoice
   *   The Braintree transaction ID.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The symfony response.
   *
   * @throws \Exception
   */
  public function download(User $user, $invoice) {
    $dompdf = new Dompdf();

    $build = $this->getRenderArray($user, $invoice);

    $dompdf->loadHtml($this->renderer->render($build));

    $dompdf->render();

    $filename = 'receipt-' . $build['#invoice_id'] . '.pdf';

    return new Response($dompdf->output(), 200, [
      'Content-Description' => 'File Transfer',
      'Content-Disposition' => 'attachment; filename="' . $filename . '"',
      'Content-Transfer-Encoding' => 'binary',
      'Content-Type' => 'application/pdf',
    ]);
  }

  /**
   * Gets the render array for the single-invoice template.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity which had this transaction.
   * @param string $invoice
   *   The Braintree transaction ID, which we assign as the invoice ID.
   *
   * @return array
   *   The render array to pass to the single-invoice template.
   */
  private function getRenderArray(User $user, $invoice) {
    $transaction = $this->asBraintreeTransaction($invoice);
    $created_timestamp = $transaction->createdAt->getTimestamp();
    $host_module_path = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/' . $this->moduleHandler->getModule('braintree_cashier')->getPath();
    $business_information = $this->config('braintree_cashier.settings')->get('invoice_business_information');
    $business_information = check_markup($business_information['value'], $business_information['format']);
    $currency_code = $this->config('braintree_cashier.settings')->get('currency_code');
    $amount = $this->moneyParser->parse($transaction->amount, $currency_code);
    $amount_prefix = $transaction->type == \Braintree_Transaction::SALE ? '' : '-';
    $build = [
      '#theme' => 'single_invoice',
      '#invoice_id' => $invoice,
      '#invoice_site_name' => ucfirst($this->requestStack->getCurrentRequest()->getHost()),
      '#original_price' => $this->moneyFormatter->format($this->getOriginalPrice($transaction)),
      '#discounts' => $this->getDiscounts($transaction),
      '#total' => $amount_prefix . $this->moneyFormatter->format($amount),
      '#username' => $user->getUsername(),
      '#user_email' => $user->getEmail(),
      '#invoice_date' => $this->dateFormatter->format($created_timestamp, 'html_date'),
      '#created_timestamp' => $created_timestamp,
      '#business_name' => $this->config('system.site')->get('name'),
      '#base_css_path' => $host_module_path . '/css/single-invoice--base.css',
      '#braintree_cashier_css_path' => $host_module_path . '/css/single-invoice.css',
      '#invoice_billing_information' => $this->billableUser->getInvoiceBillingInformation($user),
      '#invoice_business_information' => $business_information,
      '#notes' => $this->t('Thank you!'),
      '#currency_code' => $currency_code,
    ];
    return $build;
  }

  /**
   * Get discounts associated with the Braintree transaction.
   *
   * @param \Braintree\Transaction $transaction
   *   The Braintree transaction.
   *
   * @return array
   *   An array of associative discount arrays, each with keys 'id' and
   *   'amount'. The amount is a string formatted for presentation.
   */
  private function getDiscounts(Transaction $transaction) {
    $discounts = [];
    $currency_code = $this->config('braintree_cashier.settings')->get('currency_code');
    foreach ($transaction->discounts as $discount) {
      /** @var \Braintree\Discount $discount */
      $amount = $this->moneyParser->parse($discount->amount, $currency_code);
      $discounts[] = [
        'id' => $discount->id,
        'amount' => $this->moneyFormatter->format($amount),
      ];
    }
    return $discounts;
  }

  /**
   * Determines the original price for a transaction before discounts.
   *
   * @param \Braintree\Transaction $transaction
   *   The Braintree transaction.
   *
   * @return \Money\Money
   *   The original price.
   */
  private function getOriginalPrice(Transaction $transaction) {
    $currency_code = $this->config('braintree_cashier.settings')->get('currency_code');
    $original_price = $this->moneyParser->parse($transaction->amount, $currency_code);
    foreach ($transaction->discounts as $discount) {
      /** @var \Braintree_Discount $discount */
      $discount_amount = $this->moneyParser->parse($discount->amount, $currency_code);
      $original_price = $original_price->add($discount_amount);
    }
    return $original_price;
  }

  /**
   * Gets the Braintree Transaction with the given transaction ID.
   *
   * @param string $invoice
   *   The Braintree Transaction ID.
   *
   * @return \Braintree\Transaction
   *   The Braintree transaction object.
   */
  private function asBraintreeTransaction($invoice) {
    return $this->braintreeApi->getGateway()->transaction()->find($invoice);
  }

}
