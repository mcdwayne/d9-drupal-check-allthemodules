The Typekit Integration module is a lightweight module
to integrate Typekit with Drupal.

It comes with a simple configuration page to set your Typekit Kit ID
and outputs the required code to use Typekit on your site.

*Typekit Setup*

- Login or create an account at typekit.com
- You will want to setup a new kit.
  - Kits are groups of fonts that will be packaged and distributed over a CDN.
  - A kit lets you configure the fonts, selectors, and other settings
    that Typekit will apply to your web pages.
- You will need to add a descriptive name.
- Then add what domains will be able to use your kit.
- Once your kit is saved, it should give you a Kit ID.
  - This will be a 7 digit code that will be entered into the module config page.

*Module Setup*
- Enable the Typekit Integration module
- Navigate to: /admin/config/services/typekit
- Enter the Kit ID from your newly created Typekit