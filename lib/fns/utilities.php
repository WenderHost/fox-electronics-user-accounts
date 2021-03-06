<?php

namespace FoxElectronics\fns\utilities;

function formatRFQ( $user = null, $rfq = null, $cart = null ){
  $parts = [];
  if( is_array( $cart ) && 0 < count( $cart ) ){
    foreach( $cart as $part ){
      if( is_null( $part['options']['evalDate'] ) ){
        $evaluation_date = new \DateTime();
      } else {
        $evaluation_date = new \DateTime( $part['options']['evalDate'] );
      }
      $evaluation_date = $evaluation_date->format('Y-m-d');

      $datasheet = ( is_null( $part['options']['datasheet'] ) )? false : $part['options']['datasheet'] ;

      $parts[] = [
        'partno' => $part['number']['label'],
        'description' => formatPartDesc( $part ),
        'sample' => $part['options']['sample'],
        'sampleqty' => $part['options']['sampleNo'],
        'quote' => $part['options']['quote'],
        'datasheet' => $datasheet,
        'evaluationdate' => $evaluation_date,
        'internalpartnumber' => $part['options']['internalPartNo'],
        'eau' => $part['options']['eau'],
      ];
    }
  }

  $prototype_date = new \DateTime($rfq['prototype_date']);
  $production_date = new \DateTime($rfq['production_date']);

  $shippingfirstname = $user['first_name'];
  $shippinglastname = $user['last_name'];

  // We have an alternate shipping address contact
  if( ! empty( $rfq['shipping_address']['contact'] ) ){
    $shipping_address_contact = trim( $rfq['shipping_address']['contact'] );

    // We have at least a first and last name
    if( stristr( $shipping_address_contact, ' ' ) ){
      $shipping_contact = explode( ' ', $shipping_address_contact );
      if( 2 < count( $shipping_contact ) ){
        $shippinglastname = array_pop( $shipping_contact );
        $shippingfirstname = implode( ' ', $shipping_contact );
      } else if( 2 == count( $shipping_contact ) ){
        $shippingfirstname = $shipping_contact[0];
        $shippinglastname = $shipping_contact[1];
      }
    // The shipping contact is just one name
    } else {
      $shippingfirstname = $shipping_address_contact;
      $shippinglastname = '';
    }

  }

  $formatted_rfq = [
    'salesforceID' => $user['salesforceID'],
    'status' => $user['status'],
    'firstname' => $user['first_name'],
    'lastname' => $user['last_name'],
    'email' => $user['user_email'],
    'accountname' => $user['company_name'],
    'accounttype' => $user['company_type'],
    'address' => $user['company_street'],
    'city' => $user['company_city'],
    'state' => $user['company_state'],
    'country' => $user['company_country'],
    'projectname' => $rfq['project_name'],
    'projectdescription' => $rfq['project_description'],
    'shippingfirstname' => $shippingfirstname,
    'shippinglastname' => $shippinglastname,
    'shippingaddress' => $rfq['shipping_address']['street'],
    'shippingcity' => $rfq['shipping_address']['city'],
    'shippingstate' => $rfq['shipping_address']['state'],
    'shippingcountry' => $rfq['shipping_address']['country'],
    'prototypedate' => $prototype_date->format('Y-m-d'),
    'productiondate' => $production_date->format('Y-m-d'),
    'alternatevendors' => $rfq['distys'],
    'parts' => $parts,
  ];

  return $formatted_rfq;
}

function formatPartDesc( $part ){
  $description = 'Model: ' . $part['product_type']['value'] . $part['size']['value'] . $part['package_option']['value'] . ', ' . $part['tolerance']['label'] . ' tolerance, ' . $part['stability']['label'] . ' stability, ' . $part['load']['label'] . ' load, ' . $part['optemp']['label'] . ' optemp, ' . $part['frequency']['value'] . ' ' . $part['frequency_unit']['value'] . ' ' . $part['package_type']['value'];
  return $description;
}