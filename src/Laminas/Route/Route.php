<?php

namespace AtpCore\Laminas\Route;

use Laminas\Router\Http\Segment;

class Route
{

    /**
     * @param array $controllerSections
     * @return array
     */
    public static function getChildRoutes($controllerSections)
    {
        // Create child-routes
        $childRoutes = [];
        foreach ($controllerSections AS $section => $controllerAliases) {
            if (is_array($controllerAliases)) {
                foreach ($controllerAliases AS $alias => $controller) {
                    $aliasName = $section . "_" . $alias;
                    $routePrefix = '/:version/' . $section . '/' . $alias;
                    $routes = self::generateChildRoutes($aliasName, $routePrefix, $controller);
                    $childRoutes = array_merge($childRoutes, $routes);
                }
            } else {
                $alias = $section;
                $controller = $controllerAliases;
                $aliasName = $alias;
                $routePrefix = '/:version/' . $alias;
                $routes = self::generateChildRoutes($aliasName, $routePrefix, $controller);
                $childRoutes = array_merge($childRoutes, $routes);
            }
        }

        // Return
        return $childRoutes;
    }

    /**
     * Generate routes
     *
     * @param $aliasName
     * @param $routePrefix
     * @param $controller
     * @return array
     */
    private static function generateChildRoutes($aliasName, $routePrefix, $controller)
    {
        // Set child-route (general)
        $childRoute = [];
        $childRoute['type'] = Segment::class;
        $childRoute['options'] = [
            'route'    => $routePrefix . '/:action[/:actionId][/:subAction][/]',
            'constraints' => [
                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                'actionId' => '[0-9]+',
                'subAction' => '[a-zA-Z]+',
            ],
            'defaults' => [
                'controller' => $controller,
            ],
        ];
        // Add child-route to child-routes
        $childRoute['options']['route'] = preg_replace('/\/{2,}/', '/', $childRoute['options']['route']);
        $childRoute['options']['route'] = preg_replace('/\/\[\//', '[/', $childRoute['options']['route']);
        $childRoutes[$aliasName . "_general"] = $childRoute;

        // Set child-route (specific)
        $childRoute = [];
        $childRoute['type'] = Segment::class;
        $childRoute['options'] = [
            'route' => $routePrefix . '[/:id][/:action][/:actionId][/]',
            'constraints' => [
                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                'id' => '[0-9]+',
                'actionId' => '[0-9]+',
            ],
            'defaults' => [
                'controller' => $controller,
            ],
        ];
        // Add child-route to child-routes
        $childRoute['options']['route'] = preg_replace('/\/{2,}/', '/', $childRoute['options']['route']);
        $childRoute['options']['route'] = preg_replace('/\/\[\//', '[/', $childRoute['options']['route']);
        $childRoutes[$aliasName . "_specific"] = $childRoute;

        // Set child-route (specific_subaction)
        $childRoute = [];
        $childRoute['type'] = Segment::class;
        $childRoute['options'] = [
            'route'    => $routePrefix . '/:id/:mainAction/:mainActionId/:action[/:actionId][/]',
            'constraints' => [
                'mainAction' => '[a-zA-Z][a-zA-Z0-9_-]*',
                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                'id' => '[0-9]+',
                'mainActionId' => '[0-9]+',
                'actionId' => '[0-9]+',
            ],
            'defaults' => [
                'controller' => $controller,
            ],
        ];
        // Add child-route to child-routes
        $childRoute['options']['route'] = preg_replace('/\/{2,}/', '/', $childRoute['options']['route']);
        $childRoute['options']['route'] = preg_replace('/\/\[\//', '[/', $childRoute['options']['route']);
        $childRoutes[$aliasName . "_specific_subaction"] = $childRoute;

        // Return
        return $childRoutes;
    }
}