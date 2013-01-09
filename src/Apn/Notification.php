<?php
namespace Apn;
/**
 * For formatting an APN enhanced notification
 *
 * @package default
 * @author Beau Collins
 */
class Notification {
  
  const COMMAND_PUSH = 1;
  const DEVICE_BINARY_SIZE = 32;
	
  private $badge = 0;
  private $alert;
  private $message_id = 0;
  private $device;
  private $sound;
  
  public $meta;
  
  function __construct( $token, $message_id = 0, $expires = -1 ){
    $this->token = $token;
    $this->message_id = $message_id;
    $this->expires = $expires > 0 ? $this->expires : time();
    $this->meta = array();
  }
  
  function setAlert( $alert ){
    $this->alert = $alert;
  }
  
  function getAlert(){
    return $this->alert;
  }
  
  function setBadge($badge){
    $this->badge = $badge;
  }
  
  function getBadge($badge){
    return $this->badge;
  }
  
  function setSound($sound){
    $this->sound = $sound;
  }
  
  function getSound($sound){
    return $this->sound;
  }
  
  function encode(){
    $aps = array(
      'alert' => $this->alert;
    );
    if( $this->getSound() ) $aps['sound'] = $this->getSound();
    if( $this->getBadge() ) $aps['badge'] = $this->getBadge();
    
    $note = array(
      'aps' => $aps
    );
    $note = array_merge( $this->meta, $note );
    $payload = json_encode( $note );
    $encoded = pack( 'CNNnH*n',
      self::COMMAND_PUSH,
      $this->message_id,
      $this->expires,
      self::DEVICE_BINARY_SIZE,
      $this->token,
      strlen( $payload )
    );
     
    $encoded .= $payload;
    
    return $encoded;
    
  }
  
}