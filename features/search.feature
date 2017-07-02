Feature: Search repositories
  Scenario: I want to get a list of the repositories that reference Behat
    Given I am an anonymous user
    When I search for "es6"
    Then I expect 200 response code
    And I expect at least 1 result