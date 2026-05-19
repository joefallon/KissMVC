Feature: Request dispatch
  Front-controller dispatch is part of the public contract.

  Scenario: KMVC-002-S001 A root request dispatches to the default page
    Given the request path is "/"
    When the application dispatches the request
    Then the default page is selected

  Scenario: KMVC-002-S002 A one-segment request selects the matching route
    Given the request path is "/page-with-parameters"
    When the application dispatches the request
    Then the matching route is selected

  Scenario: KMVC-002-S003 Remaining URL segments are passed to the selected controller
    Given the request path is "/page-with-parameters/abc/123/xyz"
    When the application dispatches the request
    Then the selected controller receives the remaining path segments as request parameters

  Scenario: KMVC-002-S004 Query strings do not become request parameters
    Given the request path is "/page-with-parameters/abc/123/xyz?ignored=value"
    When the application dispatches the request
    Then only the path segments are passed as request parameters

  Scenario: KMVC-002-S005 An unknown route produces a 404 response path
    Given the request path is "/does-not-exist"
    When the application dispatches the request
    Then the 404 response path is selected

  Scenario: KMVC-002-S006 A controller failure produces a 500 response path
    Given the selected controller fails while handling the request
    When the application dispatches the request
    Then the 500 response path is selected
