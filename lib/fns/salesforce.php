<?php

namespace FoxElectronics\fns\salesforce;

/**
 * Sets up our SalesForce REST API Endpoint
 *
 * TODO: Add a `permission_callback`
 */
function salesforce_endpoint(){
  register_rest_route( FOXELECTRONICS_API_NAMESPACE, '/(?P<action>login|getWebUser|postWebUser)', [
    'methods'   => 'GET',
    'callback'  => function( \WP_REST_Request $request ){
      if( is_wp_error( $request ) )
        return $request;

      if( ! defined( 'FOXELECTRONCIS_SF_API_ROUTE' ) )
        return new \WP_Error( 'noapiroute', __('No `FOXELECTRONCIS_SF_API_ROUTE` defined. Please add to `FOXELECTRONCIS_SF_API_ROUTE` to your wp-config.php.') );

      $params = $request->get_params();
      $action = $params['action'];

      $valid_params = ['email','id','accountname','firstname','lastname'];
      foreach ( $valid_params as $key ) {
        $$key = ( isset( $params[$key] ) )? $params[$key] : null ;
      }

      switch( $action ){
        case 'login':
          $response = login();
          break;

        case 'getWebUser':
          if( ! isset( $_SESSION['SF_SESSION'] ) )
            login();

          $response = getWebUser([
            'access_token'  => $_SESSION['SF_SESSION']->access_token,
            'instance_url'  => $_SESSION['SF_SESSION']->instance_url,
            'query' => [
              'email'         => $email,
              'id'            => $id,
            ],
          ]);
          break;

        case 'postWebUser':
          if( ! isset( $_SESSION['SF_SESSION'] ) )
            login();

          $response = postWebUser([
            'access_token'  => $_SESSION['SF_SESSION']->access_token,
            'instance_url'  => $_SESSION['SF_SESSION']->instance_url,
            'body' => [
              'email'         => $email,
              'accountname'   => $accountname,
              'firstname'     => $firstname,
              'lastname'      => $lastname,
            ],
          ]);
          break;

        default:
          $response = new \WP_Error( 'noaction', __('No `action` specified in salesforce_endpoints()->callback().') );
          break;
      }
      return $response;
    }]);
}
add_action( 'rest_api_init', __NAMESPACE__ . '\\salesforce_endpoint' );

/**
 * Creates a lead via our Salesforce API.
 *
 * @param      object   $user         The user
 * @param      object   $RestRequest  The rest request
 * @param      bool     $creating     Are we creating a lead?
 */
function create_lead( $user, $RestRequest, $creating ){
  if( ! $creating )
    return;

  if( ! isset( $_SESSION['SF_SESSION'] ) )
    login();

  $request_json = $RestRequest->get_json_params();

  $response = postWebUser([
    'access_token'  => $_SESSION['SF_SESSION']->access_token,
    'instance_url'  => $_SESSION['SF_SESSION']->instance_url,
    'body' => [
      'email'         => $user->user_email,
      'accountname'   => $request_json['meta']['company_name'],
      'firstname'     => $request_json['first_name'],
      'lastname'      => $request_json['last_name'],
      'accounttype'   => $request_json['meta']['company_type'],
      'address'       => $request_json['meta']['company_street'],
      'city'          => $request_json['meta']['company_city'],
      'state'         => $request_json['meta']['company_state'],
    ],
  ]);

  if( ! is_wp_error( $response ) ){
    if( isset( $response->data->salesforceID ) )
      add_user_meta( $user->ID, 'salesforceID', $response->data->salesforceID, true );
    if( isset( $response->data->status ) )
      add_user_meta( $user->ID, 'status', $response->data->status, true );
  }
  error_log('[create_lead] $response->data = ' . print_r( $response->data, true ) );
}
add_action( 'rest_insert_user', __NAMESPACE__ . '\\create_lead', 10, 3 );

/**
 * Gets a Salesforce lead/contact.
 *
 * @param      array  $args   {
 *   @type  string  $access_token   Salesforce access_token
 *   @type  string  $instance_url   Our API endpoint
 *   @type  array   $query {
 *      @type   string  $email  (Optional) Email address
 *      @type   string  $id     Salesforce lead/contact ID
 *   }
 * }
 *
 * @return     \      The web user.
 */
function getWebUser( $args = [] ){
  if( ! isset( $args['access_token'] ) || empty( $args['access_token'] ) )
    return new \WP_Error( 'noaccesstoken', __('No Access Token provided.') );

  if( ! isset( $args['instance_url'] ) || empty( $args['instance_url'] ) )
    return new \WP_Error( 'noinstanceurl', __('No Instance URL provided.') );

  if( is_null( $args['query']['email'] ) && is_null( $args['query']['id'] ) )
    return new \WP_Error( 'noemailorid', __('No contact `email` or `id` provided.') );

  $request_url = trailingslashit( $args['instance_url'] ) . FOXELECTRONCIS_SF_API_ROUTE . '?' . http_build_query( $args['query'] );

  $response = wp_remote_get( $request_url, [
    'method' => 'GET',
    'timeout' => 30,
    'redirection' => 5,
    'headers' => [
      'Authorization' => 'Bearer ' . $args['access_token'],
    ],
  ]);

  if( ! is_wp_error( $response ) ){
    $data = json_decode( wp_remote_retrieve_body( $response ) );
    $response = new \stdClass();
    $response->data = $data;
  }

  return $response;
}

