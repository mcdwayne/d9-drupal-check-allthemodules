Contact storage options email recipient
---------------------------------------

The <a href="https://www.drupal.org/project/contact_storage">contact_storage</a> module supplies an 'Options email' field type which can be used to determine the recipient of the contact form. When an 'Options email' field is added to a form, you are still required to enter a recipient's e-mail address on the contact form edit page. This results in an e-mail sent to the entered recipient address <b>and</b> the recipient selected in the 'Options email' field.

This module removes the recipient field when an required 'Options email' is added to the form and places a notification on top of the edit page: "The recipient of this form is determined by the '[field name]' field.". When a non-required 'Options email' field is added to the edit page: "An optional additional recipient of this form is determined by the '[field name]' field."