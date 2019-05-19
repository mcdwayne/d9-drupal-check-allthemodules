
CONTEXTUAL RANGE FILTER (D8)
============================

This is a simple plugin for Views that adds the option to contextually filter
a view not just by a single value, but also by RANGE.

Just like normal contextual filters, contextual range filters may be set by
appending filter "arguments" to the URL. Examples follow below.

Integer, float, string and date ranges are supported, as well as lists.

Node ids etc are special cases of integers so will work also.

You may use the OR ('+') and the negate operators. You negate by ticking the
"Exclude" box on the Views Contextual filter configuration panel, in the "More"
section.

To create a contextual range filter, first create a plain contextual filter as
per normal. I.e. in the Views UI open the "Advanced" field set (upper right) and
click "add" next to the heading "Contextual filters". On the next panel select
the field or property that needs to be contextually filtered and "Apply". Fill
out the configuration panel as you see fit, press "Apply" and "Save" the view.

Now visit the Contextual Range Filter configuration page,
admin/config/content/contextual-range-filter, find your contextual filter name
and tick the box left of it to turn the filter into a contextual range filter.
Press "Save configuration".

You apply contextual filters by appending "arguments" to the view's URL.
Using the double-hyphen '--' as a range separator, you can filter your view
output like so:

  http://yoursite.com/yourview/100--199.99  (numeric range)
  http://yoursite.com/yourotherview/k--qzz  (alphabetical range)
  http://yoursite.com/somebodysview/3--6    (list range, using list keys)
  http://yoursite.com/somebodysview/child--middle-aged (list range using names
   is NOT supported in D8)

As can be seen from the examples above, for lists you should use the keys in
the "Allowed values list" on the Field settings page,
admin/structure/types/manage/<content-type>/fields/<field>/field-settings.
Example:

  1|Baby
  2|Toddler
  3|Child
  4|Teenager
  5|Adult
  6|Middle-aged
  7|Retiree

The list range order is the order of the allowed values. As far as the
Contextual Range Filter module is concerned list keys need not be consecutive,
e.g. "1,2,6,26" is fine.

All ranges are inclusive of "from" and "to" values.

Strings will be CASE-INSENSITIVE, unless your database defaults otherwise. In
your database's alphabet, numbers and special characters (@ # $ % etc.)
generally come before letters , e.g. "42nd Street" comes before "Fifth Avenue"
and also before "5th Avenue". The first printable ASCII character is the space
(%20). The last printable ASCII character is the tilde '~'. So to make sure
everything from "!Hello" and "@the Races" up to and including anything starting
with the letter r is returned, use " --r~".

You may omit the start or end values to specify open-ended filter ranges:

  http://yoursite.com/yourview/100--

Multiple contextual filters (e.g. Title followed by Price) are fine and if you
ticked "Allow multiple ranges" also, you can use the plus sign to OR filter
ranges like this:

  http://yoursite.com/yourthirdview/a--e~+k--r~/--500

Or, if your view has "Glossary mode" is on, so that only the first letter
matters, the above becomes:

  http://yoursite.com/yourthirdview/a--e+k--r/--500

You may use a colon ':' instead of the double hyphen.
Use either '--', ':' or 'all' to return all View results for the associated
filter:

  http://yoursite.com/yourthirdview/all/-100--999.99

You can opt to have URL arguments validated as numeric ranges in the Views UI
fieldset titled "When the filter value IS in the URL ...". Tick the "Specify
validation criteria" box and select the "Numeric Range" validator from the
drop-down. Just like core's "Numeric" validator, the "Numeric Range" validator
must not be selected if the "Allow multiple numeric ranges" box is ticked.
Instead select "-Basic Validation-".


ASCII AND UTF CHARACTER ORDERING
o http://en.wikipedia.org/wiki/UTF-8
