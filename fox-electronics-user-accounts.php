<?php
/**
 * Plugin Name:     Fox Electronics User Accounts
 * Plugin URI:      https://github.com/WenderHost/fox-electronics-user-accounts
 * Description:     Extends user accounts for integration with Fox Electronics business rules and SalesForce integration.
 * Author:          Michael Wender
 * Author URI:      https://michaelwender.com
 * Text Domain:     fox-electronics-user-accounts
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Fox_Electronics_User_Accounts
 */
define( 'FOXELECTRONICS_API_NAMESPACE', 'foxelectronics/v1' );
define( 'FOXELECTRONICS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'FOXELECTRONICS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load required files
require_once( 'lib/fns/jwt-authentication.php' );
require_once( 'lib/fns/salesforce.php' );
require_once( 'lib/fns/utilities.php' );

// Add User Meta Fields
$args = [
  'type' => 'string',
  'single' => true,
  'show_in_rest' => true
];
$user_company_meta = ['name' => 'Maps to Salesforce `accountname`.','type' => '','street' => '','city' => '','state' => '','zip' => '', 'country' => ''];
foreach( $user_company_meta as $meta_key => $meta_description ){
  $args['description'] = $meta_description;
  register_meta( 'user', 'company_' . $meta_key, $args );
}

/**
 * Handles validations during new user registration
 *
 * @param      <type>  $prepared_user  The prepared user
 * @param      <type>  $request        The request
 *
 * @return     <type>  ( description_of_the_return_value )
 */
function fox_pre_insert_user( $prepared_user, $request ){
  //error_log("\n--------------------------------------------\n".'fox_pre_insert_user() $prepared_user = ' . print_r( $prepared_user, true ) );
  //error_log('$request = ' . print_r( $request, true ) );

  $params = $request->get_params();
  error_log( '$params = ' . print_r( $params, true ) );
  $company_params = $params['meta'];

  $required = [];
  if( empty( $params['first_name'] ) )
    $required[] = 'First Name';

  if( empty( $params['last_name'] ) )
    $required[] = 'Last Name';

  foreach( $company_params as $key => $value ){
    if( empty( $value ) )
      $required[] = ucwords( str_replace('_', ' ', $key ) );
  }

  if( 0 < count( $required ) ){
    wp_send_json_error( [
      'code' => 'rest_missing_callback_param',
      'params' => $required,
      'message' => sprintf( __( 'Missing parameter(s): %s' ), implode( ', ', $required ) ),
    ], 400 );
  }

  return $prepared_user;
}
add_filter( 'rest_pre_insert_user', 'fox_pre_insert_user', 100, 2 );