/**
 * Creates a Salesforce Lead
 *
 * @param      array  $args   {
 *   @type  string  $access_token   Salesforce access_token
 *   @type  string  $instance_url   Our API endpoint
 *   @type  array   $body {
 *      @type   string  $email        Email address
 *      @type   string  $accountname  Company name
 *      @type   string  $firstname    First name
 *      @type   string  $lastname     Last name
 *   }
 * }
 *
 * @return     \      Created lead.
 */
function postWebUser( $args = [] ){
  if( ! isset( $args['access_token'] ) || empty( $args['access_token'] ) )
    return new \WP_Error( 'noaccesstoken', __('No Access Token provided.') );

  if( ! isset( $args['instance_url'] ) || empty( $args['instance_url'] ) )
    return new \WP_Error( 'noinstanceurl', __('No Instance URL provided.') );

  $required_body_fields = ['email','accountname','firstname','lastname'];
  foreach ($required_body_fields as $field ) {
    if( ! isset( $args['body'][$field] ) || is_null( $args['body'][$field] ) )
      return new \WP_Error('nobody' . $field, __( 'Required field `' . $field . '` missing or empty.' ) );
  }

  $request_url = trailingslashit( $args['instance_url'] ) . FOXELECTRONCIS_SF_API_ROUTE;

  $response = wp_remote_post( $request_url, [
    'method' => 'POST',
    'timeout' => 30,
    'redirection' => 5,
    'headers' => [
      'Authorization' => 'Bearer ' . $args['access_token'],
      'Content-Type' => 'application/json',
    ],
    'body' => json_encode( $args['body'] ),
  ]);

  if( ! is_wp_error( $response ) ){
    $data = json_decode( wp_remote_retrieve_body( $response ) );
    $response = new \stdClass();
    $response->data = $data;
  }

  return $response;
}

/**
 * Logs into SalesForce using Session ID Authentication.
 *
 * Upon a successful login, our Salesforce access credentials
 * object is stored in $_SESSION['SF_SESSION'].
 *
 * Example:
 *
 * $_SESSION['SF_SESSION'] = {
 *   "access_token": "00D0t0000000Pmo!AQUAQA67ZA36gbSdaQUwfYwYIuiybQThERXoUXACW70ZhIWerHgeTcOtvhJv.orEe_6D.eJxZUulx76qJ_u7DPs6ZWH34yE8",
 *   "instance_url": "https://foxonline--foxpart1.cs77.my.salesforce.com",
 *   "id": "https://test.salesforce.com/id/00D0t0000000PmoEAE/0050t000001o5DeAAI",
 *   "token_type": "Bearer",
 *   "issued_at": "1550173766327",
 *   "signature": "buz6bzPJNBCkCBS1+ABC79hFOXRhLJMB3c1MTtRORFI="
 * }
 *
 * @return    object      Login Response or WP Error.
 */
function login(){

  // Verify we have credentials we need to attempt an authentication:
  $check_const = [
    'FOXELECTRONICS_CLIENT_ID',
    'FOXELECTRONICS_CLIENT_SECRET',
    'FOXELECTRONICS_USERNAME',
    'FOXELECTRONICS_PASSWORD',
    'FOXELECTRONICS_SECURITY_TOKEN',
    'FOXELECTRONICS_SF_AUTH_EP',
  ];
  foreach ($check_const as $const) {
    if( ! defined( $const ) ){
      return new \WP_Error( 'missingconst', __('Please make sure the following constants are defined in your `wp-config.php`: ' . implode( ', ', $check_const ) . '.') );
    }
  }

  // Login to SalesForce
  $response = wp_remote_post( FOXELECTRONICS_SF_AUTH_EP, [
    'method' => 'POST',
    'timeout' => 30,
    'redirection' => 5,
    'body' => [
      'grant_type' => 'password',
      'client_id' => FOXELECTRONICS_CLIENT_ID,
      'client_secret' => FOXELECTRONICS_CLIENT_SECRET,
      'username' => FOXELECTRONICS_USERNAME,
      'password' => FOXELECTRONICS_PASSWORD . FOXELECTRONICS_SECURITY_TOKEN
    ],
  ]);

  if( ! is_wp_error( $response ) ){
    $data = json_decode( wp_remote_retrieve_body( $response ) );
    $_SESSION['SF_SESSION'] = $data;
    $response = new \stdClass();
    $response->data = $data;
  }

  return $response;
}