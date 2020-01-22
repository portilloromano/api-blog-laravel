<?php

namespace App\Http\Middleware;

use Closure;

class ApiAuthAdmMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        if ($checkToken) {
            $isAdmin = $jwtAuth->isAdmin($token);
            if ($isAdmin) {
                return $next($request);
            }
        }

        $data = [
            'code'      => 400,
            'status'    => 'error',
            'message'   => 'No posee permisos para realizar esta operación.'
        ];

        return response()->json($data, $data['code']);
    }
}
