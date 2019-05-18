@api @lightning_workflow @javascript
Feature: Scheduling transitions on content

  Background:
    Given I am logged in as a user with the "create page content, view own unpublished content, edit own page content, use editorial transition create_new_draft, use editorial transition review, use editorial transition publish, use editorial transition archive, schedule editorial transition publish, schedule editorial transition archive, view latest version" permissions
    And I visit "/node/add/page"
    And I enter "Schedule This" for "Title"

  @55c3c017
  Scenario: Automatically publishing in the future
    When I schedule a transition to Published in 10 seconds
    And I press "Save"
    And I wait 15 seconds
    And I run cron over HTTP
    And I visit the edit form
    Then I should see "Current state Published"
    And I should not see a ".scheduled-transition" element

  @bafaf901
  Scenario: Automatically publishing in the past
    When I schedule a transition to Published 10 seconds ago
    And I press "Save"
    And I run cron over HTTP
    And I visit the edit form
    Then I should see "Current state Published"
    And I should not see a ".scheduled-transition" element

  @368f0045
  Scenario: Automatically publishing, then unpublishing, in the future
    When I schedule a transition to Published in 10 seconds
    And I schedule a transition to Archived in 20 seconds
    And I press "Save"
    And I wait 15 seconds
    And I run cron over HTTP
    And I wait 10 seconds
    And I run cron over HTTP
    And I visit the edit form
    Then I should see "Current state Archived"
    And I should not see a ".scheduled-transition" element

  @19678505
  Scenario: Skipping a invalid transition scheduled in the past
    When I schedule a transition to Published 20 seconds ago
    And I schedule a transition to Archived 10 seconds ago
    And I press "Save"
    And I run cron over HTTP
    And I visit the edit form
    # It will still be in the draft state because the transition should resolve
    # to Draft -> Archived, which doesn't exist.
    Then I should see "Current state Draft"
    And I should not see a ".scheduled-transition" element

  @5cdf4335
  Scenario: Clearing previously run transitions
    When I select "In review" from "moderation_state[0][state]"
    And I press "Save"
    And I visit the edit form
    And I schedule a transition to Published in 10 seconds
    And I press "Save"
    And I wait 12 seconds
    And I run cron over HTTP
    And I visit the edit form
    And I select "Archived" from "moderation_state[0][state]"
    And I press "Save"
    And I run cron over HTTP
    And I visit the edit form
    Then I should see "Current state Archived"
    And I should not see "Current state Published"

  @4e8a6957
  Scenario: Automatically publishing when there is a pending revision
    When I select "Published" from "moderation_state[0][state]"
    # Open the publishing options.
    And I click the "#edit-options > summary" element
    And I check the box "Promoted to front page"
    And I press "Save"
    And I visit the edit form
    And I enter "MC Hammer" for "Title"
    And I select "Draft" from "moderation_state[0][state]"
    And I schedule a transition to Published in 10 seconds
    And I press "Save"
    And I wait 15 seconds
    And I run cron over HTTP
    And I visit "/node"
    Then I should see the link "MC Hammer"
