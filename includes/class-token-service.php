<?php
namespace WCTE;
defined( 'ABSPATH' ) || exit;
final class Token_Service {
	public function generate() {
		$raw = rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' );
		return array( 'raw' => $raw, 'hash' => $this->hash( $raw ) );
	}
	public function hash( $raw ) { return hash_hmac( 'sha256', (string) $raw, wp_salt( 'auth' ) ); }
	public function url( $raw ) { return home_url( '/training/redeem/' . rawurlencode( $raw ) . '/' ); }
	public function seal( $raw ) { $key=hash('sha256',wp_salt('secure_auth'),true); $iv=random_bytes(12); $tag=''; $cipher=openssl_encrypt($raw,'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag); return base64_encode($iv.$tag.$cipher); }
	public function open( $sealed ) { $data=base64_decode((string)$sealed,true); if(!$data||strlen($data)<29)return ''; $key=hash('sha256',wp_salt('secure_auth'),true); return (string)openssl_decrypt(substr($data,28),'aes-256-gcm',$key,OPENSSL_RAW_DATA,substr($data,0,12),substr($data,12,16)); }
}
