Commerce postcode delivery
--------------------------


The module provides a shipping method called Commerce postcode delivery. You
can use this shipping method multiple times to create shipping or delivery
charges based on time, distance, etc.

Lets take an example of one such use case where a store needs to provide
following shipment methods. Its quite possible that the store only provides
2 hour and 4 hour delivery to nearby areas only. The shipping charges are :

* 2 hour delivery - $30
* 4 hour delivery - $20
* 24 hour delivery - $10
* Normal delivery (2-3 days) - $5
* Pick-up from shop - $0

Here, create 4 Commerce postcode delivery shipping methods, each having a CSV
file in the format as shown below. Each CSV file must have exactly same postal
codes, that is if a store can deliver to a postal code V1X 165 in 2 hours, or
4 hours time, the CSV files uploaded to these two shipping methods must have a
row for V1X 165 with their corresponding shipping charge. For any shipping
charge not applicable to a service area or a postal code, it will be hidden
from the customer on checkout page.


For sample CSV format, refer:
/admin/help/commerce_postcode_delivery

After uploading the CSV, you can preview it right in the config itself.
With this module you can set up dynamic, ever evolving shipping rate charges.

A note for Developers:
The service CsvManager can be extended to suit any other use case you need to
implement. If you want to match only first 3 characters, (lets say) for a
postal code:
- Extend the service CsvManager
- then use setRelevantMatchingChars() to set.


Module maintainer
-----------------
 - gauravjeet (https://www.drupal.org/u/gauravjeet) @ Acro Media Inc.
