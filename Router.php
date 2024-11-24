<?php
/*
   Copyright (c) 2014-2024, Stefano Balocco <stefano.balocco@gmail.com>
   All rights reserved.

   Redistribution and use in source and binary forms, with or without
   modification, are permitted provided that the following conditions are met:

   * Redistributions of source code must retain the above copyright notice, this
     list of conditions and the following disclaimer.

   * Redistributions in binary form must reproduce the above copyright notice,
     this list of conditions and the following disclaimer in the documentation
     and/or other materials provided with the distribution.

   * Neither the name of Stefano Balocco nor the names of its contributors may
     be used to endorse or promote products derived from this software without
     specific prior written permission.

   THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
   AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
   IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
   DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
   FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
   DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
   SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
   CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
   OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
   OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace StefanoBalocco\DamnSmallRouter;

if( !function_exists( 'http_response_code' ) ) {
	function http_response_code( $code = NULL ) {
		if( ( NULL !== $code ) && is_numeric( $code ) ) {
			switch( $code ) {
				case 100: $text = 'Continue'; break;
				case 101: $text = 'Switching Protocols'; break;
				case 200: $text = 'OK'; break;
				case 201: $text = 'Created'; break;
				case 202: $text = 'Accepted'; break;
				case 203: $text = 'Non-Authoritative Information'; break;
				case 204: $text = 'No Content'; break;
				case 205: $text = 'Reset Content'; break;
				case 206: $text = 'Partial Content'; break;
				case 300: $text = 'Multiple Choices'; break;
				case 301: $text = 'Moved Permanently'; break;
				case 302: $text = 'Moved Temporarily'; break;
				case 303: $text = 'See Other'; break;
				case 304: $text = 'Not Modified'; break;
				case 305: $text = 'Use Proxy'; break;
				case 400: $text = 'Bad Request'; break;
				case 401: $text = 'Unauthorized'; break;
				case 402: $text = 'Payment Required'; break;
				case 403: $text = 'Forbidden'; break;
				case 404: $text = 'Not Found'; break;
				case 405: $text = 'Method Not Allowed'; break;
				case 406: $text = 'Not Acceptable'; break;
				case 407: $text = 'Proxy Authentication Required'; break;
				case 408: $text = 'Request Time-out'; break;
				case 409: $text = 'Conflict'; break;
				case 410: $text = 'Gone'; break;
				case 411: $text = 'Length Required'; break;
				case 412: $text = 'Precondition Failed'; break;
				case 413: $text = 'Request Entity Too Large'; break;
				case 414: $text = 'Request-URI Too Large'; break;
				case 415: $text = 'Unsupported Media Type'; break;
				case 500: $text = 'Internal Server Error'; break;
				case 501: $text = 'Not Implemented'; break;
				case 502: $text = 'Bad Gateway'; break;
				case 503: $text = 'Service Unavailable'; break;
				case 504: $text = 'Gateway Time-out'; break;
				case 505: $text = 'HTTP Version not supported'; break;
				default: {
					exit('Unknown http status code "' . htmlentities($code) . '"');
					break;
				}
			}
			$protocol = ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] : 'HTTP/1.0' );
			header( $protocol . ' ' . $code . ' ' . $text );
			$GLOBALS[ 'http_response_code' ] = $code;
		} else {
			$code = ( isset( $GLOBALS[ 'http_response_code' ] ) ? $GLOBALS[ 'http_response_code' ] : 200 );
		}
		return $code;
	}
}

class Router {
	private static function CalculateWeight( $route ) {
		$returnValue = '';
		$route = preg_replace( '/[\w]+/', '', $route );
		$last = -1;
		while( false !== ( $last = strpos( $route, '@', $last + 1 ) ) ) {
			$returnValue = str_pad( dechex( $last + 1 ), 2, '0', STR_PAD_LEFT ) . $returnValue;
		}
		return ( strlen( $returnValue ) ? $returnValue : '00' );
	}

	protected $routes = array( );
	protected $route403 = array( null, null );
	protected $route404 = array( null, null );
	protected $route405 = array( null, null );
	protected $route500 = array( null, null );

	public function __construct( ) {
		$standardErrorRoute = function( $code ) {
			http_response_code( 403 );
			return null;
		};
		$this->AddRoute403( $standardErrorRoute, [ 403 ] );
		$this->AddRoute404( $standardErrorRoute, [ 404 ] );
		$this->AddRoute405( $standardErrorRoute, [ 405 ] );
		$this->AddRoute500( $standardErrorRoute, [ 500 ] );
	}

	public function AddRoute403( $callback, $variables = array( ) ) {
		$this->route403 = array( $callback, $variables );
	}

	public function AddRoute404( $callback, $variables = array( ) ) {
		$this->route404 = array( $callback, $variables );
	}

	public function AddRoute405( $callback, $variables = array( ) ) {
		$this->route405 = array( $callback, $variables );
	}

	public function AddRoute500( $callback, $variables = array( ) ) {
		$this->route500 = array( $callback, $variables );
	}

	public function AddRoute( $route, $callback, $variables = array( ), $method = 'GET', $available = null ) {
		$id = md5( $route );
		if( !array_key_exists( $id, $this->routes ) ) {
			$this->routes[ $id ] = array (
				'regex' => '@^' . str_replace( array( '#AZ09#', '#AZ#', '#09#' ), array( '([\w]+)', '([a-zA-Z]+)', '(\d+)' ), preg_quote( $route ) ) . '$@',
				'weight' => self::CalculateWeight( $route ),
				'methods' => array( )
			);
		}
		if( !isset( $this->routes[ $id ][ 'methods' ][ $method ] ) || !$this->routes[ $id ][ 'methods' ][ $method ][ 2 ] ) {
			$this->routes[ $id ][ 'methods' ][ $method ] = array( $callback, $variables, $available );
		}
	}

	public function RouteAvailable( $method = 'GET', $withoutConditions = false ) {
		$returnValue = false;
		$callback = array( null, null, null );
		foreach( $this->routes as $route ) {
			$matches = array( );
			if( preg_match( $route[ 'regex' ], ( isset( $_SERVER[ 'PATH_INFO' ] ) ? $_SERVER[ 'PATH_INFO' ] : '' ), $matches ) && ( ( null === $callback[ 2 ] ) || ( $callback[ 2 ] > $route[ 'weight' ] ) ) ) {
				if( array_key_exists( $method, $route[ 'methods' ] ) ) {
					if( ( null === $route[ 'methods' ][ $method ][ 2 ] ) || ( !$withoutConditions && $route[ 'methods' ][ $method ][ 2 ] ) ) {
						if( is_callable( $route[ 'methods' ][ $method ][ 0 ] ) ) {
							$returnValue = true;
						}
					}
				}
			}
		}
		return $returnValue;
	}

	public function Route( ) {
		$returnValue = null;
		$method = ( ( 'HEAD' == $_SERVER[ 'REQUEST_METHOD' ] ) ? 'GET' : $_SERVER[ 'REQUEST_METHOD' ] );
		$callback = array( null, null, null );
		foreach( $this->routes as $route ) {
			$matches = array( );
			if( preg_match( $route[ 'regex' ], ( isset( $_SERVER[ 'PATH_INFO' ] ) ? $_SERVER[ 'PATH_INFO' ] : '' ), $matches ) && ( ( null === $callback[ 2 ] ) || ( $callback[ 2 ] > $route[ 'weight' ] ) ) ) {
				if( array_key_exists( $method, $route[ 'methods' ] ) ) {
					if( ( null === $route[ 'methods' ][ $method ][ 2 ] ) || $route[ 'methods' ][ $method ][ 2 ] ) {
						if( is_callable( $route[ 'methods' ][ $method ][ 0 ] ) ) {
							$callback = array(
								$route[ 'methods' ][ $method ][ 0 ],
								array_merge( $route[ 'methods' ][ $method ][ 1 ], array_splice( $matches, 1 ) ),
								$route[ 'weight' ]
							);
						} else {
							$callback = $this->route500;
							$callback[ 2 ] = $route[ 'weight' ];
						}
					} else {
						$callback = $this->route403;
						$callback[ 2 ] = $route[ 'weight' ];
					}
				} else {
					$callback = $this->route405;
					$callback[ 2 ] = $route[ 'weight' ];
				}
			}
		}
		if( null === $callback[ 0 ] ) {
			$callback = $this->route404;
		}
		if( null !== $callback[ 0 ] ) {
			$returnValue = call_user_func_array( $callback[ 0 ], $callback[ 1 ] );
		}
		return $returnValue;
	}
}
