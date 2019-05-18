
---SUMMARY---

This module uses the Byteplant(https://www.email-validator.net/) service to verify if an email address entered into a
form on your website really exists and can receive mails.

For each email invalid address you get a detailed validation result with the exact status.



---REQUIREMENTS---


*None. (Other than a clean Drupal 8 installation)


---INSTALLATION---


Install as usual.

Place the entirety of this directory in the /modules folder of
your Drupal installation.

Navigate to Administer > Extend.

Check the 'Enabled' box next to the 'Byteplant email validation'.

Click the 'Save Configuration' button at the bottom.

---CONFIGURATION---

Go to admin/config/development/email-validation.

Set key from (https://www.email-validator.net/) and the
forms id where you want to apply the validations.

If you want custom error message instead of Byteplant
status message please fill "Custom invalid email message".


---CONTACT---

Current Maintainers:
*benellefimostfa - https://www.drupal.org/u/benellefimostfa


This project has been sponsored by:

*Amazeelabs - https://www.drupal.org/amazee-labs
