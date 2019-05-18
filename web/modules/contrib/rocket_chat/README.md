#_Rocket.chat_ Module for Drupal 8.

CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Recommendations
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers

INTRODUCTION
------------

The Rocket.chat Module enables a drupal site to integrate Rocket.chat.  
It consist of several modules:

 - [Rocket chat](rocket_chat): The base module that holds the configuration.
 - [Live chat](livechat): the Live chat module contains a block you can place to
  have the rocketchat livechat widget on a page you can control as a block. 

 
Requirements
------------

This module is designed for:
 - [Drupal 8](https://www.drupal.org/project/drupal)
 - [Rocket.chat 0.58+](https://rocket.chat/)

It is tested with:
 - Drupal 8.3.7
 - Rocket.chat 0.58.0

Recommendations
---------------

We strongly recommend you run your Drupal and your Rocket.chat behind a TLS 
proxy or webserver with TLS capabilities.  
When using this module beware of HTTPS<->HTTP crossovers, they often just do not
 work and for this reason we recommend you turn both services under TLS.  
In order to use the livechat functionality you need to enable livechat on your 
rocket.chat instance, or you will not see anything.


Installation
------------

- Rocket chat Module: 
  - Install the module in your modules folder.
  - if you have not done so already, setup Rocket chat.   (out of scope for this 
    readme, check out [Rocket.chat](https://rocket.chat) for instructions on how
     to setup 
    rocketchat.)
- Livechat Module:
  - install the livechat module and rocket_chat module.
  - Setup the rocket_chat module.
  - Go to [Structure][Block layout]. there you can place the livechat block 
    using the "Place block" button.
    This works as a normal block we recommend you add it to a footer or alike 
    for performance.   
- Rocket Chat API Module:
  - This module enables you to utilize the Rocket chat API.
- API Test:
  - You can use this module to test the various aspects of the API without 
    having to write all the code to do so.  
    After enabling it its available on the `/apitest` path for rocketchat 
    admins.     

Configuration
-------------

- Configure your rocketchat url in [drupal-url]/admin/config/rocket_chat , you 
  will need to have the proper permissions! 
 
Troubleshooting
---------------
 
Leave a detailed report of your issue in the 
[issue queue](https://www.drupal.org/project/issues/search/2649818) and the 
maintainers will add it to the task list.
  
Maintainers
-----------
 
 - [sysosmaster](https://www.drupal.org/u/sysosmaster) (Current maintainer of 
   rocketchat module on d.o.).
 - [Gabriel Engel](https://www.drupal.org/u/gabriel-engel) (Creator Rocket.chat
   ).
 - [idevit](https://www.drupal.org/u/idevit) (Community Plumbing).
 - [jelhouss](https://www.drupal.org/u/jelhouss) (Initial Module Creator).

Last Updated, 28-August-2017.
