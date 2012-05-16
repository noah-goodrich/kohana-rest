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
		'cache-control' => 'no-cache, no-store, max-age=0, must-revalidate',
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

	public function before()
	{
		parent::before();

		$this->_parse_request();
	}

	public function after()
	{
		$this->_prepare_response();

		parent::after();
	}

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
		if (in_array($this->request->method(), $this->_methods_with_body_content))
		{
			$this->_parse_request_body();
		}

		$this->_request_format = $this->request->param('format');
	}

	/**
	 * @todo Support more than just JSON
	 */
	protected function _parse_request_body()
	{
		if ($this->request->body() == '')
			return;

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

	protected function _prepare_response()
	{
		// Should we prevent this request from being cached?
		if (in_array($this->request->method(), $this->_cacheable_methods))
		{
			$this->_response_headers['cache-control'] = 'cache, store';
		}

		$this->response->status($this->_response_code);

		try
		{
			if($this->_request_format == 'html')
			{
				$this->_response_headers['Content-Type'] = 'text/html';

				$this->response->body($this->_response);
			}
			else
			{
				// Format the reponse as JSON
				$this->response->body(json_encode($this->_response));
			}

			// Set the headers
			foreach($this->_response_headers as $key => $value)
			{
				$this->response->headers($key, $value);
			}

		}
		catch (Exception $e)
		{
			Kohana::$log->add(Log::ERROR, 'Error while formatting response: '.$e->getMessage());
			throw new Http_Exception_500('Error while formatting response');
		}
	}

	/**
	 * Execute the API call..
	 */
	public function action_index()
	{
		// Get the basic verb based action..
		$action = $this->_action_map[$this->request->method()];

		// If this is a custom action, lets make sure we use it.
		if ($this->request->param('custom', FALSE) !== FALSE)
		{
			$action .= '_'.$this->request->param('custom');
		}

		// If we are acting on a collection, append _collection to the action name.
		if ($this->request->param('id', FALSE) === FALSE)
		{
			$action .= '_collection';
		}

		// Execute the request
		if (method_exists($this, $action))
		{
			try
			{
				$this->_execute($action);
			}
			catch (Exception $e)
			{
				$this->response->status(500);
				$this->_response = NULL;
			}
		}
		else
		{
			/**
			 * @todo .. HTTP_Exception_405 is more appropriate, sometimes.
			 * Need to figure out a way to decide which to send...
			 */
			throw new HTTP_Exception_404('The requested URL :uri was not found on this server.', array(
				':uri' => $this->request->uri()
			));
		}
	}

	protected function _execute($action)
	{
		try
		{
			$this->{$action}();
		}
		catch (HTTP_Exception $e)
		{
			$this->response->status($e->getCode());

			$this->_response = array(
				'error' => TRUE,
				'type' => 'http',
				'message' => $e->getMessage(),
				'code'    => $e->getCode(),
			);
		}
		catch (Exception $e)
		{
			$this->response->status(500);

			$this->_response = array(
				'error' => TRUE,
				'type' => 'exception',
				'message' => $e->getMessage(),
				'code'    => $e->getCode(),
			);
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