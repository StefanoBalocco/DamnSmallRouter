<?php
/*
   Copyright (c) 2014, Stefano Balocco
   All rights reserved.

   Redistribution and use in source and binary forms, with or without
   modification, are permitted provided that the following conditions are met:

   * Redistributions of source code must retain the above copyright notice, this
     list of conditions and the following disclaimer.

   * Redistributions in binary form must reproduce the above copyright notice,
     this list of conditions and the following disclaimer in the documentation
     and/or other materials provided with the distribution.

   * Neither the name of the {organization} nor the names of its
     contributors may be used to endorse or promote products derived from
     this software without specific prior written permission.

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
	protected $route404 = array( );

	private function __construct( )
	{

	}

	public static function GetInstance( )
	{
		if( !self::$instance )
		{ 
			self::$instance = new self( );
		}
		return self::$instance; 
	}

	public static function AddRoute( $route, $callback, $variables = array( ), $method = 'GET' )
	{
		$routes = &self::GetInstance( )->routes;
		if( !array_key_exists( $method, self::GetInstance( )->routes ) )
		{
			$routes[ $method ] = array( );
		}
		$routes[ $method ][ ] = array( '@^' . preg_replace( '/@([\w]+)/', '([^\/]+)', preg_quote( $route ) ) . '(?:\/)?$@', $callback, $variables );
	}

	public static function AddRoute404( $callback, $variables = array( ) )
	{
		self::GetInstance( )->route404 = array( $callback, $variables );
	}

	public static function Route( )
	{
		$returnValue = null;
		$error404 = true;
		$routes = self::GetInstance( )->routes;
		if( array_key_exists( 'REQUEST_METHOD', $_SERVER ) && array_key_exists( $_SERVER[ 'REQUEST_METHOD' ], $routes ) )
		{
			$routes = $routes[ $_SERVER[ 'REQUEST_METHOD' ] ];
			$routesCount = count( $routes );
			for( $i = 0; ( $i < $routesCount ) && $error404; $i++ )
			{
				$matches = array( );
				if( preg_match( $routes[ $i ][ 0 ], $_SERVER[ 'PATH_INFO' ], $matches ) && is_callable( $routes[ $i ][ 1 ] ) )
				{
					$returnValue = call_user_func_array( $routes[ $i ][ 1 ], array_merge( $routes[ $i ][ 2 ], array_splice( $matches, 1 ) ) );
					$error404 = false;
					break;
				}
			}
		}
		$route404 = self::GetInstance( )->route404;
		$error404 &= !empty( $route404 );
		if( ( null == $returnValue ) && $error404 )
		{
			$returnValue = call_user_func_array( $route404[ 0 ], $route404[ 1 ] );
		}
		return $returnValue;
	}
}
?>
