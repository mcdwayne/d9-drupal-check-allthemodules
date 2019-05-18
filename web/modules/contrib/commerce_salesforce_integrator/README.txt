CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Drupal Commerce SalesForce Connector App
* Maintainers


INTRODUCTION
------------

The Commerce Saleforce Connector module that connects Drupal Commerce with the Salesforce App .

 * For a full description of the module visit
   https://www.drupal.org/project/commerce_salesforce_connector

 * To submit bug reports and feature suggestions, or to track changes visit
   https://www.drupal.org/project/issues/commerce_salesforce_connector


REQUIREMENTS
------------

This module requires Commerce module and additional modules of Drupal core.


INSTALLATION
------------

 * Install the Commerce Saleforce Connector module as you would normally install a
   contributed Drupal module. 
	-> Using Drush:
	       $ drush en --yes commerce_salesforce_connector

1. Consider your base URL as : http://yourbaseURl.com

2. The SalesForce Id field will be added to User, Default Product Variation Type of Product Variation Types, Default Order Type of Order Types.

>>>> To add SalesForce Id field to other Production Variation Types  :-

Goto http://yourbaseURl.com/admin/config/product-variation-types and in each of the Product Variation Type except Default. Goto /edit/fields and add a field with Configuration as :-
LABEL :- SalesForce Id
MACHINE NAME:- field_salesforce_id
FIELD TYPE :- Text (plain)

>>>> To add SalesForce Id field to other Order Types  :-

Goto http://yourbaseURl.com/admin/commerce/config/order-types and in each of the Order Type except Default. Goto /edit/fields and add a field with Configuration as :-
LABEL :- SalesForce Id
MACHINE NAME:- field_salesforce_id
FIELD TYPE :- Text (plain)

This will add the SalesForce Id field to each of the Product Variation Type and Order Type respectively.

CONFIGURATION
-------------

Add Security Key for safe connection:-      

Goto Manage > Configuration > Development > SalesForce Connector Settings Or "http://yourbaseURl.com/admin/config/commerce_salesforce_settings" and Add the securityKey .

DRUPAL COMMERCE SALESFORCE CONNECTOR APP
----------------------------------------

>> This is the url of SalesForce Package for Drupal Commerce SalesForce Connector App installation:
https://login.salesforce.com/packaging/installPackage.apexp?p0=04t6F0000020Uuy 

>> After Installing the App Goto Admin Settings and 
HOST :- http://yourbaseURl.com
Key  :- Security Key mentioned in configuration of commerce_salesforce_settings.

>> Also Add host url to Remote Site Settings.

>> This App will run three crons for User, Product and Order at 50th , 55th and 59th minute of every hour respectively to fetch data from drupal site and save it to SalesForce App and salesforce Id of each field will be updated in the Drupal site.



MAINTAINERS
-----------

 * Ebizon Net Info Pvt Ltd - https://ebizontek.com/
