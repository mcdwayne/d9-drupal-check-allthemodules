The Simple LDAP project is a set of modules to provide Drupal integration with
an LDAPv3 server. It is an alternative to the Lightweight Directory Access
Protocol (LDAP) module, with a much narrower set of features. The goal of the
project is to provide very basic LDAP functionality which should cover most
common use cases. Any edge case functionality or site-specific requirements
should be implemented using a helper module.

The current implementation was developed against OpenLDAP, with some testing
against Active Directory. Most functionality should work with any LDAPv3
compliant server, but this is largely untested.

Currently, the 8.x-1.x branch consists of one main module and one submodule.

Simple LDAP
===========

This is the main module, on which all of the other modules are based. It
provides an interface to the configured LDAP directory with basic low-level
LDAP functions and no bells or whistles. It does not provide anything to
Drupal on its own.

Simple LDAP User
================

This module allows authentication to the LDAP directory configured in the
Simple LDAP module. It also provides synchronization services both to and from
LDAP and Drupal. It supports mapping LDAP attributes to Drupal user object
fields (both native, and using Field API).

===============================================================================

DEVELOPERS:

Enable debugging using devel module by adding the following setting to
settings.php

$conf['simple_ldap_devel'] = TRUE;

Vagrant
=======

There is a Vagrantfile included that will build a VM with a working LDAP
directory and a fresh Drupal installation with simple_ldap installed. If OS X
is the Vagrant host, then the vagrant box is available at simpleldap.local.
For other operating systems, the IP address will need to be obtainted manually,
and added to the local hosts file for best results.

Drupal
------

The Drupal installation is set up with the following credentials:

http://simpleldap.local/

username: admin
password: admin

LDAP
----

The LDAP is pre-populated with some dummy data

ldap://simpleldap.local

DN: cn=admin,dc=local
password: admin

DN: cn=ldapuser,ou=people,dc=local
password: ldapuser

phpLDAPadmin
------------

phpLDAPadmin is available at http://simpleldap.local/pma
