Feature: Selenium Test

  @javascript
  Scenario: The homepage prints "CoopCycle"
    Given I am on homepage
    Then print last response
    Then I should see "CoopCycle"
