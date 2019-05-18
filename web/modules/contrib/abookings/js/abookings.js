/**
 * @file
 * A JavaScript file for the theme.
 *
 * In order for this JavaScript to be loaded on pages, see the instructions in
 * the README.txt next to this file.
 */

// JavaScript should be made compatible with libraries other than jQuery by
// wrapping it with an "anonymous closure". See:
// - https://drupal.org/node/1446420
// - http://www.adequatelygood.com/2010/3/JavaScript-Module-Pattern-In-Depth
(function ($, Drupal, window, document, undefined) {

/**
  CONTENTS

  .set_up_dialogs()
  .add_booking_form_submit_handler()
  .add_field_listeners()
  .check_num_guests()
  .check_date_field_valid_value()
  .add_booking_data_fetched_listener()
  .setup_addons()
  .get_addons_total_guests()
  .calculate_max_guests()
  .set_up_calendar()
  .refresh_main_info()
  .save_booking()
  .show_thank_you_dialog()
  .get_addons_sum()
  .get_addons_total()

  Drupal.abookings
    .get_bookable_nid()
    .get_bookable_data()
    .set_booking_form()
    .find_page_elements()
    .dayHasPrice()
    .dayRender()
    .dayHover()
    .get_seasonal_price()
    .fetch_bookable_data()
    .validate_promo()
    .add_booking()
    .date_unix_to_iso()
    .create_booking()
    .calculate_base_cost()
    .check_event_overlap()
    .invoke_callbacks()
    .validate_booking_form()
 */

var page_setup = false;

var calendar,
  booking_form,
  events = {},
  price_prefix = 'R',
  price_suffix = '',
  discount = 0,
  is_valid_promo = true,
  max_guests = null;

var date_arrive,
  date_depart;

// When calendar is clicked, first click used for arrival, second for departure.
var last_calendar_click = null;

var bookable_field,
  arrival_field,
  departure_field,
  nights_field,
  num_guests_field,
  num_nights_field,
  addons_field,
  base_cost_field,
  promo_field,
  booking_info_container;

var bookable_data = {};
var is_bookable_data_fetched = false;

// To understand behaviors, see https://drupal.org/node/756722#behaviors
Drupal.behaviors.abookings = {
  attach: function(context, settings) {

    if (! page_setup) {
      // console.log('context: ', context);
      // console.log('settings: ', settings);
      // console.log('drupalSettings: ', drupalSettings);

      switch (drupalSettings.route.name) {
        case 'abookings.book_page':
          Drupal.abookings.set_booking_form('#node-booking-form', context);
          break;
        case 'entity.node.edit_form':
          Drupal.abookings.set_booking_form('#node-booking-edit-form', context);
          break;
        case 'node.add':
          Drupal.abookings.set_booking_form('#node-booking-form', context);
          break;
      }

      Drupal.abookings.find_page_elements(context);

      if (calendar.length >= 1) {
        set_up_calendar(context);
      }

      var backend_url = drupalSettings.booking_settings['backend_url'];
      var bookable_nid = Drupal.abookings.get_bookable_nid();

      if (! is_bookable_data_fetched) {
        Drupal.abookings.fetch_bookable_data('seasons', bookable_nid, backend_url);
        Drupal.abookings.fetch_bookable_data('bookings', bookable_nid, backend_url);
        Drupal.abookings.fetch_bookable_data('addons', bookable_nid, backend_url);
        Drupal.abookings.fetch_bookable_data('bookables', 'all', backend_url);
        is_bookable_data_fetched = true;
      }

      set_up_dialogs(context);

      add_booking_data_fetched_listener();

      // Define hover listeners

      var calendar_days = $(calendar).find('.fc-day');
      // console.log('calendar_days: ', calendar_days);

      calendar_days.on('mouseenter', function() {
        // calendar_days.removeClass('active');
        // $(this).addClass('active');
      });
      calendar_days.on('mouseleave', function() {
        // $(this).removeClass('active');
      });

      // Define listeners

      add_booking_form_submit_handler(context);

      add_field_listeners(context);

      refresh_main_info();

      // show_thank_you_dialog('test', booking_info_container);
      page_setup = true;
    }
  }
};


function set_up_dialogs(context) {
  // Bind a click handler on the ctools modal dialog's backdrop that closes that modal.
  $(document).on('dialog_opened', function(event) {
    // Bind a click handler on the modal dialogs' backdrops that closes modals.
    $('.ui-widget-overlay', context).once('backdrop-close-modal').click(function() {
      // console.log('.ui-widget-overlay.click()');
      to_dialog.dialog( "close" );
    });
  });
}


function add_booking_form_submit_handler(context) {
  booking_form.once('booking_form:on_submit').on('submit', function(event) {
    if (drupalSettings.route.name == 'entity.node.edit_form'
      || drupalSettings.route.name == 'node.add') {
      return true;
    }

    if (! is_valid_promo) {
      alert('Promo code is not valid.');
      return false;
    }

    // Save the booking using AJAX

    event.preventDefault();

    is_valid = Drupal.abookings.validate_booking_form();
    if (! is_valid) {
      return false;
    }

    var serialized_form = $(this).serializeArray();
    // console.log('serialized_form: ', serialized_form);

    var data_object = Drupal.abookings.create_booking(serialized_form);
    // console.log('data_object: ', data_object);
    save_booking(data_object, 'booking');
  });
}


/**
 * Listener functions for the form elements such as the "Arrival date" field.
 */
function add_field_listeners(context) {
  // Define field value change listeners
  // console.log('add_field_listeners()');

  num_guests_field.change(function() {
    // Check that there's bookable data
    if (jQuery.isEmptyObject(Drupal.abookings.get_bookable_data())) {
      alert('Please select a bookable unit');
      return false;
    }

    var change_okay = check_num_guests();
    if (! change_okay) {
      return null;
    }
    refresh_main_info();
  });


  arrival_field.change(function() {
    // Calculate number of nights
    date_arrive = moment(arrival_field.val());
    date_depart = moment(departure_field.val());
    num_nights = date_depart.diff(date_arrive, 'days');
    if (num_nights <= 0) {
      alert('Departure date must be after arrival date');
      num_nights_field.val('');
      return false;
    }

    num_nights_field.val(num_nights);
    refresh_main_info();
  });
  departure_field.change(function() {
    // Calculate number of nights
    date_arrive = moment(arrival_field.val());
    date_depart = moment(departure_field.val());
    num_nights = date_depart.diff(date_arrive, 'days');
    if (num_nights <= 0) {
      alert('Departure date must be after arrival date');
      num_nights_field.val('');
      return false;
    }

    num_nights_field.val(num_nights);
    refresh_main_info();
  });


  num_nights_field.change(function() {
    // Calculate + set depart date
    num_nights = num_nights_field.val();
    date_arrive = moment(arrival_field.val());
    date_depart = moment(date_arrive);
    date_depart.add(num_nights, 'day');

    departure_field.val(date_depart.format('Y-MM-DD'));
    refresh_main_info();
  });

  addons_field.change(function() {
    check_num_guests();
    refresh_main_info();
  });

  bookable_field.change(function() {
    refresh_main_info();
  });

  $('#validate_promo', context).once('validate_promo').click(function(event) {
    event.preventDefault();
    // console.log('promo_code: ', promo_code);
    Drupal.abookings.validate_promo();
  });
};


function check_num_guests() {
  calculate_max_guests();
  var num_guests = parseInt(num_guests_field.val());

  // console.log('max_guests: ', max_guests);
  if (num_guests > max_guests) {
    alert('Sorry, you cannot book for more than ' + max_guests + ' guests.');
    num_guests_field.val(max_guests);
    return false;
  }

  var min_guests = parseInt(Drupal.abookings.get_bookable_data()['field_min_guests']);
  // console.log('min_guests: ', min_guests);
  if (num_guests < min_guests) {
    alert('Sorry, you cannot book for fewer than ' + min_guests + ' guests.');
    num_guests_field.val(min_guests);
    return false;
  }
  return true;
}


function check_date_field_valid_value(field) {
  var date_arrive = moment(arrival_field.val());
  // console.log('date_arrive: ', date_arrive);
  var date_depart = moment(departure_field.val());
  // console.log('date_depart: ', date_depart);

  // Determine number of nights
  var num_nights;

  if (date_arrive._isValid && date_depart._isValid) {
    num_nights = date_depart.diff(date_arrive, 'days');
  }
  else {
    return null;
  }
  // console.log('num_nights: ', num_nights);

  var bookable_data = Drupal.abookings.get_bookable_data();
  // console.log('bookable_data: ', bookable_data);
  var min_nights = parseInt(bookable_data['field_min_guests']);
  min_nights = (min_nights) ? min_nights : 1;
  // console.log('min_nights: ', min_nights);
  var min_nights_valid = num_nights >= min_nights;
  if (! min_nights_valid) {
    alert('Sorry, you cannot book for fewer than ' + min_nights + ' nights.');
    field.val(null);
    return false;
  }
}


function add_booking_data_fetched_listener() {
  $(document).on('bookable_data_fetched', function(element, type) {
    // console.log('bookable_data_fetched, type: ', type);

    if (calendar.length > 0) {
      // Force re-render of calendar days
      calendar.fullCalendar('next');
      calendar.fullCalendar('prev');
    }

    if (type === 'seasons') {}
    else if (type === 'bookables') {
      calculate_max_guests();
    }
    else if (type === 'addons') {
      // console.log("bookable_data['addons']: ", bookable_data['addons']);

      // Hide all the addons
      var addon_checkboxes = $('input[name^="field_addons"]');

      // If there are no checkboxes, make them from fetched data
      if (addon_checkboxes.length == 0) {
        $.each(bookable_data['addons'], function(index, addon_data) {
          // console.log('addon_data: ', addon_data);

          var checkbox = $('<div class="form-item form-type-checkbox" style="display: block;"></div>')
            .append('<input id="edit-field-addons-' + addon_data.nid + '" '
              + 'name="field_addons[' + addon_data.nid + ']" '
              + 'value="' + addon_data.nid + '" '
              + 'class="form-checkbox" type="checkbox">')
            .append('<label for="edit-field-addons-' + addon_data.nid + '" '
              + 'class="option">Cottage</label>');

          $('.field--name-field-addons .form-checkboxes').append(checkbox);
        });

        addons_field = booking_form.find('input[name^="field_addons"]');
        addons_field.change(function() {
          refresh_main_info();
        });
        addon_checkboxes = $('input[name^="field_addons"]');
        // console.log('addon_checkboxes: ', addon_checkboxes);
      }

      addon_checkboxes.parents('.form-type-checkbox').hide();

      var currency_prefix = 'R';
      var currency_suffix = '';

      // For all addons the selected bookable has, show them, append cost
      addon_checkboxes.each(function(index, checkbox) {
        // console.log('checkbox: ', checkbox);
        var nid = $(checkbox).attr('value');
        $.each(bookable_data['addons'], function(index, addon_data) {
          if (addon_data.nid == nid) {
            var price_type = addon_data.field_price_type;
            if (price_type == 'once-off') {price_type = 'once-off';}
            if (price_type == 'flat') {price_type = 'per night';}
            if (price_type == 'guest-based') {price_type = 'per night per guest';}

            $(checkbox)
              .parents('.form-type-checkbox').show()
              .find('label')
              .prepend(addon_data.field_image)
              .append(' (' + currency_prefix + addon_data.field_amount + ' ' + price_type + currency_suffix + ')')
              .append('<br><small>' + addon_data.field_short_description + '</small>');

            return false;
          }
        });
      });
      if (bookable_data['addons'].length == 0) {
        $('#edit-field-addons--wrapper .fieldset-wrapper')
          .append('<em>No addons available</em>');
      }

      calculate_max_guests();
    }

    // Display other people's bookings
    else if (type === 'bookings') {
      // console.log("bookable_data['bookings']: ", bookable_data[' bookings']);
      var date = new Date();
      var timestamp_first_of_month = date.setDate(1)/1000; // *60*60*24
      // console.log('timestamp_first_of_month: ', timestamp_first_of_month);

      // If bookable_data hasn't been fetched yet, can't render yet.
      if (typeof bookable_data['bookings'] === 'undefined') {
        return null;
      }

      $.each(bookable_data['bookings'], function(index, booking_data) {
        // console.log('booking_data: ', booking_data);
        // calendar.fullCalendar('removeEvents');
        var timestamp_checkout_date = parseInt(booking_data.field_checkout_date);
        // If the checkout date is in the past
        if (timestamp_checkout_date >= timestamp_first_of_month) {
          Drupal.abookings.add_booking(booking_data.nid, booking_data);
        }
      });
    }

  });
}


function setup_addons() {}


function get_addons_total_guests() {
  var guests = 0;
  addon_checkboxes = $('input[name^="field_addons"]:checked');

  // For all addons the selected bookable has, show them, append cost
  addon_checkboxes.each(function(index, checkbox) {
    // console.log('checkbox: ', checkbox);
    var nid = $(checkbox).attr('value');
    $.each(bookable_data['addons'], function(index, addon_data) {
      // console.log('addon_data: ', addon_data);
      if (addon_data.nid == nid) {
        guests += parseInt(addon_data.field_num_guests);
      }
    });
  });
  // console.log('addons_guests: ', addons_guests);
  return guests;
}


function calculate_max_guests() {
  var bookable_data = Drupal.abookings.get_bookable_data();
  // console.log('bookable_data: ', bookable_data);
  if (! bookable_data) {
    // throw 'No booking data in calculate_max_guests()';
    return null;
  }
  var bookable_max_guests = parseInt(bookable_data['field_max_guests']);
  // console.log('bookable_max_guests: ', bookable_max_guests);
  var addons_guests = get_addons_total_guests();
  // console.log('addons_guests: ', addons_guests);
  max_guests = bookable_max_guests + addons_guests;
  // console.log('max_guests: ', max_guests);

  var label = num_guests_field.siblings('label');
  if (! label.find('span').length) {
    label.append(" <span></span>");
  }
  label.find('span').html("(max " + max_guests + " guests)");
}



function set_up_calendar(context) {
  // console.log('set_up_calendar()');

  calendar.fullCalendar({
    dayRender: function(date, cell) {
      Drupal.abookings.dayRender(date, cell);
    },
    eventMouseover: function(event, jsEvent, view) {
      // console.log('hovered');
      // This doesn't work, because days aren't events!
      // Drupal.abookings.dayHover(event, jsEvent, view);
    },
    dayClick: function(date_clicked, jsEvent, view, resourceObj) {
      // console.log('date_clicked: ', date_clicked);

      // var date_arrive,
      //   date_depart;

      // // If the calendar hasn't been clicked, or the last click was for check out
      // // Use clicked date for arrival
      // if (last_calendar_click !== 'arrive') {
      // }
      // // Use clicked day for departure
      // else {
      //   date_depart = date_clicked;
      //   date_arrive = moment(arrival_field.val());
      //   num_nights = date_depart.diff(date_arrive, 'days');
      // }

      // date_arrive = date_clicked;
      // var nights = parseInt(nights_field.val());
      //
      // date_depart = moment(date_arrive);
      // date_depart.add(nights, 'day');
      //
      // dayClickHandler(date_arrive, date_depart);
    }
  });

  $(document, context).on('mousedown', '.fc-day, .fc-day-top', function(jsEvent) {
    var date_clicked = moment($(this).attr('data-date'));
    // console.log('date_clicked: ', date_clicked);

    date_arrive = date_clicked;
    var nights = parseInt(nights_field.val());

    arrival_field.val(date_arrive.format('Y-MM-DD'));
  });


  $(document, context).on('mouseup', '.fc-day, .fc-day-top', function(jsEvent) {
    var date_clicked = moment($(this).attr('data-date'));
    // console.log('date_clicked: ', date_clicked);

    date_depart = date_clicked;
    date_arrive = moment(arrival_field.val());
    if (date_arrive.unix() == date_depart.unix()) {
      date_depart.add(1, 'day');
    }
    num_nights = date_depart.diff(date_arrive, 'days');
    num_nights_field.val(num_nights);

    departure_field.val(date_depart.format('Y-MM-DD'));

    dayClickHandler(date_arrive, date_depart);
  });
};


/**
 * @param {Object} date_arrive - Moment object
 * @param {Object} date_depart - Moment object
 */
function dayClickHandler(date_arrive, date_depart) {
  // console.log('dayClickHandler()');
  // console.log('date_arrive: ', date_arrive);
  // console.log('date_depart: ', date_depart);

  // Check that there's bookable data
  if (jQuery.isEmptyObject(Drupal.abookings.get_bookable_data())) {
    alert('Please select a bookable unit');
    return null;
  }
  if(! Drupal.abookings.dayHasPrice(date_arrive)) {
    alert('Please select a date that has a price');
    return null;
  }

  // If this new event overlaps with any others
  var overlap = Drupal.abookings.check_event_overlap(date_arrive, date_depart);
  if (overlap) {
    return null;
  }

  // Create or update the current booking
  var booking_data = {
    field_booking_status: 'provisional',
    field_checkin_date: date_arrive.unix(),
    field_checkout_date: date_depart.unix()
  };
  Drupal.abookings.add_booking('current', booking_data);

  refresh_main_info();

  // if (last_calendar_click == null) {
  //   last_calendar_click = 'arrive';
  // }
  // else if (last_calendar_click == 'arrive') {
  //   last_calendar_click = 'depart';
  // }
  // else {
  //   last_calendar_click = 'arrive';
  // }
}



/**
 * Used by both front-end (Book page) and backend (booking node edit forms).
 */
function refresh_main_info() {
  // console.log('refresh_main_info()');
  // console.log('date_arrive: ', date_arrive);

  // var is_from_click = (typeof date_arrive === 'undefined') ? false : true;

  var date_arrive = moment(arrival_field.val());
  // console.log('date_arrive: ', date_arrive);
  var date_depart = moment(departure_field.val());
  // console.log('date_depart: ', date_depart);

  if (date_arrive._isValid) {
    calendar.fullCalendar('gotoDate', date_arrive);
  }

  // Determine number of nights
  var num_nights;

  if (date_arrive._isValid && date_depart._isValid) {
    num_nights = date_depart.diff(date_arrive, 'days');
  }
  else {
    return null;
  }
  // console.log('num_nights: ', num_nights);

  var overlap = Drupal.abookings.check_event_overlap(date_arrive, date_depart);
  if (overlap) {
    return null;
  }

  // If the is the Book page
  if (drupalSettings.route.name == 'abookings.book_page') {
    // Create or update the current booking (on JS calendar)
    var booking_data = {
      field_booking_status: 'provisional',
      field_checkin_date: date_arrive.unix(),
      field_checkout_date: date_depart.unix()
    };
    // console.log('booking_data: ', booking_data);
    Drupal.abookings.add_booking('current', booking_data);
  }

  // If bookable_data hasn't been fetched yet, can't render yet.
  if (jQuery.isEmptyObject(Drupal.abookings.get_bookable_data())) {
    return null;
  }

  // console.log('here1');


  // Base cost
  var base_cost = Drupal.abookings.calculate_base_cost();
  // console.log('base_cost: ', base_cost);
  base_cost_field.val(base_cost); // The hidden field
  var currency_prefix = 'R';

  // .toLocaleString("en-GB", {style: "currency", currency: "GBP", minimumFractionDigits: 2})
  booking_info_container.find('.field_base_cost .value')
    .html(currency_prefix + base_cost);

  // Additions  (not shown)
  // Deductions (not shown)

  // Addons
  var addons_total = get_addons_total(num_nights);
  booking_info_container.find('.field_addons .value')
    .html(currency_prefix + addons_total);

  // Promotions (technically calculated as % of total cost)
  var discount_amount = discount / 100 * base_cost;
  booking_info_container.find('.field_promo_discount .value')
    .html(currency_prefix + discount_amount);

  // Total cost
  booking_info_container.find('.field_total_cost .value')
    .html(currency_prefix + (base_cost - discount_amount + addons_total));



  // console.log('here2');

  // Arrival date
  booking_info_container.find('.field_checkin_date .value')
    .html(date_arrive.format('dddd, D MMMM Y'));

  // Departure date
  booking_info_container.find('.field_checkout_date .value')
    .html(date_depart.format('dddd, D MMMM Y'));

  // Number of nights
  booking_info_container.find('.field_num_nights .value')
    .html(num_nights);
}


/**
 * @returns true if successful, false otherwise.
 */
function save_booking(data_object, type, success_callbacks, always_callbacks) {
  // console.log('data_object: ', data_object);

  var url;
  var api_url = drupalSettings.booking_settings['backend_url'] + '/bookings-api/';
  // console.log('api_url: ', api_url);

  switch (type) {
    case 'booking':
      url = api_url + 'booking';
      // + 'filter[collection]=' + collection_nid
      // + 'range=1000';
      break;

    default:
      throw 'Param "type" not valid in ' + 'Drupal.es_api_interactions.fetch_selectables_data().';
      break;
  }
  // console.log('data: ', data);

  var settings = {
    type: "POST",
    contentType: "application/json",
    data: JSON.stringify(data_object, null, 2),
    url: url
  }
  var jqxhr = $.ajax(settings);
  // alert('Placing booking...');
  booking_info_container.find('.loader').removeClass('hidden');

  // Failure
  jqxhr.fail(function( data ) {
    if(jqxhr.readyState < 4)  {
      console.log( "Request was not completed." );
    }
    else {
      console.log( "In jqxhr.fail(), data: ", data );
    }

    var message = 'Sorry, there was a problem with your booking.\n\n'
        + 'Reason: ' + data.responseJSON.error_message;
    alert(message);
  });

  // Success
  jqxhr.done(function( data ) {
    // console.log( 'jqxhr.done(). data: ', data);

    if (typeof data == 'string') {
      var message = 'Sorry, there was a problem with your booking.\n\n'
          + 'Reason: ' + data;
      alert(message);
      return null;
    }

    var node_url = drupalSettings.path.baseUrl + 'node/' + data.nid;
    // console.log('node_url: ', node_url);
    var node_link = '<a href="' + node_url + '">View booking</a>';
    // window.location.replace(node_url);

    booking_info_container.find('.message_container')
      .find('[class*="messages"]').remove();

    show_thank_you_dialog(data.nid, booking_info_container);

    arrival_field.val(null);
    departure_field.val(null);
    num_nights_field.val(null);

    Drupal.abookings.invoke_callbacks(success_callbacks);
  });

  // Always
  jqxhr.always(function( data ) {

    booking_info_container.find('.loader').addClass('hidden');
  });

}


function show_thank_you_dialog(nid, container) {
  var message_text = '<div><p>Thank you for booking with us! '
    + 'We have sent you an email with your provisional booking information.</p>'
    + '<p>Your reference number is <strong>' + nid + '</strong></p></div>';
  var message = $(message_text)
    .appendTo(container.find('.message_container'));
  // console.log('container: ', container);
  // console.log('message: ', message);

  message.dialog({
      title: 'Booking successful',
      dialogClass: 'no-close',
      modal: true,
      show: 300,
      width: 450,
      buttons: [
        {
          text: "Okay",
          click: function() {
            $( this ).dialog( "close" );
          }
        }
      ]
    });
}



function get_addons_sum(type) {
  var sum = 0;

  // Loop through addons checkboxes
  $('input[name^="field_addons"]').each(function(element) {

    if (! $(this).is(':checked')) {
      return;
    }

    var nid = $(this).val();
    // console.log('nid: ', nid);

    // Loop through addons fetched
    $.each(bookable_data['addons'], function(index, addon) {
      if (addon['field_price_type'] == type) {
        sum += parseFloat(addon['field_amount']);
      }
    });
  });

  return sum;
}

function get_addons_total(num_nights) {
  // console.log('num_nights: ', num_nights);
  var total = 0;

  var num_guests = parseInt(num_guests_field.val());

  var addons_once   = get_addons_sum('once-off'),
      addons_flat   = get_addons_sum('flat'),
      addons_pguest = get_addons_sum('guest-based');

  total += addons_once;

  for (var i = num_nights - 1; i >= 0; i--) {
    total += addons_flat;
    total += addons_pguest * num_guests;
    // console.log('total: ', total);
  }

  return total;
}





Drupal.abookings = {

  get_bookable_nid: function() {
    return bookable_field.val();
  },

  /**
   * Gets the data of the active bookable unit.
   */
  get_bookable_data: function() {
    var bookable_nid = Drupal.abookings.get_bookable_nid();
    // console.log('bookable_nid: ', bookable_nid);
    var active_bookable = null;

    $.each(bookable_data['bookables'], function(index, bookable) {
      // console.log("bookable: ", bookable);
      if (bookable['nid'] == bookable_nid) {
        active_bookable = bookable;
      }
    });
    // if (active_bookable === null) {
    //   throw "There is no active bookable.";
    //   // alert('Please choose a bookable unit before placing a booking.');
    // }
    return active_bookable;
  },

  set_booking_form: function(selector, context) {
    // console.log('selector: ', selector);

    // console.log('calendar: ', calendar);
    if (typeof booking_form === 'undefined') {
      // booking_form = $('#book-page-form', context);
      booking_form = $(selector, context);
    }
    // console.log('booking_form: ', booking_form);
  },



  find_page_elements: function(context) {
    // console.log('booking_form: ', booking_form);

    // Define calendar and booking_form
    if (typeof calendar === 'undefined') {
      calendar = $('#calendar', context);
    }

    if (typeof booking_info_container === 'undefined') {
      booking_info_container = $('#booking_info', context);
      // console.log('booking_info_container: ', booking_info_container);

      if (booking_info_container.length == 0) {
        booking_info_container =
          $('<div id="booking_info"><div class="message_container"></div></div>', context)
          .prependTo('body');
      }
      // console.log('booking_info_container: ', booking_info_container);
    }
    // console.log('booking_info_container: ', booking_info_container);

    bookable_field    = booking_form.find('select[name="field_bookable_unit"]');
    arrival_field     = booking_form.find('input[name^="field_checkin_date"]');
    departure_field   = booking_form.find('input[name^="field_checkout_date"]');
    nights_field      = booking_form.find('input[name^="field_num_nights"]');
    num_guests_field  = booking_form.find('input[name^="field_num_guests"]');
    num_nights_field  = booking_form.find('input[name^="field_num_nights"]');
    addons_field      = booking_form.find('input[name^="field_addons"]');
    base_cost_field   = booking_form.find('input[name^="field_base_cost"]');
    promo_field       = booking_form.find('input[name^="field_promo_code_provided"]');
  },


  dayHasPrice: function(date) {
    var date_price = Drupal.abookings.get_seasonal_price(date.unix());
    return date_price ? true : false;
  },


  dayRender: function(date, cell) {
    // console.log('date: ', date);
    // console.log('cell: ', cell);

    // If bookable_data hasn't been fetched yet, can't render yet.
    if (typeof bookable_data['seasons'] === 'undefined') {
      return null;
    }

    var date_price;
    var cell_markup = '';
    var num_guests = num_guests_field.val();
    // console.log('num_guests: ', num_guests);

    var date_price = Drupal.abookings.get_seasonal_price(date.unix()) * 1; // Display as integer

    if (date_price) {
      // date_price = date_price * parseInt(num_guests);
      cell_markup = '<p>' + price_prefix + date_price + '</p>';
    }
    else {
      cell_markup = '<p><span title="Price on request">PoR</span></p>';
    }

    $(cell).append(cell_markup);
  },


  dayHover: function(event, jsEvent, view) {
    // console.log('event: ', event;
    // console.log('jsEvent: ', jsEvent);
    // console.log('view: ', view);
  },


  get_seasonal_price: function(unix_date) {
    var seasonal_price = null;

    // If bookable_data hasn't been fetched yet, can't render yet.
    if (typeof bookable_data['seasons'] === 'undefined') {
      console.log( "Error: bookable_data hasn't been fetched yet" );
      return null;
    }

    // Loop through all seasons

    // console.log(bookable_data, 'bookable_data causing problems?');
    $.each(bookable_data['seasons'], function(index, season_data) {
      // console.log(index, ': ', season_data);

      // If date falls in a season
        if ((unix_date > season_data.field_start_date)
          && (unix_date < season_data.field_end_date)) {
          // Calculate product of season price and guests
          seasonal_price = season_data.field_seasonal_price;
        }
    });

    return seasonal_price;
  },


  /**
   * Fetches data from the backend. Has to be fetched using an HTTP request
   * rather than from drupalSettings because the backend might be a different site.
   *
   * @param type
   *   The type of data to fetch. Can be "seasons", "bookings", "bookables".
   * @param nid
   *   The ID of the bookable unit node.
   * @param drupalSettings
   */
  fetch_bookable_data: function(type, nid, backend_url) {
    // If there is no chosen bookable unit, set nid to 'all'.
    nid = (nid == '_none') ? 'all' : nid;
    var url = backend_url + '/data/' + type + '/' + nid;

    var settings = {
      type: "GET",
      contentType: "application/json",
      url: url
    };
    var jqxhr = $.ajax(settings);

    jqxhr.fail(function( data ) {
      if(jqxhr.readyState < 4)  {
        console.log( "Request was not completed." );
      }
      else {
        console.log( "In jqxhr.fail(), data: ", data );
      }
    });

    jqxhr.done(function( data ) {
      // console.log('Request success. type, data: ', type, data);
      bookable_data[type] = data;
      // console.log('bookable_data: ', bookable_data);
      $(document).trigger('bookable_data_fetched', type);
    });

    jqxhr.always(function( data ) {
      // notification.remove();
    });

  },


  /**
   *
   */
  validate_promo: function() {
    // If there is no chosen bookable unit, set nid to 'all'.
    var discount_span = promo_field.parents('[class*="field--type"]')
      .find('.discount');

    var promo_code = promo_field.val();
    var checkin_date = arrival_field.val();
    var nights = num_nights_field.val();

    if (promo_code == '') {
      promo_field.attr('is_valid', '');
      is_valid_promo = true;
      discount_span.html('');
      return null;
    }

    promo_field.attr('is_valid', 'loading');

    data_object = {
      field_promo_code_provided:  promo_code,
      field_checkin_date:         checkin_date,
      field_num_nights:           nights
    }

    var settings = {
      type: "POST",
      contentType: "application/json",
      data: JSON.stringify(data_object, null, 2),
      url: drupalSettings.booking_settings['backend_url'] + '/admin/promos/validate/'
    };
    var jqxhr = $.ajax(settings);

    jqxhr.fail(function( data ) {
      if(jqxhr.readyState < 4)  {
        console.log( "Request was not completed." );
      }
      else {
        console.log( "In jqxhr.fail(), data: ", data );
      }
    });

    jqxhr.done(function( data ) {
      // console.log('Request success. data: ', data);

      if (data.is_valid) {
        // alert('Promo code is valid.');
        promo_field.attr('is_valid', 'true');
        is_valid_promo = true;
        discount = data.discount;
        discount_span.html('Discount: ' + discount + '%');
      }
      else {
        // alert('Promo code is NOT valid.');
        promo_field.attr('is_valid', 'false');
        is_valid_promo = false;
        discount_span.html('Discount: 0% (' + data.error_message + ')');
      }
    });

    jqxhr.always(function( data ) {
      // notification.remove();
      refresh_main_info();
    });

  },


  /**
   * Adds a booking to the full-calendar.
   */
  add_booking: function(nid, booking_data) {
    // console.log('nid: ', nid);
    // console.log(nid, ' -> ', booking_data);
    // console.log('events: ', events);

    // Validation

    if (typeof booking_data === 'undefined') {
      return null;
    }
    if (calendar.length === 0) {
      return null;
    }
    if (booking_data.field_checkout_date < booking_data.field_checkin_date) {
      // console.log('Booking "' + nid + '" has an invalid check-out date.');
      return null;
    }

    // console.log('adding');
    // If this is a new event (not yet on the calendar)
    if (typeof events[nid] === 'undefined') {
      // console.log('New event: events[nid]: ', events[nid]);
      events[nid] = {
        id: nid,
        field_booking_status: booking_data.field_booking_status,
        start: moment(booking_data.field_checkin_date, 'X'),
        // End 1 day before checkout date
        end: moment(booking_data.field_checkout_date, 'X'), // - 1*60*60*24
        rendered: false
      }
      // events[nid].end.subtract(1, 'days');
      if (nid === 'current') {
        events[nid].title = 'Booking';
        events[nid].color = '#69C0FF'; // light blue
        events[nid].field_booking_status = 'provisional';
      }
      else {
        events[nid].title = 'Unavailable';
        events[nid].color = '#A8A8A8';
      }
      // console.log('events[nid]: ', events[nid]);
    }

    // If this is not new (is on the calendar), and is the current event
    else if (nid === 'current') {
      // console.log('Is current event: events[nid]: ', events[nid]);
      // Update the dates
      events[nid].event[0].start = moment(booking_data.field_checkin_date, 'X');
      events[nid].event[0].end = moment(booking_data.field_checkout_date, 'X');
      // console.log('events[nid].event[0]: ', events[nid].event[0]);
      // Update the event on the calendar
      calendar.fullCalendar('updateEvent', events[nid].event[0]);
      return null;
    }
    // console.log('events: ', events);

    // Render event on calendar
    if (! events[nid].rendered) {
      // console.log('rendering events[' + nid + ']');
      events[nid]['event'] = calendar.fullCalendar( 'renderEvent', events[nid], true );
      events[nid].rendered = true;
      // console.log('events[nid] after creation: ', events[nid]);
    }
    // console.log('events', events);
  },


  date_unix_to_iso: function(timestamp) {
    // If timestamp is a string, parse it as an integer
    timestamp = (typeof timestamp === 'string') ? parseInt(timestamp) : timestamp;
    var date = new Date(timestamp * 1000);
    return date.toISOString();
  },



  // get_token: function() {
  //   return '';
  // },


  /**
   * Creates a booking object that can be saved to fullCalendar.
   */
  create_booking: function(serialized_form) {
    var data_object_raw = {};
    $.each(serialized_form, function () {
        if (data_object_raw[this.name] !== undefined) {
            if (!data_object_raw[this.name].push) {
                data_object_raw[this.name] = [data_object_raw[this.name]];
            }
            data_object_raw[this.name].push(this.value || '');
        } else {
            data_object_raw[this.name] = this.value || '';
        }
    });
    // console.log('data_object_raw: ', data_object_raw);

    var booking_title =
      data_object_raw['field_first_name[0][value]'] + ' ' +
      data_object_raw['field_last_name[0][value]'] +
      ' (' + data_object_raw['field_num_nights[0][value]'] + ' nights)';

    var data_object = {
      'title': booking_title,
      'field_booking_status':       'provisional', // data_object_raw['field_booking_status[0][value]'],
      'field_paid_status':          'none',
      'field_bookable_unit':        data_object_raw['field_bookable_unit'],
      'field_checkin_date':         data_object_raw['field_checkin_date[0][value][date]'],
      'field_checkout_date':        data_object_raw['field_checkout_date[0][value][date]'],
      'field_first_name':           data_object_raw['field_first_name[0][value]'],
      'field_last_name':            data_object_raw['field_last_name[0][value]'],
      'field_country':              data_object_raw['field_country'],
      'field_email_address':        data_object_raw['field_email_address[0][value]'],
      'field_first_name':           data_object_raw['field_first_name[0][value]'],
      'field_notes':                data_object_raw['field_notes[0][value]'],
      'field_num_guests':           data_object_raw['field_num_guests[0][value]'],
      'field_num_nights':           data_object_raw['field_num_nights[0][value]'],
      'field_phone_number':         data_object_raw['field_phone_number[0][value]'],
      'field_phone_number_alt':     data_object_raw['field_phone_number_alt[0][value]'],
      'field_promo_code_provided':  data_object_raw['field_promo_code_provided[0][value]'],
      'field_base_cost':            data_object_raw['field_base_cost[0][value]'],
      'field_addons':               []
    };
    // console.log('data_object: ', data_object);

    addon_checkboxes = $('input[name^="field_addons"]:checked');
    // console.log('addon_checkboxes: ', addon_checkboxes);

    addon_checkboxes.each(function(index, checkbox) {
      // console.log('checkbox: ', checkbox);
      var nid = $(checkbox).attr('value');
      data_object.field_addons.push(nid);
    });

    return data_object;
  },


  /**
   * @param start_date
   *
   * @param end_date
   *   Moment.js date object.
   */
  calculate_base_cost: function() {
    // console.log('calculate_base_cost()');

    var active_bookable_data = Drupal.abookings.get_bookable_data();
    // console.log('active_bookable_data: ', active_bookable_data);
    if (! active_bookable_data) {
      return null;
    }

    // If there is no current event, quit.
    if ((drupalSettings.route.name == 'abookings.book_page') && (! events.hasOwnProperty('current'))) {
      return null;
    }
    // console.log("events['current']: ", events['current']);

    // start_date and end_date must be Moment.js date objects.

    start_date = moment(arrival_field.val() + ' 09');
    // console.log('start_date: ', start_date);

    end_date = moment(departure_field.val() + ' 09');
    // console.log('end_date: ', end_date);

    var t = new Date(); // Use current day and time
    t.setHours(0,0,0,0); // Set time to 00:00am
    var today = moment(t);
    // console.log('today: ', today);

    var total_cost = 0;
    var num_guests = parseInt(num_guests_field.val());
    var guest_multiplier = num_guests;

    // If bookable uses a flat rate (not dependant on number of guests)
    if (active_bookable_data['field_price_type'] == 'flat') {
      guest_multiplier = 1;
    }

    if (! (start_date.unix() > today.unix())) {
      alert('You cannot book a date in the past.');
      return null;
    }
    if (! (end_date.unix() - start_date.unix() > 0)) {
      alert('The dates you selected for your booking are invalid.');
      return null;
    }


    // Loop through each night of the booking (called an iteration)
    var iteration_date = moment(start_date);
    while (iteration_date.unix() < end_date.unix()) {
      var date_price = Drupal.abookings.get_seasonal_price(iteration_date.unix());
      // console.log('date_price: ', date_price);

      // If bookable has a minimum cost, use it...
      // @todo this ^.

      total_cost += date_price * guest_multiplier;

      iteration_date.add(1, 'day');
    }

    if (total_cost == 0) {
      alert('The booking does not fall within a season.');
    }

    return total_cost;
  },


  /**
   * Determines if the event with specified dates overlaps any other event.
   */
  check_event_overlap: function(date_arrive, date_depart) {
    // console.log('events: ', events);

    // Only works using calendar
    if (calendar.length == 0) {
      return false;
    }

    var overlap = false;
    // console.log('events: ', events);
    $.each(events, function(nid, event_data) {
      // console.log('event_data: ', event_data);
      if (nid === 'current') {
        return false;
      }

      // If event end date is missing, it's a 1-day event.
      if (! event_data.event[0].end) {
        event_data.event[0].end = event_data.event[0].start;
      }
      // console.log('event_data: ', event_data);

      var StartA = date_arrive.unix(),
        EndB = event_data.event[0].end.unix(),
        EndA = date_depart.unix(),
        StartB = event_data.event[0].start.unix();

      // console.log(
      //   date_arrive.format('YYYY-MM-DD'),
      //   event_data.event[0].end.format('YYYY-MM-DD'),
      //   date_depart.format('YYYY-MM-DD'),
      //   event_data.event[0].start.format('YYYY-MM-DD'));

      if ((StartA <= EndB) && (EndA >= StartB)) {
        overlap = true;
      }
    });

    if (overlap) {
      alert('Your booking cannot overlap with another booking.');
    }
    return overlap;
  },


  /**
   * Invokes functions provided in an array. Often used by delayed fuctions
   * like .done() and .always() which rely on HTTP requests.
   *
   * @param callbacks
   *   An array of arrays, in the form [[function, thisobject, [args...]], ...]
   */
  invoke_callbacks: function(callbacks) {
    // If callback functions were provided, invoke them.
    if (typeof callbacks !== 'undefined') {
      // console.log('callbacks: ', success_callbacks, always_callbacks);
      $.each(callbacks, function(key, callback_info) {
        // console.log('callback_info: ', callback_info);
        // console.log('this[1]: ', this[1]);

        // If callback's "this" object wasn't provided, provide a default
        this[1] = typeof this[1] === 'undefined' ? this : this[1];
        // If callback's parameters weren't provided, provide a default of an empty array
        this[2] = typeof this[2] === 'undefined' ? [] : this[2];

        // var this_obj = (typeof callback_info[2] === 'undefined') ? this : callback_info[2];
        this[3] = this[0].apply(this[1], this[2]);
      });
    }
    return callbacks;
  },



  validate_booking_form: function() {
    var is_valid = true;

    if (departure_field.val() <= arrival_field.val()) {
      alert('Departure date must be after arrival date.');
      is_valid = false;
    }
    var base_cost = Drupal.abookings.calculate_base_cost();
    // console.log('base_cost: ', base_cost);
    if (parseFloat(base_cost_field.val()) != base_cost) {
      alert('Base cost is not valid. Please select an arrival and departure date.');
      is_valid = false;
    }
    return is_valid;
  }


}



})(jQuery, Drupal, this, this.document);
