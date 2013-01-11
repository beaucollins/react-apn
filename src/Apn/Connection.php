<?php
namespace Apn;
use React\Stream\Stream;
use React\Stream\WritableStreamInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\StreamInterface;
use React\Stream\Util;
use Evenement\EventEmitter;
use Evenement\EventEmitterInterface;

class Connection extends EventEmitter implements StreamInterface {
  
  const FEEDBACK_URI = 'tcp://feedback.push.apple.com:2196';
  const GATEWAY_URI = 'ssl://gateway.push.apple.com:2195'; 

  const FEEDBACK_SANDBOX_URI = 'tcp://feedback.sandbox.push.apple.com:2196';
  const GATEWAY_SANDBOX_URI = 'tcp://gateway.sandbox.push.apple.com:2195';   
  
  const RETRY_TIMEOUT = 10;
  const CONNECTION_TIMEOUT = 60;
  
  public $retry_timeout = self::RETRY_TIMEOUT;
  
  protected $connection;
  private $password;
  private $cert;
  private $stream;
  protected $loop;
  private $uri;
  private $secure = false;
  private $retry_count = 0;
  public $stream_context;
  
  function __construct( $uri, $ssl_options, $loop ){
    $this->loop = $loop;
    
    if ( is_array( $ssl_options ) ) {
      $this->stream_context = array( 'ssl' => $ssl_options );
    } else {
      $this->stream_context = array( 'ssl' => array( 'local_cert' => $ssl_options ) );
    }
        
    $this->uri = $uri;
    
    $this->connect();

  }
  
  function connect(){
    $socket = stream_socket_client( $this->uri, $error, $errorString, CONNECTION_TIMEOUT, STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT );
    stream_context_set_option( $socket, $this->stream_context );
    if ( !$socket ) {
      $this->emit( 'error', array( $error, $errorString ) );
      return;
    }
    stream_set_blocking( $socket, 0 );
    $this->emit( 'connect', array( $socket ) );
    $uri = parse_url( $this->uri );
    if ( $uri['scheme'] == 'ssl' ) {
      $this->handleSocket( $socket );
      return;
    }
    $this->once('secure', array( $this, 'handleSocket' ) );
    $this->secureConnection( $socket );
  }
  
  function reconnect(){
    if ( $this->connection ) $this->connection->close();
    $this->connect();
  }
  
  function retryConnection(){
    if ( $this->retry_count > 5 ) {
      $this->emit( 'error', array( sprintf( "Couldn't reconnect after %d retries", $this->retry_count ) ) );
    } else {
      // exponential backoff
      $wait = $this->retry_timeout + pow( 2, $this->retry_count ) - 1;
      $this->timer = $this->loop->addTimer( $wait, array( $this, 'reconnect' ) );
      $this->emit( 'reconnect', array( $this->timer, $wait ) );
    }
    $this->retry_count ++;
  }
  
  function getConnection(){
    return $this->connection;
  }
  
  function secureConnection( $socket ){
    $this->loop->addWriteStream( $socket, function( $socket ){
      $this->loop->removeWriteStream( $socket );
      
      $enableCrypto = function( $socket ){
        $secure = stream_socket_enable_crypto( $socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT );
        if ( $secure === 0 ){
          return;
        }
        $this->loop->removeReadStream( $socket );
        $this->loop->removeWriteStream( $socket );
        if ( $secure === true ) {
          $this->emit( 'secure', array( $socket ) );
        } else {
          $this->emit( 'error', array( 'Secure handshake failed' ) );
        }
        
      };
      $this->loop->addWriteStream( $socket, $enableCrypto );
      $this->loop->addReadStream( $socket, $enableCrypto );
    } );
  }
  
  function handleSocket( $socket ){
    $this->retry_count = 0;
    $this->connection = new Stream( $socket, $this->loop );
    $events = array( 'close', 'end', 'pause', 'resume', 'error' );
    $this->connection->on( 'data', array( $this, 'handleData' ) );
    $this->forwardEvents( $this, $this->connection, $events);
    $this->connection->on( 'close', array( $this, 'retryConnection' ) );
  }
  
  public function handleData( $data ){
    $this->emit( 'data', array( $data ) );
  }
  
  // WritableInterface
  public function isWritable(){
    return $this->connection->isWritable();
  }
  
  public function write($data){
    return $this->connection->write( $data );
  }
  
  public function end($data = null){
    return $this->connection->end( $data );
  }
  
  // Readable interface
  public function isReadable(){
    return $this->connection->isReadable();
  }
  
  public function pause(){
    return $this->connection->pause();
  }
  
  public function resume(){
    return $this->connection->resume();
  }
  
  public function close(){
    return $this->connection->close();
  }
  
  function pipe(WritableStreamInterface $dest, array $options = array()){
    Util::pipe($this, $dest, $options);

    return $dest;
  }
  
  static function forwardEvents( EventEmitterInterface $to, EventEmitterInterface $from, array $events ){
    foreach( $events as $event ) {
      $from->on( $event, function() use( $event, $to ){
        $event_args = func_get_args();
        call_user_func_array( array( $to, 'emit' ), array( $event, $event_args ) );
      });
    }
  }
  
}