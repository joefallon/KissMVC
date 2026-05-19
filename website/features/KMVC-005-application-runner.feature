Feature: Application runner
  Application runner behavior is part of the public contract.

  Scenario: KMVC-005-S001 Running the application checks whether SSL redirection is required
    Given the application is started
    When it begins running
    Then it checks whether SSL redirection is required

  Scenario: KMVC-005-S002 Running the application applies a configured timezone
    Given a timezone is configured
    When the application begins running
    Then it applies the configured timezone

  Scenario: KMVC-005-S003 Running the application dispatches through the front controller
    Given the application is started
    When it begins running
    Then it dispatches through the front controller

  Scenario: KMVC-005-S004 When SSL is not required, no redirect occurs
    Given SSL redirection is not required
    When the application begins running
    Then no redirect occurs

  Scenario: KMVC-005-S005 When SSL is required and the request is already secure, no redirect occurs
    Given SSL redirection is required and the request is secure
    When the application begins running
    Then no redirect occurs

  Scenario: KMVC-005-S006 When SSL is required and the request is not secure, the application redirects to HTTPS
    Given SSL redirection is required and the request is not secure
    When the application begins running
    Then it redirects to HTTPS

  Scenario: KMVC-005-S007 When headers were already sent, SSL redirection cannot be performed
    Given headers have already been sent
    And SSL redirection is required
    When the application begins running
    Then it reports that SSL redirection cannot be performed

  Scenario: KMVC-005-S008 Invalid timezone configuration is reported without stopping request dispatch
    Given the timezone configuration is invalid
    When the application begins running
    Then the invalid timezone is reported
    And request dispatch continues
