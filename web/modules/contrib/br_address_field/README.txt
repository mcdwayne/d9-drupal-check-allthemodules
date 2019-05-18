CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This defines a new field type to store brazilian postal adresses according to
recommendations of the brazilian postal service company, Correios. The address
consists of the following fields:

 * Thoroughfare (Logradouro): type and name of the thoroughfare
 * Number (Número): the number of thoroughfare
 * Complement (Complemento): apartment number and/or another info
 * Neighborhood (Bairro)
 * City (Cidade)
 * State (Estado)
 * Postal code (CEP)

  * For a full description of the module, visit the project page:
    https://drupal.org/project/br_address_field

  * To submit bug reports and feature suggestions, or to track changes:
    https://drupal.org/project/issues/br_address_field

REQUIREMENTS
------------

This module requires the following modules:

 * commerce (https://www.drupal.org/project/commerce)
 * cpf (https://drupal.org/project/cpf)

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------

After installed, go to "Manage Fields" page of choosed entity, normally, user
(admin/config/people/accounts/fields), and click Add field. Add a new field
Brazilian address.

MAINTAINERS
-----------

Current maintainers:

 * Bruno de Oliveira Magalhaes (bmagalhaes) - https://drupal.org/user/333078
 * Ronan Ribeiro (RonanRBR) - https://drupal.org/user/332925

This project has been sponsored by:

 * 7Links Web Solutions
   Specialized in consulting and planning of Drupal powered sites, 7Links
   offers installation, development, theming, customization, and hosting
   to get you started. Visit https://7links.com.br for more information.
