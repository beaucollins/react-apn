<?php

$autoload = require 'vendor/autoload.php';
$autoload->add( 'App', realpath( './src' ) );
$autoload->add( 'Apn', realpath( './src' ) );

use Apn\Connection;
use Apn\GatewayConnection;
use Apn\FeedbackConnection;


$loop = React\EventLoop\Factory::create();

$token_store = array();

$cert = array(
  'file' => $_ENV['APN_CERT'],
  'password' => $_ENV['APN_PASSWORD']
);

$push = new GatewayConnection( Connection::GATEWAY_SANDBOX_URI, $cert,  $loop );
$push->on( 'data', function( $data ){
	$error = unpack( 'Ccommand/CstatusCode/Nidentifier', $data );
  echo "Error: " . json_encode( $error ) . PHP_EOL;
} );
$feedback = new FeedbackConnection( Connection::FEEDBACK_SANDBOX_URI , $cert, $loop );
$feedback->on( 'data', function( $data ){
  echo "Data length: " . strlen( $data );
  $message = unpack( 'N1timestamp/n1length/H*devtoken', $data );
  echo "Message: " . json_encode( $message ) . PHP_EOL;
} );

$feedback->on( 'close', function() use ( $loop, $feedback ){
  echo "Closed !" . PHP_EOL;
  $loop->addTimer( 10, function() use ( $feedback ){
    echo "Reconnecting!" . PHP_EOL;
    $feedback->reconnect();
  });
});

App\TokenServer::createServer( $loop, $push, $feedback, $token_store, 4000, '0.0.0.0' );

$loop->run();
