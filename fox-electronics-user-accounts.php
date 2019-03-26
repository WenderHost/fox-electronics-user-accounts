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

// Add User Meta Fields
$args = [
  'type' => 'string',
  'single' => true,
  'show_in_rest' => true
];
$user_company_meta = ['name' => 'Maps to Salesforce `accountname`.','type' => '','street' => '','city' => '','state' => '','zip' => ''];
foreach( $user_company_meta as $meta_key => $meta_description ){
  $args['description'] = $meta_description;
  register_meta( 'user', 'company_' . $meta_key, $args );
}
