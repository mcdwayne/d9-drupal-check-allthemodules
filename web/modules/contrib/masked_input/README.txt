CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Usage
 * Advanced


INTRODUCTION
------------

Sometimes you need the user to input data in a particular format like a Social
Security Number or a standard US phone number. By masking input of a particular
textbox, you can change its behavior so that it accepts input only according to
specified format, e.g. a masked phone number input box will only allow 10 digits of
of the phone number to pass through and won’t accept any other input.

The Masked Input module is a wrapper for the Masked Input jQuery Plugin by Josh Bush,
http://digitalbush.com/projects/masked-input-plugin. It allows a user to more easily
enter fixed width input where you would like them to enter the data in a certain
format (dates, phone numbers, etc).

INSTALLATION
------------

Enable the modules from the Administration >> Modules page.

USAGE
-----

The Masked Input module is used in Content Type design when adding a Text field. From the
Widget selection, choose Masked Input and click Save. After defining the maximum length and saving
again, you will arrive at a page for more detail on your new field. Define the mask in the Mask
field.

Example: Go to the Manage Fields form for Basic Page (Or create a new content type, and go to Manage
Fields).

1) Add a new field called Phone Number.
2) From the Select 'Add a new field' select Text (Fromatted) or Text (Fromatted,long).
3) In the Label type "Phone number".
4) Click Save and continue.
5) On the next page, accept the default field length, and click Save.
6) After you get message "Saved phone number configuration." then click on "Manage form display"
7) Go the Phone number field and select "masked_input" in Widget select dropdown
8) Then click on settings icon in same row.
9) In the Mask field, type
         (999) 999-9999
8) clik update
9) Then Save settings

When you create a new node of type you created or altered, the Phone Number field will have the following:
(___) ___-____
The field will only accept three numerals inside the parentheses, three numerals before the dash,
and four numerals after the dash. It will not accept letters, only numbers. That is because of the
mask we created in step (7).

ADVANCED
--------

You can create new Mask Defenitions at Administration » Configuration » User interface » Masked Input
(admin/config/masked_input).
