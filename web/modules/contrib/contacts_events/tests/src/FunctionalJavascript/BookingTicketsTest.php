<?php

namespace Drupal\Tests\contacts_events\FunctionalJavascript;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_store\Entity\Store;
use Drupal\contacts_events\Entity\Event;
use Drupal\contacts_events\Entity\EventClass;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\contacts_events\Kernel\EventClassConditionTrait;

/**
 * Test the tickets step of the booking process.
 *
 * @group contacts_events
 */
class BookingTicketsTest extends WebDriverTestBase {

  use EventClassConditionTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['contacts_events'];

  /**
   * The event.
   *
   * @var \Drupal\contacts_events\Entity\EventInterface
   */
  protected $event;

  /**
   * A user with permission to the checkout.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The booking.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $booking;

  /**
   * The next screenshot number for the filename.
   *
   * @var int
   */
  protected static $screenshotCount = 0;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set up another event class.
    $class = EventClass::create([
      'id' => 'child',
      'label' => 'Child',
      'type' => 'contacts_ticket',
      'selectable' => FALSE,
    ]);
    $this->addClassDateCondition($class, NULL, 'P18Y');
    $class->save();

    // Set up the event.
    $this->event = Event::create(['type' => 'default']);
    $start = DrupalDateTime::createFromTimestamp(strtotime('+2 months'))
      ->setTime(10, 0, 0);
    $end = (clone $start)
      ->add(new \DateInterval('P1D'))
      ->setTime(17, 0, 0);
    $this->event
      ->set('title', 'Test event')
      ->set('code', 'TEST')
      ->set('date', [
        'value' => $start->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
        'end_value' => $end->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
      ])
      ->set('booking_status', Event::STATUS_OPEN)
      ->set('booking_windows', [
        [
          'id' => 'standard',
          'label' => 'Standard',
        ],
      ])
      ->set('ticket_classes', ['child', 'standard'])
      ->set('ticket_price', [
        [
          'number' => 4.99,
          'currency_code' => 'USD',
          'booking_window' => 'standard',
          'class' => 'child',
        ],
        [
          'number' => 9.99,
          'currency_code' => 'USD',
          'booking_window' => 'standard',
          'class' => 'standard',
        ],
      ])
      ->save();

    $this->user = $this->drupalCreateUser([
      'can book for contacts_events',
    ]);
    $this->drupalLogin($this->user);

    $name = $this->randomMachineName(8);
    $mail = $this->user->getEmail();

    $currency_importer = \Drupal::service('commerce_price.currency_importer');
    $currency_importer->import('USD');
    $store = Store::create([
      'type' => 'online',
      'uid' => 1,
      'name' => $name,
      'mail' => $mail,
      'address' => [
        'country_code' => 'US',
        'address_line1' => $this->randomString(),
        'locality' => $this->randomString(5),
        'administrative_area' => 'WI',
        'postal_code' => '53597',
      ],
      'default_currency' => 'USD',
      'billing_countries' => [
        'US',
      ],
    ]);
    $store->save();

