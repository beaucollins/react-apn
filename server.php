<?php

require 'vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
echo "Create connection" . PHP_EOL;
$client = stream_socket_client('tcp://gateway.sandbox.push.apple.com:2195');
echo "Opened client" . PHP_EOL;
$conn = new React\Socket\Connection($client, $loop);
$conn->pipe(new React\Stream\Stream(STDOUT, $loop));
$conn->write("Hello World!\n");

echo "Running" . PHP_EOL;

$loop->run();
