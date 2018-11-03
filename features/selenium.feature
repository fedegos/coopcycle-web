Feature: Selenium Test

  @javascript
  Scenario: The homepage prints "CoopCycle"
    Given I am on "/fr"
    Then print last response
    Then I should see "De bons plats livrés à domicile"
