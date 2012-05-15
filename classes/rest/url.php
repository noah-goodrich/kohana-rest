<?php

/**
 * Description of url
 *
 * @author noah
 * @date $(date)
 */
class Rest_URL extends Kohana_URL
{
	public static function link($method, $rel, $uri, $parameters = array(), $type = 'application/json')
	{
		$uri = URL::base().$uri;

		strtr($uri, $parameters);

		$link = array(
			'method'	=> $method,
			'rel'		=> $rel,
			'url'		=> $uri,
			'type'		=> $type,
		);

		return $link;
	}
}
