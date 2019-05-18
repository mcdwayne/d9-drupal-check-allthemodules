@api
Feature: Test Accordion Blocks Widget
  Tests the ability to add Accordion Block and its display
  
  
  Scenario: Test Accordion Block is listed on add block page
    Given I am logged in as a user with the 'administrator' role
    When I visit '/block/add'
    Then I should see "Accordion block"
  
  Scenario: Test new Accordion Block creation
    Given I am logged in as a user with the 'administrator' role
    When I visit '/block/add/accordion_block'
    And I fill in "info[0][value]" with "BDD Testing accordion block"
    And I fill in "field_blocks[0][target_id]" with "User account menu (bartik_account_menu)"
    And I press "Add another item"
    And I fill in "field_blocks[1][target_id]" with "Site branding (bartik_branding)"
    And I press "Add another item"
    And I fill in "field_blocks[2][target_id]" with "Entity view (User) (entityviewuser)"
    And I press "Save"
    Then I should see the message "Accordion block BDD Testing accordion block has been created."
    
  @javascript
  Scenario: Test Accordion Block Display
    Given I am logged in as a user with the 'administrator' role
    When I visit '/block/add/accordion_block'
    And I press "Add another item"
    And I press "Add another item"
    And I wait for the field items to appear
    And I fill in "info[0][value]" with "BDD Testing accordion block"
    And I fill in "field_blocks[0][target_id]" with "User account menu"
    And I fill in "field_blocks[1][target_id]" with "Site branding"
    And I fill in "field_blocks[2][target_id]" with "Secondary tabs"
    And I press "Save"
    And I should see the message "Accordion block BDD Testing accordion block has been created."
    And I visit "/admin/structure/block/library/bartik?region=sidebar_first"
    And I click "Place block" in the "BDD Testing accordion block" row
    And I wait for the suggestion box to appear
    And I Save Block Placement
    And I visit '/admin/structure/block'
    Then I should see "BDD Testing accordion block"
    And I visit '/node'
    Then I should see "BDD Testing accordion block" in the "sidebar_first"
    
    
  