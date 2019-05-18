Kashing Payments README file

INTRODUCTION
-----------------------------
Provides Kashing Payments integration with Drupal. Start accepting secure 
card payments instantly with the Kashing module on your website. 
Its quick and easy to set up. You can do it yourself in no time!
* https://www.kashing.co.uk/
Project page:
* https://www.drupal.org/project/kashing
Please submit any bug reports:
* https://www.drupal.org/project/issues/kashing


REQUIREMENTS
-----------------------------
This module requires the following:
* Submodules of Drupal Commerce package (https://drupal.org/project/commerce)
  - Commerce core
  - Commerce Payment (and its dependencies)
* Stripe PHP Library (https://github.com/stripe/stripe-php)
* Stripe Merchant account (https://dashboard.stripe.com/register)


INSTALLATION
-----------------------------

* Login to your page as an administrator and navigate to Extend menu.
* Choose the +Install new module option. You will be redirected to module 
installation page.
* Either upload previously downloaded module .zip file or just paste the 
module URL. You can also just unzip the kashing module folder and place it 
directly in /modules/ directory of your page.
* Go back to Extend menu. In a Filter by name or description field type in 
Kashing or scroll the page down to the Kashing module section.
* Activate selected module elements (Kashing itself is the main one and is 
required by the other elements) by marking them out and click an Install button.
* If you are not able to mark any of the module elements verify the required 
modules (click on the module description).
* After the CKEditor button or Shortcodes module selection make sure to follow
up the instructions under Activating shortcodes functionality and/or 
* Activating CKEditor button functionality section of this page.
* Do not forget to clear cache.
* After the installation, selected module elements should be available.

Please visit:
* www.drupal.org/project/kashing 
to see full instalation guides.

CONFIGURATION
-----------------------------
* Navigate to Extend/Kashing and under the module details click on Configure 
button. Alternatively go to Administration page and you will find 
Kashing Payments Settings under the Index tab.
* Click the Retrieve Your Kashing API Keys link (Configuration tab)  that will 
take you to the latest version of Kashing documentation where you 
will be explained how to retrieve the module configuration data.
* After you receive the configuration data, fill all 4 fields in the 
Configuration tab: Test Merchant ID, Test Secret Key, Live Merchant ID, 
Live Secret Key. The first two are going to be used only if the 
Test Mode is enabled. When youre done, click Save Settings button.
* For test purposes, it is recommended to use the Test Mode.
* To change the currency or to create success and failure pages select 
the General tab.
* Choose your desired currency from the Choose Currency dropdown menu.
* Design the look of both the success and failure page in a standard 
way using CKEditor.
* Once again save changes.

HOW IT WORKS
-----------------------------
Project consist of three modules:
* Kashing - the main standalone module providing Kashing payments functionality. 
It allows to configure the payments (API keys) and to create payment 
form in a separate block.
* Kashing Shortcodes - provides Kashing shortcode tag functionality. 
Just put down [kashing id=[ID] /] in your node body, where [ID] refers to 
previously created form ID (block machine name). Note: the shortcodes
 module is required.
* Kashing CKEditor button - adds the Kashing shortcode button to CKEditor, 
which allows to easily choose one of the created forms and 
place a shortcode directly in selected node.
