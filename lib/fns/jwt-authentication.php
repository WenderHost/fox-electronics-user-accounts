<?php

namespace FoxElectronics\fns\jwtauth;

/**
 * Add Salesforce user meta to the data sent back to the client.
 *
 * This function adds the following data to the client response
 * received by the FOXSelect React app:
 *
 * - $salesforceID
 * - $status (`lead` || `contact`)
 *
 * @param      array  $data   The data returned from a Salesforce authentication attemp
 *
 * @return     array  Our modified $data to be sent to the FOXSelect client.
 */
function add_to_login_response( $data ){
  $user = get_user_by( 'email', $data['user_email'] );

  $meta_keys = ['salesforceID','status','company_name','company_street','company_city','company_state','company_zip','company_type','first_name','last_name'];
  foreach ($meta_keys as $key) {
    $$key = get_user_meta( $user->ID, $key, true );
    $data[$key] = $$key;
  }

  return $data;
}
add_filter( 'jwt_auth_token_before_dispatch', __NAMESPACE__ . '\\add_to_login_response' );

/**
 * Prevents users from changing their email addresses.
 *
 * We don't want users to be able to change their own email address
 * because doing so has implications for accessing other user's
 * details from Salesforce.
 *
 * @param      array    $errors  The errors
 * @param      bool     $update  `true` if updating.
 * @param      object   $user    The user object
 */
function prevent_user_email_change( $errors, $update, $user ){
  $old = get_user_by( 'id', $user->ID );

  if( $user->user_email != $old->user_email && ( ! current_user_can( 'create_users' ) ) )
    $user->user_email = $old->user_email;
}
add_action( 'user_profile_udpate_errors', __NAMESPACE__ . '\\prevent_user_email_change', 10, 3 );
