
-- SUMMARY --

Provides a drupal admin side interface to manually purge Acquia
Cloud varnish cache using the Acquia  Cloud API. It uses Acquia 
Cloud API credentials and validates those credentials in configuration. 
It flush varnish cache in domain level. It saves developer's time 
while implementation.

For a full description of the module, visit the project page:
  http://www.drupal.org/project/acquia_flush_varnish
-- REQUIREMENTS --

None.


-- INSTALLATION --

* Install as usual, see http://drupal.org/node/1897420 for further information.

-- CONFIGURATION --

* Add you acquia cloud API credentials in Administration » Configuration 
  » Acquia cloud API Credentials.

FAQ
---

Q: How do I get Acquia cloud API credentials ?

A: Login at https://accounts.acquia.com/account this URL using 
your Acquia account credentials. After login go to credentials tab. 
In credentials tab we have Acquia cloud API credentials (E-mail and Private Key)
below "Cloud API".

-- CONTACT --

Current maintainers:
* Buvaneswaran (buvan) - http://www.drupal.org/u/buvanesh.chandrasekaran
