
About
-----
The Commerce guest registration is an add-on module for the Commerce 2.x.
The module won't have any configuration and permission implementations.
This module help to create the user account in the system while they are
placing orders as guest users. The user email id is considering the
unique parameter to create the account.

In commerce guest checkout process we collecting the email id of the
end user. From email id, we run a test in the database any user exists with
the same id. If yes, the order will map to existing user. Otherwise, a new
user account will create and mapped to order. For new user account
creation this module will generate the unique username and send the
One-Time-Login link to the respective email id.

Installation
------------
Install the module using standard module installation.

Usage
-----
The module won't have any special configuration and permission implementations.

Requirement
-----------
Drupal commerce 2.x
