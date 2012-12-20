<?php

/**
 * Description of url
 *
 * @author noah
 * @date $(date)
 */
class Rest_URL extends Kohana_URL
{
	public static function is_absolute($url)
	{
		return strpos($url, '://') !== false;
	}

	public static function link(array $link)
	{
		if(Request::$current->referrer() != 'internal')
		{
			if(!Rest_URL::is_absolute($link['href']))
			{
				$link['href'] = URL::site($link['href'], true, false);
			}

		}

		if(isset($link['args']))
		{
			foreach($link['args'] as $key => $param)
			{
				if(is_callable($param))
				{
					$param = $param();
				}

				$link['args'][$key] = $param;
			}

			$link['href'] = strtr($link['href'], $link['args']);
		}

		if(!isset($link['type']))
		{
			$link['type'] = 'application/json';
		}

		if(!isset($link['method']))
		{
			$link['method'] = 'GET';
		}

		return $link;
	}
}
