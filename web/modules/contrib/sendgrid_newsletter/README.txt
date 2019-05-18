This is a module that creates a form in a modal 
that allows users to subscribe to a newsletter.
The form has 3 fields:

    First Name
    LastName
    Email

It subscribes the user via Ajax to a SendGrid 
(https://sendgrid.com) newsletter list.

The module has a configuration page that allows admins
to enter SendGrid API keys, to select which newsletter
lists should the visitor be subscribed to when they submit 
the subscription form and an ability to define paths where 
the modal will be displayed.

How to use.

Enable the module. Refer to the documentation for help Here.

Define the permissions from /admin/people/permissions 
(Permission name is: Administer SendGrid Newsletter module settings).

Configure the settings from admin/config/sendgrid-newsletter-config

This module has a dependency: Ctools
