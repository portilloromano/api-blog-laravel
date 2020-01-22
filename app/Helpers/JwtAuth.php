<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use App\User;
use PhpParser\Node\Stmt\TryCatch;

class JwtAuth
{
    public $key;

    public function __construct()
    {
        $this->key = 'u24Z9EJV94LMLr3hmMYS';
    }

    public function signup($email, $password, $getToken = null)
    {
        $user = User::where([
            'email'     => $email,
            'password'  => $password
        ])->first();

        $signup = false;
        if (is_object($user)) {
            $signup = true;
        }

        if ($signup) {
            $token = [
                'sub'       => $user->id,
                'email'     => $user->email,
                'name'      => $user->name,
                'surname'   => $user->surname,
                'image'     => $user->image,
                'iat'       => time(),
                'exp'       => time() + (7 * 24 * 60 * 60)
            ];

            $jwt = JWT::encode($token, $this->key, 'HS256');

            if (is_null($getToken)) {
                $data = [
                    'code'      => 200,
                    'status'    => 'success',
                    'message'   => 'El usuario se ha identificado correctamente.',
                    'token' => $jwt
                ];
            } else {
                $decoded = JWT::decode($jwt, $this->key, ['HS256']);
                $data = [
                    'code'      => 200,
                    'status'    => 'success',
                    'message'   => 'El usuario se ha identificado correctamente.',
                    'identity'  => $decoded
                ];
            }
        } else {
            $data = [
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'Login incorrecto.'
            ];
        }

        return $data;
    }

    public function checkToken($jwt, bool $getIdentity = false)
    {
        $auth = false;
        try {
            $decoded = JWT::decode($jwt, $this->key, ['HS256']);
        } catch (\UnexpectedValueException $e) {
            $auth = false;
        } catch (\DomainException $e) {
            $auth = false;
        }

        if (!empty($decoded) && is_object($decoded) && isset($decoded->sub)) {
            $auth = $getIdentity ? $decoded : true;
        } else {
            $auth = false;
        }

        return $auth;
    }

    public function isAdmin($jwt)
    {
        $admin = false;
        try {
            $decoded = JWT::decode($jwt, $this->key, ['HS256']);
        } catch (\UnexpectedValueException $e) {
            $admin = false;
        } catch (\DomainException $e) {
            $admin = false;
        }

        if (!empty($decoded) && is_object($decoded) && isset($decoded->sub)) {
            $user = User::find($decoded->sub);
            if ($user->role == 'ROLE_ADMIN')
                $admin = true;
        } else {
            $admin = false;
        }

        return $admin;
    }
}
