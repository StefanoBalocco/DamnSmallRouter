<?php
/*
   Copyright (c) 2014-2018, Stefano Balocco <stefano.balocco@gmail.com>
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
class Router
{
	private static $instance;

	protected $routes = array( );
	protected $route403 = array( null, null );
	protected $route404 = array( null, null );
	protected $route405 = array( null, null );
	protected $route500 = array( null, null );

	private function __construct( )
	{

	}

	private static function CalculateWeight( $route )
	{
		$returnValue = null;
		$route = preg_replace( '/[\w]+/', '', $route );
		$last = 0;
		while( false !== ( $last = strpos( $route, '@', $last + 1 ) ) )
		{
			$returnValue = dechex( $last + 1 ) . $returnValue;
			if( 0 == strlen( $returnValue ) )
			{
				$returnValue = '00';
			}
			elseif( ( 1 == ( strlen( $returnValue ) % 2 ) ) )
			{
				$returnValue = '0' . $returnValue;
			}
		}
		return ( strlen( $returnValue ) ? $returnValue : '00' );
	}

	public static function GetInstance( )
	{
		if( !self::$instance )
		{
			self::$instance = new self( );
			self::$instance->AddRoute403
			(
				function( )
				{
					header( 'HTTP/1.1 403 Forbidden' );
					return( null );
				},
				array( )
			);
			self::$instance->AddRoute404
			(
				function( )
				{
					header( 'HTTP/1.1 404 Not found' );
					return( null );
				},
				array( )
			);
			self::$instance->AddRoute405
			(
				function( )
				{
					header( 'HTTP/1.1 405 Method not allowed' );
					return( null );
				},
				array( )
			);
			self::$instance->AddRoute500
			(
				function( )
				{
					header( 'HTTP/1.1 500 Internal server error' );
					return( null );
				},
				array( )
			);
		}
		return self::$instance;
	}

	public static function AddRoute403( $callback, $variables = array( ) )
	{
		self::GetInstance( )->route403 = array( $callback, $variables );
	}

	public static function AddRoute404( $callback, $variables = array( ) )
	{
		self::GetInstance( )->route404 = array( $callback, $variables );
	}

	public static function AddRoute405( $callback, $variables = array( ) )
	{
		self::GetInstance( )->route405 = array( $callback, $variables );
	}

	public static function AddRoute500( $callback, $variables = array( ) )
	{
		self::GetInstance( )->route500 = array( $callback, $variables );
	}

	public static function AddRoute( $route, $callback, $variables = array( ), $method = 'GET', $available = true )
	{
		$routes = &self::GetInstance( )->routes;
		$id = md5( $route );
		if( !array_key_exists( $id, $routes ) )
		{
			$routes[ $id ] = array
			(
				'regex' => '@^' . preg_replace( '/@([\w]+)/', '([^\/]+)', preg_quote( $route ) ) . '$@',
				'weight' => self::CalculateWeight( $route ),
				'methods' => array( )
			);
		}
		$routes[ $id ][ 'methods' ][ $method ] = array( $callback, $variables, $available );
	}

	public static function Route( )
	{
		$returnValue = null;
		$routes = self::GetInstance( )->routes;
		$method = ( ( 'HEAD' == $_SERVER[ 'REQUEST_METHOD' ] ) ? 'GET' : $_SERVER[ 'REQUEST_METHOD' ] );
		$callback = array( null, null, null );
		foreach( $routes as $route )
		{
			$matches = array( );
			if( preg_match( $route[ 'regex' ], $_SERVER[ 'PATH_INFO' ], $matches ) && ( is_null( $callback[ 2 ] ) || ( $callback[ 2 ] > $route[ 'weight' ] ) ) )
			{
				if( array_key_exists( $method, $route[ 'methods' ] ) )
				{
					if( $route[ 'methods' ][ $method ][ 2 ] )
					{
						if( is_callable( $route[ 'methods' ][ $method ][ 0 ] ) )
						{
							$callback = array
							(
								$route[ 'methods' ][ $method ][ 0 ],
								array_merge( $route[ 'methods' ][ $method ][ 1 ], array_splice( $matches, 1 ) ),
								$route[ 'weight' ]
							);
						}
						else
						{
							$callback = self::GetInstance( )->route500;
							$callback[ 2 ] = $route[ 'weight' ];
						}
					}
					else
					{
						$callback = self::GetInstance( )->route403;
						$callback[ 2 ] = $route[ 'weight' ];
					}
				}
				else
				{
					$callback = self::GetInstance( )->route405;
					$callback[ 2 ] = $route[ 'weight' ];
				}
			}
		}
		if( is_null( $callback[ 0 ] ) )
		{
			$callback = self::GetInstance( )->route404;
		}
		if( !is_null( $callback[ 0 ] ) )
		{
			$returnValue = call_user_func_array( $callback[ 0 ], $callback[ 1 ] );
		}
		return $returnValue;
	}
}
?>