    // Create the booking.
    $this->booking = Order::create([
      'type' => 'contacts_booking',
      'store_id' => $store->id(),
      'event' => $this->event,
      'uid' => $this->user->id(),
      'checkout_step' => 'tickets',
    ]);
    $this->booking->save();
  }

  /**
   * Tests that adding a ticket to an empty booking works.
   */
  public function testAddTicketToEmpty() {
    // Load up the booking page.
    $this->drupalGet(Url::fromRoute('booking_flow', [
      'commerce_order' => $this->booking->id(),
      'step' => 'tickets',
    ]));

    // Check the page loaded correctly.
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', 'form.commerce-checkout-flow-booking-flow');
    $assert_session->elementExists('css', 'div.checkout-pane-tickets');

    // Fill our our first ticket and submit it.
    $this->assertTicketFormEmpty();
    $data = [
      'First name' => 'John',
      'Surname' => 'Smith',
      'date_of_birth' => '01/01/2002',
    ];
    $this->fillTicket($data, ['child' => 'Child'], '$4.99');
    $this->submitTicket('Create ticket', 'Ticket for John Smith', '$4.99');

    // Edit the first ticket to adjust the date of birth.
    $this->click('input[type=submit][value="Edit"][data-drupal-selector="edit-tickets-order-items-entities-0-actions-ief-entity-edit"]');
    $assert_session->assertWaitOnAjaxRequest();
    $this->fillTicket(['date_of_birth' => '01/01/1995'], ['standard' => 'Standard'], '$9.99', 0);
    $this->submitTicket('Update ticket', 'Ticket for John Smith', '$9.99');

    // Add a second ticket.
    $this->click('input[type=submit][value="Add new ticket"]');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertTicketFormEmpty();

    // Fill out and submit the form.
    $data = [
      'First name' => 'Jane',
      'Surname' => 'Smith',
      'date_of_birth' => '01/01/1998',
    ];
    $this->fillTicket($data, ['standard' => 'Standard'], '$9.99');
    $this->submitTicket('Create ticket', 'Ticket for Jane Smith', '$9.99', 1);

    // Edit and resave the second ticket.
    $this->click('input[type=submit][value="Edit"][data-drupal-selector="edit-tickets-order-items-entities-1-actions-ief-entity-edit"]');
    $assert_session->assertWaitOnAjaxRequest();
    $this->fillTicket(['First name' => 'Janet'], NULL, NULL, 1);
    $this->submitTicket('Update ticket', 'Ticket for Janet Smith', '$9.99', 1);
  }

  /**
   * Helper for asserting a ticket form is empty.
   */
  protected function assertTicketFormEmpty() {
    $locators = [
      0 => 'First name',
      1 => 'Surname',
      2 => 'Email',
      "tickets[order_items][form][inline_entity_form][purchased_entity][0][inline_entity_form][date_of_birth][0][value][date]" => 'Date of birth',
    ];

    $page = $this->getSession()->getPage();
    foreach ($locators as $locator => $title) {
      if (is_int($locator)) {
        $locator = $title;
      }
      $this->assertEmpty($page->findField($locator)->getValue(), "{$title} is empty");
    }
  }

  /**
   * Fill out the ticket, optionally checking the price update.
   *
   * @param array $data
   *   An array of data to submit.
   * @param array|null $expected_classes
   *   If we are checking the expected classes, an array of value/label pairs.
   * @param string|null $expected_price
   *   If w eare checking the expected price, a formatted price.
   * @param int|null $delta
   *   The delta of the ticket being edited, or NULL for a new ticket.
   */
  protected function fillTicket(array $data, array $expected_classes = NULL, $expected_price = NULL, $delta = NULL) {
    // Fill out the general details.
    $page = $this->getSession()->getPage();
    foreach ($data as $locator => $value) {
      if ($locator == 'date_of_birth') {
        if (!isset($delta)) {
          $locator = "tickets[order_items][form][inline_entity_form][purchased_entity][0][inline_entity_form][date_of_birth][0][value][date]";
        }
        else {
          $locator = "tickets[order_items][form][inline_entity_form][entities][{$delta}][form][purchased_entity][0][inline_entity_form][date_of_birth][0][value][date]";
        }
      }
      $page->fillField($locator, $value);
    }

    // If we don't have expected classes/price, return now.
    if (!isset($expected_classes) && !isset($expected_price)) {
      return;
    }

    // Wait for the AJAX response to complete (allowing for a delayed trigger).
    usleep(600000);
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Check the expected class options.
    if (isset($expected_classes)) {
      $class_element = $page->findField('Class');
      $options = [];
      foreach ($class_element->findAll('css', 'option') as $option) {
        /* @var \Behat\Mink\Element\NodeElement $option */
        $options[$option->getAttribute('value')] = $option->getText();
      }
      $this->assertEquals($expected_classes, $options, 'Correct classes on update.');
    }

    // Check the expected price.
    if (isset($expected_price)) {
      if (!isset($delta)) {
        $selector = ".form-item-tickets-order-items-form-inline-entity-form-purchased-entity-0-inline-entity-form-mapped-price-0-price-final";
      }
      else {
        $selector = ".form-item-tickets-order-items-form-inline-entity-form-entities-0-form-purchased-entity-{$delta}-inline-entity-form-mapped-price-0-price-final";
      }
      $this->assertSession()->elementTextContains('css', $selector, $expected_price);
    }
  }

  /**
   * Submit a ticket edit form.
   *
   * @param string $button_text
   *   The text on the button to press.
   * @param string $title
   *   The expected title on the IEF row.
   * @param string $price
   *   The expected formatted price on the IEF row.
   * @param int $delta
   *   The delta of the row we're checking.
   */
  protected function submitTicket($button_text, $title, $price, $delta = 0) {
    $row_number = $delta + 1;
    $this->click('input[type=submit][value="' . $button_text . '"]');
    $assert_session = $this->assertSession();
    $assert_session->assertWaitOnAjaxRequest();

    // Check there are no errors.
    $assert_session->elementNotExists('css', '.alert-danger');

    // Check the title and price.
    $row = $this->getSession()
      ->getPage()
      ->find('css', 'table.ief-entity-table')
      ->find('css', "tbody tr:nth-child({$row_number})");
    $this->assertEquals($title, $row->find('css', '.inline-entity-form-commerce_order_item-label')->getText(), 'Correct title');
    $this->assertEquals($price, $row->find('css', '.inline-entity-form-commerce_order_item-unit_price')->getText(), 'Correct price');
  }

}
