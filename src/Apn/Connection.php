<?php
namespace Apn;
use React\Socket\Connection as SocketConnection;
use React\Socket\ConnectionInterface;
use React\Stream\WritableStreamInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\StreamInterface;
use React\Stream\Util;
use Evenement\EventEmitter;
use Evenement\EventEmitterInterface;

class Connection extends EventEmitter implements ConnectionInterface {
  
  const FEEDBACK_URI = 'ssl://feedback.push.apple.com:2196';
  const GATEWAY_URI = 'ssl://gateway.push.apple.com:2195'; 

  const FEEDBACK_SANDBOX_URI = 'ssl://feedback.sandbox.push.apple.com:2196';
  const GATEWAY_SANDBOX_URI = 'ssl://gateway.sandbox.push.apple.com:2195';   
  
  const CONNECTION_TIMEOUT = 60;
  
  private $connection;
  private $password;
  private $cert;
  private $stream;
  private $loop;
  private $uri;
  
  function __construct( $uri, $cert, $loop ){
    $this->loop = $loop;
    
    if (is_array( $cert ) ) {
      $this->password = $password = $cert['password'];
      $this->cert = $cert = $cert['file'];
    } else {
      $this->$password = null;
    }
        
    $this->stream_context = $stream_context = stream_context_create();
    stream_context_set_option( $stream_context, 'ssl', 'local_cert', $cert );
    if( !empty( $password ) )
      stream_context_set_option( $stream_context, 'ssl', 'passphrase', $password);
    
    $this->uri = $uri;
    
    $this->connect();

  }
  
  function connect(){
    
    $socket_settings = STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT;
    $client = stream_socket_client( $this->uri, $error, $errorString, CONNECTION_TIMEOUT, $socket_settings, $this->stream_context );
    
    if ( !$client ) {
      $this->emit( 'error', array( $error, $errorString ) );
    }
    
    $this->connection = $conn = new SocketConnection($client, $this->loop);
    
    $events = array( 'data', 'error', 'close', 'end', 'pause',' resume' );
    $this->forwardEvents( $this, $this->connection, $events);
    
  }
  
  function reconnect(){
    echo "Reconnect!" . PHP_EOL;
    if ( $this->connection ) $this->connection->close();
    $this->connect();
  }
  
  function getConnection(){
    return $this->connection;
  }
  
  /**
   * note needs to be in the format of
   *
   * @param string $note 
   * @return void
   * @author Beau Collins
   */
  function sendNotification( $note ){
    
    
  }
  
  // ConnectionInterface
  public function getRemoteAddress(){
    return $this->connection->getRemoteAddress();
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