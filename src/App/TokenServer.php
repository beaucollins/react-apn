<?php
namespace App;
use React\Http\Server as HttpServer;
use React\Socket\Server as SocketServer;
use Evenement\EventEmitter;

class TokenServer extends EventEmitter {
	
	private $token_store;
	
	function __construct( $gateway, $feedback, $token_store, $http_server ){
		$this->gateway = $gateway;
		$this->feedback = $feedback;
		$this->token_store = $token_store;
		$this->app = $app = new \Phluid\App;
		$app->inject( new \Phluid\Middleware\ExceptionHandler() );
		$app->inject( function( $req, $res, $next ){
		  $res->on( 'end', function() use ( $req, $res ){
		    echo "$req $res" . PHP_EOL;
		  });
		  $next();
		});

		$app->post( '/token', function( $req, $res ) {
		  $token = "";
		  $req->on( 'data', function( $data ) use ( &$token ){
		    $token .= $data;
		  });
		  $req->on( 'end', function( ) use ( $res, &$token ){
		    $decoded = unpack( 'H*token', $token );
		    $this->token_store[$decoded['token']] = date('U');
		    $res->setHeader( 'Content-Type', 'application/json' );
		    $res->renderText( json_encode( array( 'token' => $decoded['token'] ) ) );
		  } );
		} );

		$app->get( '/tokens', function( $req, $res ){
		  $res->render('tokens', array( 'tokens' => $this->token_store ));
		} );
		
		$app->post( '/broadcast', new \Phluid\Middleware\JsonBodyParser, function( $req, $res ){
			echo "BODY: " . json_encode( $req->body ) . PHP_EOL;
			foreach ($this->token_store as $device => $time) {
				$notification = new \Apn\Notification( $device );
				$notification->setAlert( $req->body['message'] );
				$this->gateway->sendNotification( $notification );
			}
			
			$res->setHeader( 'Content-Type', 'application/json' );
			// send the message to the feedback server
			$res->renderText( json_encode( array( 'response' => ';)' ) ) );
		});
		
		$this->expose( $http_server );
		
	}
	
	function expose( $http ){
		$this->app->createServer( $http );
	}
	
	public static function createServer( $loop, $gateway, $feedback, $token_store, $port = 4040, $host = null ){
		$socket = new SocketServer( $loop );
		$http = new HttpServer( $socket );
		$token_server = new TokenServer( $gateway, $feedback, $token_store, $http );
		
		$socket->listen( $port, $host );
		
	}
	
}