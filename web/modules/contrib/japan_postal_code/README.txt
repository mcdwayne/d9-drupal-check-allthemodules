This module provides Japan postal code data and API for them. This fetches
the csv file from Japan post office website and insert it into the local
database table. After fetching the data, developers can make a postal code
related feature such as address autofill without depending on any external
service.

For now, this fetch process may fail with timeout error because it takes a so
long time (over 1 minutes depending on the environment) but it doesn't split
the process into queue tasks.

This module is only for developers. It provides only several simple APIs and
doesn't provide any end-user functionality.


Similar projects
----------------

- Addressfield - Japanese Postal Code
  (https://www.drupal.org/project/addressfield_jp) - This module uses an
  external service "ajaxzip3" and provides an end-user functionality with
  Address Field (https://www.drupal.org/project/addressfield) module.


Installation
------------

1. Enable this module.
2. Go to the admin page `admin/config/services/japan-postal-code` and click the
  button in the page to fetch Japan postal code data from Japan post office
  website. This process takes a long time because it has large amount of data
  (over 100,000). Actually the file size is not so large but inserting them
  into the database table takes a long time.
3. Then, you can start to use the Japan postal code data with API. For detail
  on the API, please see below.


Usage
-----

This module provides 2 ways to use postal code data.

1. A function
2. A request handler

First one, a function is named `japan_postal_code_get_address_by_postal_code()`.
This is for backend developers. It takes a 7-digit postal code in Japan (it may
contain `-` like 100-0001) and returns the address related with the postal
code. The address is a `stdClass` object with 5 members: postal_code,
prefecture, city, address_under_city and name. For example,
`japan_postal_code_get_address_by_postal_code('1000001')` returns the following
object loaded from the postal code database table.

<code>
object(
  "postal_code": "1000001",
  "prefecture": "東京都",
  "city": "千代田区",
  "address_under_city": "千代田",
  "name": "",
)
</code>

In the above example, the `name` is empty. `name` is a member only for special
address. In Japan, limited organizations have unique postal code and `name`
represents the name of organization in the case such postal code is passed.
If invalid postal code is passed, the returned value is FALSE.

Second one, a request handler is set for the path
`admin/config/services/japan-postal-code/%`. This is mainly for front-end
developers. This page takes a postal code in the path and returns a json output
containing an address data which matches the postal code.

For example, if one who has the above permission visits the page with path
`admin/config/services/japan-postal-code/1000001`, Drupal returns a json output
like:

<code>
{
  "status": true,
  "data": {
    "postal_code": "1000001",
    "prefecture": "東京都",
    "city": "千代田区",
    "address_under_city": "千代田"
  }
}
</code>

In the above example, `status` is the result status for the request. If the
status is `true`, it means the matched address is found. On the other hand,
`false` status represents the postal code is invalid or no matched address is
found. The value of the member `data` is same as the returned value of the
function `japan_postal_code_get_address_by_postal_code()`.


Test
----

You can see this functionality easily by creating a node with body
`full_html` format and pasting the following code in the body:

<code>
<form id="myform" action="">
  <input type="text" name="postalcode" id="myform-postalcode"
    placeholder="postal code">
  <button type="button" id="myform-autocomplete">autocomplete</button>
  <input type="text" name="address" id="myform-address" placeholder="address">
</form>

<script>
$ = jQuery;
$('#myform-autocomplete').click(function(){
  var postalcode = $('#myform-postalcode').val();
  $.getJSON('/japan-postal-code/address/' + postalcode, null, fillInAddress);
  return false;
});

function fillInAddress(data) {
  if (data.status) {
    var d = data.data;
    var address = d.prefecture + d.city + d.address_under_city + d.name;
    $('#myform-address').val(address);
  }
}
</script>
</code>

This one is a test node for the request handler. If you also want to test the
function `japan_postal_code_get_address_by_postal_code()`, please try using it
in `drush php` or similar functionality.
