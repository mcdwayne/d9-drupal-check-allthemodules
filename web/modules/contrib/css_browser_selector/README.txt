
-- SUMMARY --

CSS Browser Selector automatically includes browser/OS-specific CSS selectors in your HTML.

For a full description visit the project page:
  http://drupal.org/project/css_browser_selector
Bug reports, feature suggestions and latest developments:
  http://drupal.org/project/issues/css_browser_selector


-- REQUIREMENTS --

* None


-- INSTALLATION --

Install as usual, see http://drupal.org/node/70151 for further information.


-- KNOWN ISSUES --

If everything is enabled and you're still not seeing the browser/OS classnames
in your body HTML tag, this is most likely because you are using the Garland
or similar theme where it does not output this variable.

The simple solution: modify your page.tpl.php file so that the <html> tag now contains this variable, like so:
  <html class="<?php print $body_classes; ?>">
  
The more advanced solution is to write a custom theme function to replace what currently outputs the CSS in your body tag.


-- CREDITS --
Original Javascript concept by Rafael Lima http://rafael.adm.br/css_browser_selector.
PHP port by Bastian Allgeier http://bastian-allgeier.de.


-- CONTACT --

Current maintainers:
Jason Trobman (jwhat)
