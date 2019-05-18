@api @drupal
Feature: Redirect
  Automated tests for the Permissions by Term Redirect Drupal 8 module.

  Background:
    Given
    Given users:
      | name            | mail                        | pass     | roles         |
      | behat-admin1234 | behat-admin1234@example.com | password | administrator |
    Given restricted "tags" terms:
      | name          | access_user   | access_role                             |
      | public        |               | anonymous, administrator, authenticated |
      | private       |               | administrator                           |
    Given article content:
      | title           | author     | status | created           | field_tags | alias           |
      | Public content  | Admin      | 1      | 2014-10-17 8:00am | public     | public-content  |
      | Private content | Admin      | 1      | 2014-10-17 8:00am | private    | private-content |
    Given Node access records are rebuild

  Scenario: All users should be able to access public content
    Given I open node view by node title "Public content"
    Then I should see text matching "Public content"
    Then I am on "/user/logout"
    Given I am on "/"
    Then I am logged in as a user with the "administer nodes" permission
    Then I open node view by node title "Public content"
    Then I should see text matching "Public content"
    Then I am on "/user/logout"
    Given I am on "/"
    Then I am logged in as a user with the "administrator" role
    Then I open node view by node title "Public content"
    Then I should see text matching "Public content"

  Scenario: Admins trying to access restricted content should success
    Given I am logged in as a user with the "administrator" role
    Then I open node view by node title "Private content"
    Then I should see text matching "Private content"

  Scenario: Authenticated users trying to access restricted content should see access denied
    Given I am logged in as a user with the "administer nodes" permission
    Then I open node view by node title "Private content"
    Then I should see text matching "Access denied"

  Scenario: Anonymous users trying to access restricted content should be required to log in, then be directed back
    Given I open node view by node title "Private content"
    Then I should see text matching "Log in"
    Then I fill in "Username" with "behat-admin1234"
    Then I fill in "Password" with "password"
    Then I click by selector "#edit-submit" via JavaScript
    Then I should see text matching "Private content"
    Then I am on "/user/logout"
    Then I open node view by node title "Private content"
    Then I should see text matching "Log in"
