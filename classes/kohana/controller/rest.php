<?php
abstract class Kohana_Controller_Rest extends Controller
{
	/**
	 * @var Object Request Payload
	 */
	protected $_request_payload = null;

	protected $_request_format = null;

	protected $_response_code = 200;

	protected $_response_headers = array
	(
		'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate',
		'Content-Type' => 'application/json'
	);

	/**
	 * @var Object Response Payload
	 */
	protected $_response = array('_links' => array());

	/**
	 * @var array Map of HTTP methods -> actions
	 */
	protected $_action_map = array
	(
		Http_Request::POST   => 'post',   // Typically Create..
		Http_Request::GET    => 'get',
		Http_Request::PUT    => 'put',    // Typically Update..
		Http_Request::DELETE => 'delete',
		Http_Request::OPTIONS => 'options', // Gets available methods for a resource
		Http_Request::HEAD => 'head',
		'PATCH'              => 'patch',
	);

	protected $_formats = array
	(
		'application/json',
		'text/html'
	);

	/**
	 * @var array List of HTTP methods which support body content
	 */
	protected $_methods_with_body_content = array
	(
		Http_Request::POST,
		Http_Request::PUT,
		'PATCH'
	);

	/**
	 * @var array List of HTTP methods which may be cached
	 */
	protected $_cacheable_methods = array
	(
		Http_Request::GET,
	);

	/**
	 * Parse the request...
	 */
	protected function _parse_request()
	{
		// Override the method if needed.
		$this->request->method(Arr::get(
			$_SERVER,
			'HTTP_X_HTTP_METHOD_OVERRIDE',
			$this->request->method()
		));

		// Is that a valid method?
		if ( ! isset($this->_action_map[$this->request->method()]))
		{
			// TODO .. add to the if (maybe??) .. method_exists($this, 'action_'.$this->request->method())
			throw new Http_Exception_405('The :method method is not supported. Supported methods are :allowed_methods', array(
				':method'          => $method,
				':allowed_methods' => implode(', ', array_keys($this->_action_map)),
			));
		}

		// Are we be expecting body content as part of the request?
		if (in_array($this->request->method(), $this->_methods_with_body_content) AND $this->request->body() != '')
		{
			try
			{
				$this->_request_payload = json_decode($this->request->body(), TRUE);

				if ( ! is_array($this->_request_payload) AND ! is_object($this->_request_payload))
					throw new Http_Exception_400('Invalid json supplied. \':json\'', array(
						':json' => $this->request->body(),
					));
			}
			catch (Exception $e)
			{
				throw new Http_Exception_400('Invalid json supplied. \':json\'', array(
					':json' => $this->request->body(),
				));
			}
		}

		$this->_request_format = $this->request->headers('Accept');

		if(!$this->_request_format OR $this->_request_format == '/')
		{
			$this->_request_format = 'application/json';
		}
		elseif(!in_array($this->_request_format, $this->_formats))
		{
			$this->_request_format = 'application/json';

			throw new HTTP_Exception_405('Bad Content-Type: '.$this->request->headers('Accept'));
		}
	}

	/**
	 * Execute the API call..
	 */
	public function action_index()
	{
		try
		{
			$this->_parse_request();

			// Get the basic verb based action..
			$action = $this->_action_map[$this->request->method()];

			// If we are acting on a collection, append _collection to the action name.
			if ($this->request->param('id', FALSE) === FALSE)
			{
				$action .= '_collection';
			}

			if (method_exists($this, $action))
			{
				try
				{
					$this->{$action}();
				}
				catch (Exception $e)
				{
					$this->response->status(500);
					$this->_response = NULL;
				}
			}
			else
			{
				throw new HTTP_Exception_404('The requested URL :uri was not found on this server.', array(
					':uri' => $this->request->uri()
				));
			}

		}
		catch (HTTP_Exception $e)
		{
			$this->_response_code = $e->getCode();

			$this->_response = array(
				'error' => TRUE,
				'type' => 'http',
				'message' => $e->getMessage(),
				'code'    => $e->getCode(),
			);
		}
		catch (Exception $e)
		{
			$this->_response_code = 500;

			$this->_response = array(
				'error' => TRUE,
				'type' => 'exception',
				'message' => $e->getMessage(),
				'code'    => $e->getCode(),
			);
		}

		if($this->_request_format == 'text/html')
		{
			$this->_response_headers['Content-Type'] = 'text/html';
		}
		elseif($this->_request_format == 'application/json')
		{
			// Format the reponse as JSON
			$this->_response = json_encode($this->_response);
		}

		if(!is_string($this->_response))
		{
			$this->_response_headers['Content-Type'] = 'application/json';
			$this->_response_code = 500;
			$this->_response = json_encode
			(
				array
				(
					'error' => true,
					'type' => 'exception',
					'message' => 'Error formatting response',
					'code' => 500
				)
			);
		}

		$this->response->body($this->_response);

		// Should we prevent this request from being cached?
		if (in_array($this->request->method(), $this->_cacheable_methods))
		{
			$this->_response_headers['cache-control'] = 'cache, store';
		}

		$this->response->status($this->_response_code);

		// Set the headers
		foreach($this->_response_headers as $key => $value)
		{
			$this->response->headers($key, $value);
		}
	}

	protected function _get_page()
	{
		$offset = Arr::get($_GET, 'offset', 0);
		$limit = Arr::get($_GET, 'limit', 100);

		if($offset < 0)
		{
			$offset = 0;
		}

		if($limit > 100)
		{
			$limit = 100;
		}

		return array($offset, $limit);
	}
}