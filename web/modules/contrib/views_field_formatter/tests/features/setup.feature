@api @javascript
Feature: Setup
  A user needs to be able to configure the VFF properly.

  Background:
    Given "tags" terms:
    | name |
    | TagA |
    | TagB |
    | TagC |
    | TagD |
    | TagE |
    | TagF |
    | TagG |
    | TagH |
    | TagI |
    Given "article" content:
      | title        | body        | field_tags     |
      | Node 1 title | Node 1 body | TagA,TagB,TagC |
      | Node 2 title | Node 2 body | TagD,TagE,TagF |
      | Node 3 title | Node 3 body | TagG,TagH,TagI |
    And I am logged in as a user with the "administrator" role
    And I set the "views_field_formatter" formatter to the field "body" of the "article" bundle of "node" entity
    And I set the "views_field_formatter" formatter to the field "field_tags" of the "article" bundle of "node" entity

  Scenario:
    When I am on "/admin/structure/types/manage/article/display"
    Then I should see the text "Not configured yet."

  Scenario:
    When I am on "/admin/structure/types/manage/article/display"
    And I press "body_settings_edit"
    And I select "vff_single_test_view::embed_1" from "View"
    And I press "Update"
    And I wait for AJAX to finish
    And I press "Save"
    Then I should see "Your settings have been saved."
    When I am on "/admin/content"
    And I follow "Node 1 title"
    Then I should see the text "**Node 1 title**"
    Then I should see the text "**Node 2 title**"
    Then I should see the text "**Node 3 title**"
    When I am on "/admin/content"
    And I follow "Node 2 title"
    Then I should see the text "**Node 1 title**"
    Then I should see the text "**Node 2 title**"
    Then I should see the text "**Node 3 title**"
    When I am on "/admin/content"
    And I follow "Node 3 title"
    Then I should see the text "**Node 1 title**"
    Then I should see the text "**Node 2 title**"
    Then I should see the text "**Node 3 title**"

  Scenario:
    When I am on "/admin/structure/types/manage/article/display"
    And I press "body_settings_edit"
    And I select "vff_single_test_view::embed_1" from "View"
    And I press "Add a new argument"
    And I wait for AJAX to finish
    Then I should see "Use a static string or a Drupal token."
    And I fill in "Argument" with "[node:nid]"
    And I check the box "fields[body][settings_edit_form][settings][arguments][0][checked]"
    And I press "Update"
    And I wait for AJAX to finish
    And I press "Save"
    Then I should see "Your settings have been saved."
    When I am on "/admin/content"
    And I follow "Node 1 title"
    Then I should see the text "**Node 1 title**"
    Then I should see the text "**Node 2 title**"
    Then I should see the text "**Node 3 title**"
    When I am on "/admin/content"
    And I follow "Node 2 title"
    Then I should see the text "**Node 1 title**"
    Then I should see the text "**Node 2 title**"
    Then I should see the text "**Node 3 title**"
    When I am on "/admin/content"
    And I follow "Node 3 title"
    Then I should see the text "**Node 1 title**"
    Then I should see the text "**Node 2 title**"
    Then I should see the text "**Node 3 title**"

  Scenario:
    When I am on "/admin/structure/types/manage/article/display"
    And I press "body_settings_edit"
    And I select "vff_single_test_view::embed_2" from "View"
    And I press "Add a new argument"
    And I wait for AJAX to finish
    Then I should see "Use a static string or a Drupal token."
    And I fill in "Argument" with "[node:nid]"
    And I check the box "fields[body][settings_edit_form][settings][arguments][0][checked]"
    And I press "Update"
    And I wait for AJAX to finish
    And I press "Save"
    Then I should see "Your settings have been saved."
    When I am viewing my "article" with the title "Node 1 title"
    Then I should see the text "**Node 1 title**"
    Then I should not see the text "**Node 2 title**"
    Then I should not see the text "**Node 3 title**"
    When I am viewing my "article" with the title "Node 2 title"
    Then I should not see the text "**Node 1 title**"
    Then I should see the text "**Node 2 title**"
    Then I should not see the text "**Node 3 title**"
    When I am viewing my "article" with the title "Node 3 title"
    Then I should not see the text "**Node 1 title**"
    Then I should not see the text "**Node 2 title**"
    Then I should see the text "**Node 3 title**"

  Scenario:
    When I am on "/admin/structure/types/manage/article/display"
    And I press "field_tags_settings_edit"
    And I select "vff_single_test_view::embed_3" from "View"
    And I press "Add a new argument"
    And I wait for AJAX to finish
    Then I should see "Use a static string or a Drupal token."
    And I fill in "Argument" with "[node:field_tags:current]"
    And I check the box "fields[field_tags][settings_edit_form][settings][arguments][0][checked]"
    And I fill in "Concatenate arguments" with ","
    And I press "Update"
    And I wait for AJAX to finish
    And I press "Save"
    Then I should see "Your settings have been saved."
    Then the cache has been cleared
    When I am on "/admin/content"
    And I follow "Node 1 title"
    Then I should see the text "**Node 1 title**TagA,TagB,TagC**"
    When I am on "/admin/content"
    And I follow "Node 2 title"
    Then I should see the text "**Node 2 title**TagD,TagE,TagF**"
    When I am on "/admin/content"
    And I follow "Node 3 title"
    Then I should see the text "**Node 3 title**TagG,TagH,TagI**"
