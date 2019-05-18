INTRODUCTION
------------

Give your stakeholders flexibility in topic subscription. The
GovDelivery taxonomy integration module for Drupal automatically creates
subscription topics for every taxonomy term or content tag in select vocabularies
that you create within your Drupal instance. Your website visitors will be able
to subscribe to the taxonomy terms and content tags. When a new page, story, or
custom content type is published with tags, all interested subscribers will be
automatically notified with a DCM Bulletin send.


CONFIGURATION
-------------

Once the module is installed and enabled, the user must go to the Configure
screen and enter relevant GovDelivery information to activate the module:

1. Web Services Administrator Username

2. Web Services Administrator Password

3. GovDelivery DCM Client Account Code

4. GovDelivery API URL (Without HTTPS://)

5. Drupal Instance Base URL (Without HTTPS://) (This is the URL to this Drupal instance without any subdirectories)

6. GovDelivery DCM Parent Category Code for Created Topics

7. Edit the selected vocabulary and choose a GovDelivery category to associate
it with. If a vocabulary isn't associated with a category no topics will be
created for terms in the vocabulary.

8. Edit terms in your selected ovocabularies and associate them with a predefined
GovDelivery Topic, or by default on form submission a new topic in the chosen
category will be automatically created.

OPERATING DETAILS
-----------------
Once activated, the GovDelivery taxonomy module creates a Topic
in the GovDelivery platform for every taxonomy term or vocabulary
created in Drupal. All Drupal terms in selected vocabularies are assigned
to a Category within GovDelivery, specified in the configuration screen.
When the term is created in Drupal, the module uses the GovDelivery Create Topic API
to create the corresponding GD Topic and assign it to the specified category.

Drupal Taxonomy vocabularies are associated with GovDelivery Categories and Terms
are associated with GovDelivery Topics. Only specified vocabularies are created
along with terms in those vocabularies.

A block is provided which gives the user's links to sign up for specific topics
on a node page where the terms are associated with GovDelivery Topics. The block
provides a fieldset with a customizable title and description. The link text is
also customizable.


SUPPORT
-------

For help in configuring the module or for any other questions please
contact Govdelivery Customer Support by emailing help@govdelivery.com

If you are a government or transit organization and would like to learn
about GovDelivery and its products, please email:
info@govdelivery.com or navigate to www.govdelivery.com
