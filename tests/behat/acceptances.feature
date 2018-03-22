@tool @tool_policy
Feature: Viewing acceptances reports and accepting on behalf of other users
  In order to manage user acceptances
  As a manager
  I need to be able to view acceptances and accept on behalf of other users

  Background:
    Given the following config values are set as admin:
      | sitepolicyhandler | tool_policy |
    And the following policies exist:
      | Name                | Revision | Content    | Summary     | Status   |
      | This site policy    |          | full text2 | short text2 | active   |
      | This privacy policy |          | full text3 | short text3 | draft    |
    And the following "users" exist:
      | username | firstname | lastname | email           |
      | user1    | User      | One      | one@example.com |
      | user2    | User      | Two      | two@example.com |
      | manager  | Max       | Manager  | man@example.com |
    And the following "role assigns" exist:
      | user    | role           | contextlevel | reference |
      | manager | manager        | System       |           |

  Scenario: View acceptances made by users on their own, single policy
    When I log in as "user1"
    Then I should see "This site policy"
    And I should not see "Course overview"
    And I press "Next"
    And I set the field "I agree to the This site policy" to "1"
    And I press "Next"
    And I should see "Course overview"
    And I log out
    And I log in as "manager"
    And I press "Next"
    And I set the field "I agree to the This site policy" to "1"
    And I press "Next"
    And I navigate to "Privacy and policies > User agreements" in site administration
    And "Agreed" "icon" should exist in the "User One" "table_row"
    And "Agreed" "icon" should exist in the "Max Manager" "table_row"
    And "Not agreed" "icon" should exist in the "User Two" "table_row"

  Scenario: Agree on behalf of another user as a manager, single policy
    Given I log in as "admin"
    And I set the following system permissions of "Manager" role:
      | capability | permission |
      | tool/policy:acceptbehalf | Allow |
    And I log out
    When I log in as "manager"
    And I press "Next"
    And I set the field "I agree to the This site policy" to "1"
    And I press "Next"
    And I navigate to "Privacy and policies > User agreements" in site administration
    And I click on "Not agreed" "link" in the "User One" "table_row"
    Then I should see "Consent details"
    And I should see "User One"
    And I should see "This site policy"
    And I should see "I acknowledge that consents to these policies have been acquired"
    And I set the field "Remarks" to "Consent received from a parent"
    And I press "I agree to the policy"
    And "Agreed on behalf of" "icon" should exist in the "User One" "table_row"
    And "Max Manager" "link" should exist in the "User One" "table_row"
    And "Consent received from a parent" "text" should exist in the "User One" "table_row"
    And "Not agreed" "icon" should exist in the "User Two" "table_row"

  Scenario: View acceptances made by users on their own, multiple policies
    Given I log in as "admin"
    And I navigate to "Privacy and policies > Manage policies" in site administration
    And I open the action menu in "This privacy policy" "table_row"
    And I click on "Set status to \"Active\"" "link" in the "This privacy policy" "table_row"
    And I press "Continue"
    And I log out
    When I log in as "user1"
    Then I should see "This site policy"
    And I press "Next"
    And I should see "This privacy policy"
    And I press "Next"
    And I set the field "I agree to the This site policy" to "1"
    And I set the field "I agree to the This privacy policy" to "1"
    And I press "Next"
    And I should see "Course overview"
    And I log out
    And I log in as "manager"
    And I press "Next"
    And I press "Next"
    And I set the field "I agree to the This site policy" to "1"
    And I set the field "I agree to the This privacy policy" to "1"
    And I press "Next"
    And I navigate to "Privacy and policies > User agreements" in site administration
    And "Agreed" "icon" should exist in the "User One" "table_row"
    And "Not agreed" "icon" should not exist in the "User One" "table_row"
    And "Agreed" "icon" should exist in the "Max Manager" "table_row"
    And "Not agreed" "icon" should exist in the "User Two" "table_row"
    And "Agreed" "icon" should not exist in the "User Two" "table_row"
    And I click on "2 of 2" "link" in the "User One" "table_row"
    And "Agreed" "icon" should exist in the "This site policy" "table_row"
    And "Agreed" "icon" should exist in the "This privacy policy" "table_row"
    And I am on site homepage
    And I navigate to "Privacy and policies > User agreements" in site administration
    And I click on "0 of 2" "link" in the "User Two" "table_row"
    And "Not agreed" "icon" should exist in the "This site policy" "table_row"
    And "Not agreed" "icon" should exist in the "This privacy policy" "table_row"

  Scenario: Agree on behalf of another user as a manager, multiple policies
    Given I log in as "admin"
    And I navigate to "Privacy and policies > Manage policies" in site administration
    And I open the action menu in "This privacy policy" "table_row"
    And I click on "Set status to \"Active\"" "link" in the "This privacy policy" "table_row"
    And I press "Continue"
    And I set the following system permissions of "Manager" role:
      | capability | permission |
      | tool/policy:acceptbehalf | Allow |
    And I log out
    When I log in as "manager"
    And I press "Next"
    And I press "Next"
    And I set the field "I agree to the This site policy" to "1"
    And I set the field "I agree to the This privacy policy" to "1"
    And I press "Next"
    And I navigate to "Privacy and policies > User agreements" in site administration
    And I click on "Not agreed, click to agree to \"This site policy\"" "link" in the "User One" "table_row"
    Then I should see "Consent details"
    And I should see "User One"
    And I should see "This site policy"
    And I should see "I acknowledge that consents to these policies have been acquired"
    And I set the field "Remarks" to "Consent received from a parent"
    And I press "I agree to the policy"
    And "Agreed on behalf of" "icon" should exist in the "User One" "table_row"
    And "Not agreed, click to agree to \"This privacy policy\"" "icon" should exist in the "User One" "table_row"
    And I click on "1 of 2" "link" in the "User One" "table_row"
    And "Agreed on behalf of" "icon" should exist in the "This site policy" "table_row"
    And "Max Manager" "link" should exist in the "This site policy" "table_row"
    And "Consent received from a parent" "text" should exist in the "This site policy" "table_row"
    And "Not agreed" "icon" should exist in the "This privacy policy" "table_row"
