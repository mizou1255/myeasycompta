<?php

namespace ECWP\API;

class Routes
{
    protected $routes = [];

    public function register_routes()
    {
        // S'assurer que les routes sont enregistrées au bon moment
        if (did_action('rest_api_init')) {
            // Si rest_api_init a déjà été déclenché, enregistrer immédiatement
            foreach ($this->routes as $route) {
                register_rest_route('my-easy-compta/v1', $route['route'], [
                    'methods' => $route['methods'],
                    'callback' => [$route['class'], $route['callback']],
                    'permission_callback' => $route['permission_callback'],
                ]);
            }
        } else {
            // Sinon, attendre le hook rest_api_init
            add_action('rest_api_init', function () {
                foreach ($this->routes as $route) {
                    register_rest_route('my-easy-compta/v1', $route['route'], [
                        'methods' => $route['methods'],
                        'callback' => [$route['class'], $route['callback']],
                        'permission_callback' => $route['permission_callback'],
                    ]);
                }
            }, 10);
        }
    }

    public function add_route($route, $methods, $class, $callback, $permission_callback)
    {
        $this->routes[] = [
            'route' => $route,
            'methods' => $methods,
            'class' => $class,
            'callback' => $callback,
            'permission_callback' => $permission_callback,
        ];
    }
}