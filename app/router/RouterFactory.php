<?php

namespace ClassifyRSA;

use Nette;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;


class RouterFactory
{
	use Nette\StaticClass;

	/**
	 * @return Nette\Application\IRouter
	 */
	public static function createRouter()
	{
		$router = new RouteList;
        $router[] = new Route('api/<action>', 'Api:groups');
        $router[] = new Route('<presenter>/<action>[/<id>]', 'NewInterface:default');
//        $router[] = new Route('<presenter>/<action>[/<id>]', 'Classification:default');
		return $router;
	}
}
