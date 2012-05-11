<?php

/**
 * Description of response
 *
 * @author noah
 * @date $(date)
 */
class Response extends Kohana_Response
{

	const OK = 200;
	const CREATED = 201;
	const ACCEPTED = 202;

	const NOT_MODIFIED = 304;

	const BAD_REQUEST = 400;
	const UNAUTHORIZED = 401;
	const FORBIDDEN = 403;
	const NOT_FOUND = 404;
	const METHOD_NOT_ALLOWED = 405;
	const CONFLICT = 409;
	const GONE = 410;

	const INTERNAL_SERVER_ERROR = 500;
	const NOT_IMPLEMENTED = 501;
	const BAD_GATEWAY = 502;
	const SERVICE_UNAVAILABLE = 503;
}
