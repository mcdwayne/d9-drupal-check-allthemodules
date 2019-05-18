-- SUMMARY --

Domain finder, a drupal module which creates a new block to search domain names
and generate results showing whether they are free or unavailable.

-- REQUIREMENTS --
Requires GPL licensed phpWhois library for whois servers list, and querying.

-- PRE INSTALLATION --
This needs just if not composer installed the drupal core.
Install phpWhois.
In command line run on project root folder:
php composer.phar require "jsmitty12/phpwhois":"^5.0"

-- INSTALL DOMAIN FINDER --
- After end previous process without any error phpWhois library is ready to use.
- Enable the domain finder module. 
- Add and configure domain finer block to regio on Block layout page.
  (admin/structure/block)
