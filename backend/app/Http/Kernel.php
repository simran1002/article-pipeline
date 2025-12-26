<?php

namespace App\Http;

use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Kernel implements KernelContract
{
    protected $middleware = [];

    protected $middlewareGroups = [
        'web' => [],
        'api' => [],
    ];

    public function __construct(
        protected Application $app,
        protected Router $router
    ) {}

    public function handle($request)
    {
        try {
            // Add CORS headers
            $response = $this->router->dispatch($request);
            
            // Ensure CORS headers are set
            if (method_exists($response, 'header')) {
                $response->header('Access-Control-Allow-Origin', 'http://localhost:3001');
                $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
                $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            }
            
            return $response;
        } catch (\Exception $e) {
            return $this->handleException($request, $e);
        }
    }

    public function terminate($request, $response)
    {
        //
    }

    protected function handleException($request, $e)
    {
        http_response_code(500);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: http://localhost:3001');
        return new Response(json_encode([
            'error' => 'Server Error',
            'message' => $e->getMessage(),
        ]), 500);
    }
}
