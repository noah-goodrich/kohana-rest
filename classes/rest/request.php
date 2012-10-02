<?php defined('SYSPATH') or die('No direct script access.');

class Rest_Request extends Kohana_Request
{

	/**
	 * @var  array  cached links
	 */
	protected static $_links = array();

	/**
	 * @var  array  default variables for use in Request::follow_link()
	 */
	protected static $_default_params = array
	(
		'body' => array(),
		'accept' => 'application/json',
		'query' => array(),
	);

	/**
	 * Get the url from local.php config file and append any resources
	 *
	 * @param   string resource
	 * @return  string
	 */
	public static function get_url($resource = NULL)
	{
		$base = \Kohana::$config->load('local.api');

		if ($resource !== null)
		{
			$base.= '?resource='.$resource;
		}

		return $base;
	}

	/**
	 * Get a particular link by name from a list of links returned from a resource
	 *
	 * @param   string link name
	 * @param   string resource name
	 * @return  stdClass
	 */
	public static function get_link($link, $resource = null)
	{
		// Get the list of all the links
		$links = self::get_links($resource);

		// Return the requested link object
		return $links->$link;
	}

	/**
	 * Get a list of links from a resource request
	 *
	 * @param   string resource name
	 * @return  stdClass
	 */
	public static function get_links($resource)
	{
		if ($resource instanceof stdClass AND isset($resource->_links))
		{
			// Use the links provided with the resource
			return $resource->_links;
		}

		// Look for cached links and use them if they already exist
		if (empty(self::$_links[$resource]))
		{
			// Get the url to use
			$url = self::get_url($resource);

			// Cache the result so we can reuse these links without having to make another request
			self::$_links[$resource] = self::phone_home($url, 'OPTIONS')->response->_links;
		}

		return self::$_links[$resource];
	}

	/**
	 * Shortcut to follow a particular link from a resource. Params can be keys to replace
	 * params in the URL and also values for `body`, `accept` and `fields`
	 *
	 *     // Follow a link to get a form for a particular user
	 *     $params = array
	 *     (
	 *     	':id' => 21,
	 *     	'fields' => array('email', 'business_phone'),
	 *     );
	 *     $response = Request::follow_link('form', $params);
	 *
	 * @param   string link name
	 * @param   array parameters
	 * @return  stdClass
	 */
	public static function follow_link($link, $params = array())
	{
		// Look for specific values
		foreach (self::$_default_params as $param => $default)
		{
			if (isset($params[$param]))
			{
				// Set the variable to the passed value
				$$param = $params[$param];
				// Unset it from the params array so we don't accidentally replace bad values
				// in the URL
				unset($params[$param]);
			}
			else
			{
				// Otherwise set the variable to the default value
				$$param = $default;
			}
		}

		// Find all the types available for this link
		$types = explode(';', $link->type);
		if ( ! in_array($accept, $types))
		{
			// If the requested type to accept is not allowed in the link, throw an exception
			throw new Kohana_Exception($accept.' is not an allowed type. Allowed types: '.$link->type);
		}

		// Use the link's href as the url
		$href = $link->href;

		if ( ! empty($params))
		{
			// Replace any key/value pairs left in the URL
			$href = strtr($href, $params);
		}

		if ($query)
		{
			// Attach query params to the end of the url
			$href.= '?';

			$i = 0;
			foreach ($query as $field => $values)
			{
				if ($i > 0)
				{
					$href.= '&';
				}

				if (is_array($values))
				{
					$href.= $field.'='.implode(',', $values);
				}
				else
				{
					$href.= $field.'='.$values;
				}

				$i++;
			}
		}

		// Make the phone_home call and return the response
		return self::phone_home($href, $link->method, 'application/json', $body);
	}

	/**
	 * Phone home to a URL and get a response, and decode the json object returned
	 *
	 * @param   string url to request from
	 * @param   string method type
	 * @param   string response type to accept
	 * @param   array body of request
	 * @return  object
	 */
	public static function phone_home($url, $method = 'GET', $accept = 'application/json', $body = array())
	{
		// Create and execute the request
		$response = Request::factory($url)
			->method($method)
			->body(json_encode($body))
			->headers('Accept', $accept)
			->headers('Authorization', 'Basic MWUxY2U3NmZiMDlhNDc4NmUxZDdiYzk1ZTIxMGE4YzlkNmZiYTQwNjM4N2JjYjJiMzUyNDMwMjMyMGIzNzdmOToxZjYwNWU0MDBmZjc4MTZlNDAxZjE2ZDk2NTAwOTAxOTQyMzRlOWU1ODRmNTIyZDA4ZTc1ODM4Zjg1M2IzNGQ2==')
			->headers('Content-Type', 'application/json')
			->execute();

		// Pull out the body of the response
		$body = $response->body();

		if($response->headers('Content-Type') == 'application/json')
		{
			// Set $response->response to a stdClass only if was a json type
			$response->response = (object) json_decode($response->body());
		}

		// Return the response object
		return $response;
	}
}