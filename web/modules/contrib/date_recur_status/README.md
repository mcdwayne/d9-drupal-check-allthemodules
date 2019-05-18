# Date Recur Status

An extension to [Date Recur](https://drupal.org/project/date_recur) that allows
to set a status per occurrence.

Install as usual. Create a new field of type _Date Recur_. Select
_Status enabled occurence handler_  as occurrence handler in the field settings
and _Date recur status formatter_ as formatter in the display settings.

Open `/admin/config/content/date_recur_status` to configure the list of statuses.

Content having status enabled recurring dates has a new tab _Occurences_
providing a form to change the status per occurrence.

The module provides optional integration with _Search API_ to skip occurrences
with selected statuses from being indexed.