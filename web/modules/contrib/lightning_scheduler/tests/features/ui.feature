@api @lightning_workflow @javascript
Feature: Lightning Scheduler UI

  Background:
    Given I am logged in as a user with the "create page content, view own unpublished content, edit own page content, use editorial transition create_new_draft, schedule editorial transition publish, schedule editorial transition archive" permissions
    And I visit "/node/add/page"
    And I enter "Schedule This" for "Title"

  @a55f7706
  Scenario: Scheduling moderation state transitions
    When I schedule a transition to Published on "5/4/2038" at "6 PM"
    And I schedule a transition to Archived on "9/19/2038" at "8:57 AM"
    And I press "Save"
    And I visit the edit form
    Then I should see "Change to Published on May 4, 2038 at 6:00 PM"
    And I should see "Change to Archived on September 19, 2038 at 8:57 AM"
    And I should see the link "add another"

  @e0c3690a
  Scenario: Removing a previously saved transition
    When I schedule a transition to Published on "9/19/2038" at "8:57 AM"
    And I press "Save"
    And I visit the edit form
    And I click "Remove transition to Published on September 19, 2038 at 8:57 AM"
    And I press "Save"
    And I visit the edit form
    Then I should not see "Change to Published on September 19, 2038 at 8:57 AM"
    And I should see the link "Schedule a status change"

  @769caa15
  Scenario: Canceling and removing moderation state transitions
    When I schedule a transition to Published on "5/4/2038" at "6 PM"
    And I schedule a transition to Archived on "9/19/2038" at "8:57 AM"
    And I prepare a transition to Published on "10/31/2038" at "9 AM"
    And I click "Cancel transition"
    And I click "Remove transition to Archived on September 19, 2038 at 8:57 AM"
    And I press "Save"
    And I visit the edit form
    Then I should see "Change to Published on May 4, 2038 at 6:00 PM"
    But I should not see "Change to Archived on September 19, 2038 at 8:57 AM"
    And I should not see "Change to Published on October 31, 2038 at 9:00 PM"
    And I should not see the link "Schedule a status change"
    But I should see the link "add another"
