<?php
namespace Apn;
/**
 * $gateway = new GatewayService();
 *
 * @package default
 * @author Beau Collins
 */
class GatewayConnection extends Connection {
  
  const MESSAGE_LENGTH = 6;
  
  public function sendNotification( Notification $notification ){
    $this->write( $notification->encode() );
  }
  
  function handleData( $data ){
    $pos = 0;
    while( $pos < strlen( $data )){
      $message = $this->parseMessage( substr( $data, $pos, self::MESSAGE_LENGTH ) );
      $this->emit( 'notification-error', array( $message ) );
      $pos += self::MESSAGE_LENGTH;
    }
  }
  
  function parseMessage( $data ){
    return @unpack( 'Ccommand/CstatusCode/Nidentifier', $data );
  }
  
}