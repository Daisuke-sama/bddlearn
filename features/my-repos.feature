Feature: Get my repositories
  Scenario: I want to get a list of my own repositories
    Given I am an authenticated user
    When I request a list of my repositories
    Then The result should include a repository name "bddlearn"