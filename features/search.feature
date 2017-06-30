Feature: Search repositories
  Scenario: I want to get a list of the repositories that reference Behat
    Given I am an anonymous user
    When I search for behat
    Then I get a result