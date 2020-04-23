@api @javascript @multidev @dev @100
Feature: Basic site operation and navigation
  In order to use the intranet
  As an authenticated user
  I need to be able to navigate from the homepage

  Background:
    Given I am using a 1440x900 browser window

  Scenario: Visit the homepage as authenticated user
    When I visit "/"
    Then I should see "Portland Employees"
