Feature: There are a number of repositories we want to watch

  Scenario: I want to watch all projects that my project depends on
    Given I am an authenticated user
      And I have a following repositories:
        | owner   | project   |
        | Behat   | Behat     |
        | laravel | laravel   |
        | laravel | framework |
    When I watch this repositories
    Then My watch list includes those repositories