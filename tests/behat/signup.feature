@tool @tool_policy
Feature: User must accept policy when logging in and signing up
  In order to record user agreement to use the site
  As a user
  I need to be able to accept site policy during sign up

  Scenario: Accept policy on sign up, no site policy
    Given the following config values are set as admin:
      | registerauth    | email |
      | passwordpolicy  | 0     |
      | sitepolicyhandler | tool_policy |
    And I am on site homepage
    And I follow "Log in"
    When I press "Create new account"
    Then I should not see "I understand and agree"
    And I set the following fields to these values:
      | Username      | user1                 |
      | Password      | user1                 |
      | Email address | user1@address.invalid |
      | Email (again) | user1@address.invalid |
      | First name    | User1                 |
      | Surname       | L1                    |
    And I press "Create my new account"
    And I should see "Confirm your account"
    And I should see "An email should have been sent to your address at user1@address.invalid"
    And I confirm email for "user1"
    And I should see "Thanks, User1 L1"
    And I should see "Your registration has been confirmed"
    And I open my profile in edit mode
    And the field "First name" matches value "User1"
    And I log out
    # Confirm that user can login and browse the site (edit their profile).
    And I log in as "user1"
    And I open my profile in edit mode
    And the field "First name" matches value "User1"

  Scenario: Accept policy on sign up, one policy
    Given the following config values are set as admin:
      | registerauth    | email |
      | passwordpolicy  | 0     |
      | sitepolicyhandler | tool_policy |
    Given the following policies exist:
      | Policy | Name             | Revision | Content    | Summary     | Status   |
      | P1     | This site policy |          | full text1 | short text1 | archived |
      | P1     | This site policy |          | full text2 | short text2 | active   |
      | P1     | This site policy |          | full text3 | short text3 | draft    |
    And I am on site homepage
    And I follow "Log in"
    When I press "Create new account"
    Then I should see "This site policy"
    And I should see "short text2"
    And I should see "full text2"
    And I press "Next"
    And I should see "Please agree to the following policies"
    And I should see "This site policy"
    And I should see "short text2"
    And I should not see "full text2"
    And I set the field "I agree to the This site policy" to "1"
    And I press "Next"
    And I should not see "I understand and agree"
    And I set the following fields to these values:
      | Username      | user1                 |
      | Password      | user1                 |
      | Email address | user1@address.invalid |
      | Email (again) | user1@address.invalid |
      | First name    | User1                 |
      | Surname       | L1                    |
    And I press "Create my new account"
    And I should see "Confirm your account"
    And I should see "An email should have been sent to your address at user1@address.invalid"
    And I confirm email for "user1"
    And I should see "Thanks, User1 L1"
    And I should see "Your registration has been confirmed"
    And I open my profile in edit mode
    And the field "First name" matches value "User1"
    And I log out
    # Confirm that user can login and browse the site.
    And I log in as "user1"
    And I follow "Profile" in the user menu
    # User can see his own agreements in the profile.
    And I follow "Policies and agreements"
    And "Agreed" "icon" should exist in the "This site policy" "table_row"
    And I log out

  Scenario: Accept policy on sign up, multiple policies
    Given the following config values are set as admin:
      | registerauth    | email |
      | passwordpolicy  | 0     |
      | sitepolicyhandler | tool_policy |
    Given the following policies exist:
      | Name                | Type | Revision | Content    | Summary     | Status   | Audience |
      | This site policy    | 0    |          | full text2 | short text2 | active   | all      |
      | This privacy policy | 1    |          | full text3 | short text3 | active   | loggedin |
      | This guests policy  | 0    |          | full text4 | short text4 | active   | guest    |
    And I am on site homepage
    And I follow "Log in"
    When I press "Create new account"
    Then I should see "This site policy"
    And I should see "short text2"
    And I should see "full text2"
    And I press "Next"
    And I should see "This privacy policy"
    And I should see "short text3"
    And I should see "full text3"
    And I press "Next"
    And I should see "Please agree to the following policies"
    And I should see "This site policy"
    And I should see "short text2"
    And I should not see "full text2"
    And I should see "This privacy policy"
    And I should see "short text3"
    And I should not see "full text3"
    And I should not see "This guests policy"
    And I should not see "short text4"
    And I should not see "full text4"
    And I set the field "I agree to the This site policy" to "1"
    And I set the field "I agree to the This privacy policy" to "1"
    And I press "Next"
    And I should not see "I understand and agree"
    And I set the following fields to these values:
      | Username      | user1                 |
      | Password      | user1                 |
      | Email address | user1@address.invalid |
      | Email (again) | user1@address.invalid |
      | First name    | User1                 |
      | Surname       | L1                    |
    And I press "Create my new account"
    And I should see "Confirm your account"
    And I should see "An email should have been sent to your address at user1@address.invalid"
    And I confirm email for "user1"
    And I should see "Thanks, User1 L1"
    And I should see "Your registration has been confirmed"
    And I open my profile in edit mode
    And the field "First name" matches value "User1"
    And I log out
    # Confirm that user can login and browse the site.
    And I log in as "user1"
    And I follow "Profile" in the user menu
    # User can see his own agreements in the profile.
    And I follow "Policies and agreements"
    And "Agreed" "icon" should exist in the "This site policy" "table_row"
    And "Agreed" "icon" should exist in the "This privacy policy" "table_row"
    And I should not see "This guests policy"
    And I log out

  Scenario: Accept policy on sign up and age verification
    Given the following config values are set as admin:
      | registerauth    | email |
      | passwordpolicy  | 0     |
      | sitepolicyhandler | tool_policy |
      | agedigitalconsentverification | 1 |
    Given the following policies exist:
      | Name             | Revision | Content    | Summary     | Status   |
      | This site policy |          | full text2 | short text2 | active   |
    And I am on site homepage
    And I follow "Log in"
    When I press "Create new account"
    Then I should see "Age and location verification"
    And I set the field "What is your age?" to "16"
    And I set the field "In which country do you live?" to "DZ"
    And I press "Proceed"
    And I should see "This site policy"
    And I should see "short text2"
    And I should see "full text2"
    And I press "Next"
    And I should see "Please agree to the following policies"
    And I should see "This site policy"
    And I should see "short text2"
    And I should not see "full text2"
    And I set the field "I agree to the This site policy" to "1"
    And I press "Next"
    And I should not see "I understand and agree"
    And I set the following fields to these values:
      | Username      | user1                 |
      | Password      | user1                 |
      | Email address | user1@address.invalid |
      | Email (again) | user1@address.invalid |
      | First name    | User1                 |
      | Surname       | L1                    |
    And I press "Create my new account"
    And I should see "Confirm your account"
    And I should see "An email should have been sent to your address at user1@address.invalid"
    And I confirm email for "user1"
    And I should see "Thanks, User1 L1"
    And I should see "Your registration has been confirmed"
    And I open my profile in edit mode
    And the field "First name" matches value "User1"
    And I log out
    # Confirm that user can login and browse the site.
    And I log in as "user1"
    And I follow "Profile" in the user menu
    # User can see his own agreements in the profile.
    And I follow "Policies and agreements"
    And "Agreed" "icon" should exist in the "This site policy" "table_row"
    And I log out
