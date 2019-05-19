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
        $router[] = new Route('api/definition', 'Definition:classification');
        $router[] = new Route('api/cmocl/definition', 'Definition:cmocl');
        $router[] = new Route('api/cmocl/', 'CMoCL:base');
        $router[] = new Route('api/cmocl/<source>/<period>/', 'CMoCL:dates');
        $router[] = new Route('api/cmocl/<source>/<period>/<date>/', 'CMoCL:findRecord');
        $router[] = new Route('api/cmocl/<source>/<period>/<from>/<to>/', 'CMoCL:findRecords');
        $router[] = new Route('api/<action>', 'Api:groups');
        $router[] = new Route('api/<action>', 'Api:groups');
        $router[] = new Route('classify/<action>[/<id>]', 'ClassifyAjaxInterface:default');
        $router[] = new Route('<presenter>/<action>[/<id>]', 'Home:default');
//        $router[] = new Route('<presenter>/<action>[/<id>]', 'Classification:default');
		return $router;
	}
}
