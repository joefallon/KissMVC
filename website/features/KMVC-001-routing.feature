Feature: Routing
  Routing behavior is part of the public contract.

  Scenario: KMVC-001-S001 Empty route resolves to the default controller
    Given the route is empty
    When the application resolves the route
    Then it selects the default controller

  Scenario: KMVC-001-S002 The default route resolves to the index page controller
    Given the route is "default"
    When the application resolves the route
    Then it selects the default page controller

  Scenario: KMVC-001-S003 Route matching trims whitespace
    Given the route is "  default  "
    When the application resolves the route
    Then it selects the default page controller

  Scenario: KMVC-001-S004 Route matching is case-insensitive
    Given the route is "PaGe-WiTh-PaRaMeTeRs"
    When the application resolves the route
    Then it selects the page-with-parameters controller

  Scenario: KMVC-001-S005 Unknown routes resolve to no controller
    Given the route is "does-not-exist"
    When the application resolves the route
    Then no controller is selected
    And the front controller can show a 404 page

  Scenario: KMVC-001-S006 The page-with-parameters route resolves to its controller
    Given the route is "page-with-parameters"
    When the application resolves the route
    Then it selects the page-with-parameters controller
