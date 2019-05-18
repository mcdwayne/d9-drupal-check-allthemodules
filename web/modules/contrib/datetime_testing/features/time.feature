@api
Feature: Maniuplating the time
    In order to test behavior over time
    We need to be able to manipulate the time

  Scenario: Manipulate the time is various ways
    Given the date is "17 May 2008 2pm"
    Given time is frozen
    When the time is "+1 hour"
    When time is unfrozen
    When the time is frozen at "5pm"
    When the day is "next Tuesday"
