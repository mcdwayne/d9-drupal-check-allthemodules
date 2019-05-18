
Contact Storage Export Module Readme
----------------------------------

This module provides a simple export operation automatically for messages from
each created contact form. It exports all fields and base data about the
submission (such as logged in user and date submitted). It also handles fields
that allow multiple options to be selected. This module requires Contact
Storage.

Installation
------------

To install this module, place it in your modules folder and enable it on the
modules page.


Configuration
-------------

There is nothing yet to configure. I would welcome suggestions as to what sort
of configuration would be useful for your various use cases. If you wish to
allow roles other than administrators to export form submissions, you should
give them the 'Export contact form messages' permission.


How to use this module
-------------------------------------

Users with the 'Export contact form messages' will find an Export operation in
the list of operations on the page containing the list of all contact forms.
You can find that at `/admin/structure/contact`.


Alternatives to this module
--------------------------

You can set up individual views using the `Views Data Export` project. Both use
the `CSV Serialization` project under the hood. This module differs in that you
can allow editors to set up forms and the export functionality will
automatically be available, while the Views method requires you set up a view
for each newly created form. At the moment the Views method will allow further
control over the output (but feature requests are welcome).


Feedback on this module
-----------------------

Please add issues with feature requests as well as feedback on the existing
functionality. This is a first stab at it.

TODO
----

Allow control over which columns are exported
Allow export to a file, potentially on a scheduled basis
Allow export of new submissions since last export
Give control over default options via config
