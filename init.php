<?php
/**
 * Extra HTTP Status Code Messages
 */
Response::$messages[102] = 'Processing';
Response::$messages[207] = 'Multi-Status';
Response::$messages[422] = 'Unprocessable Entity';
Response::$messages[423] = 'Locked';
Response::$messages[424] = 'Failed Dependency';
Response::$messages[507] = 'Insufficient Storage';

/**
 * Routes
 */

/*Route::set
(
	'api', '<controller>(/<id>)(/<custom>)', array('id' => '\d+')
)
->subdomains(array('api'))
->defaults
(
	array
	(
		'directory'  => 'api',
		'id'         => FALSE,
		'action'     => 'index'
	)
);*/
