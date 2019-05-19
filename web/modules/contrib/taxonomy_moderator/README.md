# Taxonomy Moderator module for Drupal 8

 * This modules allows to create a basic approval process for taxonomy terms.
 Taxonomy terms will be only created to the respective vocabulary after the
 approval. This module has a dependency of Taxonomy modules which is in core.

# You use it in Scenario's like this.

 * If you needs any approval process for taxonomy term to be created.

# Dependency

 * Requires: Taxonomy, Node, Text, Field, Filter, User, System modules.

# Installation

 * To use this module enable it under "Extend".

# Permissions

 * Manage the permissions under "admin/people/permissions" and check the role
 you want to provide "Term approval access".

# Usage

 * Add a field type "Taxonomy moderator field" and configure the field setting
 for the same. Make sure that you have the taxonomy field in the same content 
 type configured with the same vocabulary.

 * All the suggested terms will get listed under "admin/content/term_approve"
 which will provide the user an option to approve it.

 * After the approval of a term will get added to the respective vocabulary and
 the field that content type has.

# Future Enhancements

 * Mail notification on Suggest and the Approval.

# Author

 * Anishnirmal - https://www.drupal.org/u/Anishnirmal
