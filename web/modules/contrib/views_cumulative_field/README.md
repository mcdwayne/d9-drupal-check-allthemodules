# Views Cumulative Field

This module creates a Views field handler that enables you to calculate
the per-row cumulative value of another field in your view. This is great
for Views displays created by the Charts module.

## Instructions

After you enable this module (you may need to run cron/clear caches):
1) Create/edit your view.
2) Add a field that outputs numbers. This is considered the "data field"
   for purposes of this module.
3) Add the "Global: Cumulative Field" field (created by this module)
4) In cumulative field's field settings, select the field for which you
   want a cumulative value (the "data field") from the radios.

That's it! The cumulative field should add all the previous rows' values
with the current row's value at each row.

## Credit
**Author:** Daniel Cothran (andileco)
Thanks to Marton Bodonyi (codesidekick)
via "Database independent Views 3 custom field handlers"
(codesidekick.com/blog/database-independent-views-3-custom-field-handlers)
for the majority of the code used in this project. I also received support
from Elias Muluneh.
