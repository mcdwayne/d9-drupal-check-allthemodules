CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Configuration
 * Future Work
 * Maintainers

INTRODUCTION
------------

Jeditable(jEditable inline editing module) provides "click to edit" functions
for "text", "text_long", "text_with_summary", "number_integer",
"number_decimal", "number_float" for nodes.

So what is this good for? it provides a very quick and easy way to update the
things they need to change without having to edit specific fields without
having to switch to the full-blown node editor.

INSTALLATION
--------------------------

Install the module like any other Drupal module.
To make use of it you will need to create a GitHub application at
https://github.com/account/applications/new.
It is important to set the correct URLs here or else the module won´t work.
Main URL: http://<yourdomain.com>/
Callback URL: http://<yourdomain.com>/github/register/create

CONFIGURATION
--------------------------

1. Turn on the "use jeditable" permission for all rolls you want to have access
 to jeditable operations.
The module respects basic node access, so saving using jeditable will only
work if the user has "update" permissions on the node.
However, if they don't have update permissions, they will still get the
jeditable input forms, so this needs some thought for your application.

2. Go to the "display settings" of your node, or into the display settings for
a view with fields in it and enable the jEditable textfield, jEditable
textarea, jEditable datetime or jEditable noderefence fields as appropriate.
You can also use the computed_field module to get this to show up as a field
in views and elsewhere.

3. Finally, load a node, "click to edit", and enjoy!

FUTURE WORK
---------------------
* Jeditable display for select list, checkboxes.
* Jeditable display for workflow mode.

Maintainers
---------------------

nehajyoti (Jyoti Bohra)
