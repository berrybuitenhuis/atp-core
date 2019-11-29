<?php

namespace AtpCore\Zf\Route;

use Zend\Router\Http\Segment;

class Route
{

    /**
     * @param array $controllerAliases
     * @return array
     */
    public static function getChildRoutes($controllerAliases)
    {
        // Create child-routes
        $childRoutes = [];
        foreach ($controllerAliases AS $alias => $controller) {
            // Set child-route (general)
            $childRoute = [];
            $childRoute['type'] = Segment::class;
            $childRoute['options'] = [
                'route'    => '/:version/' . $alias . '/:action[/:actionId][/]',
                'constraints' => [
                    'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    'actionId' => '[0-9]+',
                ],
                'defaults' => [
                    'controller' => $controller,
                ],
            ];
            // Add child-route to child-routes
            $childRoute['options']['route'] = preg_replace('/\/{2,}/', '/', $childRoute['options']['route']);
            $childRoute['options']['route'] = preg_replace('/\/\[\//', '[/', $childRoute['options']['route']);
            $childRoutes[$alias . "_general"] = $childRoute;

            // Set child-route (specific)
            $childRoute = [];
            $childRoute['type'] = Segment::class;
            $childRoute['options'] = [
                'route' => '/:version/' . $alias . '[/:id][/:action][/:actionId][/]',
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
            $childRoutes[$alias . "_specific"] = $childRoute;

            // Set child-route (specific_subaction)
            $childRoute = [];
            $childRoute['type'] = Segment::class;
            $childRoute['options'] = [
                'route'    => '/:version/' . $alias . '/:id/:mainAction/:mainActionId/:action[/:actionId][/]',
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
            $childRoutes[$alias . "_specific_subaction"] = $childRoute;
        }

        // Return
        return $childRoutes;
    }

}