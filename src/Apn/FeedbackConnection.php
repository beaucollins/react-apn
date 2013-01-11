<?php
namespace Apn;

class FeedbackConnection extends Connection {
  
  const MESSAGE_LENGTH = 38;
  
  public $retry_timeout = 20;
  
  function handleData( $data ){
    $pos = 0;
    while( $pos < strlen( $data )){
      $message = $this->parseMessage( substr( $data, $pos, self::MESSAGE_LENGTH ) );
      $this->emit( 'feedback', array( $message ) );
      $pos += self::MESSAGE_LENGTH;
    }
  }
  
  function parseMessage( $data ){
    return @unpack( 'N1timestamp/n1length/H*device_token', $data );
  }
  
}