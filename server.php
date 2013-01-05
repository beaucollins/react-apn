<?php

require 'vendor/autoload.php';

$cert = realpath('client.cer');

$loop = React\EventLoop\Factory::create();
echo "Create connection" . PHP_EOL;
$client = stream_socket_client('tcp://gateway.sandbox.push.apple.com:2195');
stream_context_set_option( $client, 'ssl', 'local_cert', $cert );
echo "Opened client" . PHP_EOL;
$conn = new React\Socket\Connection($client, $loop);
$conn->pipe(new React\Stream\Stream(STDOUT, $loop));

echo "Running" . PHP_EOL;

$loop->run();
