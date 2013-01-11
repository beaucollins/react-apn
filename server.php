<?php

$autoload = require 'vendor/autoload.php';
$autoload->add( 'App', realpath( './src' ) );
$autoload->add( 'Apn', realpath( './src' ) );

use Apn\Connection;
use Apn\GatewayConnection;
use Apn\FeedbackConnection;

$loop = React\EventLoop\Factory::create();

$log = new React\Stream\Stream( STDOUT, $loop );

$token_store = array(
  '412cabf4543748233f5c3fc07f6ef95303aadd808a1162b100c09e2899335fb5' => time()
);

$ssl_options = array(
  'local_cert' => getenv('APN_CERT'),
  'passphrase' => getenv('APN_PASSWORD')
);

$push = new GatewayConnection( Connection::GATEWAY_SANDBOX_URI, $ssl_options,  $loop );
$push->on( 'notification-error', function( $message ){
  $log->write( "Gateway error: " . json_endoe( $message ) . PHP_EOL );
} );

$feedback = new FeedbackConnection( Connection::FEEDBACK_SANDBOX_URI , $ssl_options, $loop );
$feedback->on( 'feedback', function( $token ) use ( $log ){
  $log->write( "Token removed: " . json_encode( $token ) . PHP_EOL );
} );

$internal = new React\Socket\Server( $loop );
$internal->listen( 5010, '0.0.0.0' );

App\TokenServer::createServer( $loop, $push, $feedback, $token_store, 4000, '0.0.0.0' );

$loop->run();
