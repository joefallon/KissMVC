Feature: Configuration
  Application configuration is part of the public contract.

  Scenario: KMVC-004-S001 A configuration file can return a configuration array
    Given a configuration file is loaded
    When the application reads it
    Then it can return a configuration array

  Scenario: KMVC-004-S002 A configuration file can assign a config array
    Given a configuration file assigns a config array
    When the application reads it
    Then the configuration values are available

  Scenario: KMVC-004-S003 Later configuration loads override earlier values with the same key
    Given two configuration sources define the same key
    When the later source is loaded
    Then the later value is used

  Scenario: KMVC-004-S004 Missing registry values return no value
    Given a registry value is not defined
    When the application reads it
    Then no value is returned

  Scenario: KMVC-004-S005 Environment-specific configuration selects development defaults
    Given the application runs in development
    When environment-specific configuration is loaded
    Then the development defaults are selected

  Scenario: KMVC-004-S006 Environment-specific configuration selects production defaults
    Given the application runs in production
    When environment-specific configuration is loaded
    Then the production defaults are selected

  Scenario: KMVC-004-S007 Environment variables can provide deployment-specific values
    Given deployment-specific environment variables are defined
    When configuration is loaded
    Then the secret key, SSL requirement, and timezone can come from the environment
