Feature: Error responses
  Error-response behavior is part of the public contract.

  Scenario: KMVC-006-S001 An unresolved route produces a 404 response
    Given a route cannot be resolved
    When the application handles the request
    Then a 404 response is produced

  Scenario: KMVC-006-S002 A configured 404 view is rendered when available
    Given a 404 view is configured
    When a 404 response is produced
    Then the configured 404 view is rendered

  Scenario: KMVC-006-S003 A safe fallback 404 message is rendered when no 404 view is available
    Given no 404 view is configured
    When a 404 response is produced
    Then a safe fallback 404 message is rendered

  Scenario: KMVC-006-S004 A controller failure produces a 500 response
    Given a controller fails while handling the request
    When the application handles the request
    Then a 500 response is produced

  Scenario: KMVC-006-S005 A configured 500 view is rendered when available
    Given a 500 view is configured
    When a 500 response is produced
    Then the configured 500 view is rendered

  Scenario: KMVC-006-S006 A safe fallback 500 message is rendered when no 500 view is available
    Given no 500 view is configured
    When a 500 response is produced
    Then a safe fallback 500 message is rendered

  Scenario: KMVC-006-S007 Error responses do not expose internal exception details by default
    Given an exception occurs while handling a request
    When an error response is produced
    Then internal exception details are not exposed by default
