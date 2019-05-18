@api @drupal
Feature: Config Blocking

  Background:
    Given I am installing the following Drupal modules:
      | client_config_care |
      | config_filter      |

  Scenario: I am modifying config as a client and I do not want to loose it on config import
    Given I am logged in as a user with the "administrator" role
    Then I execute shell command "drush cex -y"
    And I am on "/admin/config/system/site-information"
    And I fill in "site_name" with "Some site name"
    And I press "Save configuration"
    And I am on "/admin/config/system/site-information"
    And the "site_name" field should contain "Some site name"
    Then I execute shell command "drush cim -y"
    And I am on "/admin/config/system/site-information"
    And the "site_name" field should contain "Some site name"

  Scenario: I am modifying site name, deleting all blockers and expect config import
    Given I am logged in as a user with the "administrator" role
    And I am on "/admin/config/system/site-information"
    And I fill in "site_name" with "Some site name"
    And I press "Save configuration"
    And I am on "/admin/config/system/site-information"
    And the "site_name" field should contain "Some site name"
    Then I execute shell command "drush cex -y"
    And I am on "/admin/config/system/site-information"
    And I fill in "site_name" with "Test site name"
    And I press "Save configuration"
    Then I execute shell command "drush cim -y"
    And I am on "/admin/config/system/site-information"
    And the "site_name" field should contain "Test site name"
    Then I execute shell command "drush client_config_care:delete_all_blockers -y"
    And I execute shell command "drush cim -y" and expect output contains "system.site"
    And I am on "/admin/config/system/site-information"
    And the "site_name" field should contain "Drush Site-Install"

  Scenario: Config blocker entities listing contains necessary column labels
    Given I am logged in as a user with the "administrator" role
    And I am on "/admin/structure/config_blocker_entity"
    Then I should see HTML content matching "<th>Name</th>"
    Then I should see HTML content matching "<th>Entity ID</th>"
    Then I should see HTML content matching "<th>User operation</th>"
    Then I should see HTML content matching "<th>Created by</th>"
    Then I should see HTML content matching "<th>Changed by</th>"
    Then I should see HTML content matching "<th>Changed date</th>"
    Then I should see HTML content matching "<th>Created date</th>"
    Then I should see HTML content matching "<th>Operations</th>"

  Scenario: As an user I am deleting a content type field and expecting a config blocker entity with delete user operation
    Given I am logged in as a user with the "administrator" role
    And I am on "/admin/structure/types/manage/article/fields"
    And I should see text matching "Tags"
    And I should see HTML content matching "/admin/structure/types/manage/article/fields/node.article.field_tags/delete"
    And I am on "/admin/structure/types/manage/article/fields/node.article.field_tags/delete"
    And I should see text matching "Are you sure you want to delete the field Tags?"
    And I submit the form
    Then I should see text matching "The field Tags has been deleted from the Article content type."
    And I am on "/admin/structure/types/manage/article/fields"
    And I should not see text matching "Tags"
    And I am on "/admin/structure/config_blocker_entity"
    And I should see text matching "field.field.node.article.field_tags"
    And I should see text matching "field.storage.node.field_tags"
    Then I proof that config blocker with name "field.field.node.article.field_tags" exists with user operation "delete"
    Then I proof that config blocker with name "field.storage.node.field_tags" exists with user operation "delete"

