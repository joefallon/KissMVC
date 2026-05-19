Feature: Controller rendering
  Controller rendering is part of the public contract.

  Scenario: KMVC-003-S001 A controller can render its configured layout
    Given a controller is handling the request
    When the controller renders the response
    Then its configured layout is rendered

  Scenario: KMVC-003-S002 A layout can render the selected view
    Given a controller has selected a view
    When the response is rendered
    Then the layout renders the selected view

  Scenario: KMVC-003-S003 A view can access public controller methods intended for presentation
    Given a controller exposes public presentation methods
    When the view is rendered
    Then the view can access those presentation methods

  Scenario: KMVC-003-S004 A view can render a partial
    Given a view needs reusable markup
    When the view is rendered
    Then it can render a partial

  Scenario: KMVC-003-S005 A partial receives its provided data
    Given a partial is rendered with data
    When the partial is rendered
    Then it receives the provided data

  Scenario: KMVC-003-S006 A controller can expose request parameters to a view
    Given a request includes parameters
    When the controller renders the view
    Then the view can access the request parameters
