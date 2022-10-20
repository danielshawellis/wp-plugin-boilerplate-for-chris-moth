<?php

/**
 * Plugin Name:       Plugin Boilerplate
 * Description:       Boilerplate for a WordPress plugin that sends webhooks to an external server on Gravity Forms submissions and receives webhooks from an external server
 * Version:           1.0.0
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Requires at least: 5.6
 * Requires PHP:      7.2
 */

// If this file is called directly, abort.
defined('ABSPATH') or die;

// Hook the Gravity Forms submission and send it's contents to an external server (note that you could also use the GF Webhooks Addon here: https://www.gravityforms.com/add-ons/webhooks/)
add_action( 'gform_after_submission', function ( $entry, $form ) { // DOCUMENTATION: https://docs.gravityforms.com/gform_after_submission/
  // Get the post that was created by the form submission
  $post = get_post( $entry['post_id'] );

  // Grab the submission details and use them to create the body of a POST request (I'm not as familiar with how GF handles this, so you may need to edit this piece)
  $body = array(
    'first_name' => rgar( $entry, '1.3' ),
    'last_name' => rgar( $entry, '1.6' ),
    'message' => rgar( $entry, '3' ),
  );

  // Send the request and get the raw response back
  $endpoint_url = 'https://yourserver.com'; // beeceptor.com can be a helpful debugging tool here
  $response = wp_remote_post( $endpoint_url, array( 'body' => $body ) );

  // Throw any errors from the query attempt
  if (is_wp_error($response)) throw new \Exception("The post failed with the following error message: " . $response->get_error_message());

  // Throw errors for any failing HTTP response codes
  if (wp_remote_retrieve_response_code($response) !== 200) throw new \Exception("The post failed with the following HTTP response code: " . wp_remote_retrieve_response_message($response));

  // Retrieve the response body
  $raw_response_body = wp_remote_retrieve_body($response);

  // Throw any errors generated while retrieving the response body
  if (is_wp_error($raw_response_body)) throw new \Exception("Failed to decode the remote server's response with the following error message: " . $raw_response_body->get_error_message());

  // Decode the response body from JSON and do something with it
  $response_body = json_decode($raw_response_body);
}, 10, 2 );

// Create an endpoint on the WP REST API to recieve webhooks from an external service
add_action("rest_api_init", function () {
  register_rest_route( // DOCUMENTATION: https://developer.wordpress.org/reference/functions/register_rest_route/
    'plugin-boilerplate',
    '/webhook',
    [
      'methods' => 'POST',
      'sanitize_callback' => function ($value) { // Optionally do some sanitization with WP sanitization functions
        return sanitize_text_field($value);
      },
      'permission_callback' => function () { // Optionally run some authentication logic (i.e. checking for a secret header value)
        return true;
      },
      'args' => [ // Optionally run some validation on JSON in the post body: https://www.shawnhooper.ca/2017/02/15/wp-rest-secrets-found-reading-core-code/
        'some_json_property' => [
          'required' => true,
          'type' => 'string',
          'description' => 'A JSON property to validate as a required string'
        ]
      ],
      'callback' => function (\WP_REST_Request $request) {
        // Destructure the JSON in the post body and do something with it below
        ['some_json_property' => $some_json_property] = $request->get_json_params();
      },
    ]
  );
});
