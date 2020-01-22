<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('api.auth', ['except' => [
            'register',
            'login',
            'getImage',
            'detail'
        ]]);
    }

    public function register(Request $request)
    {
        $json = $request->input('json', null);

        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if (!empty($params) && !empty($params_array)) {
            $params_array = array_map('trim', $params_array);

            $validate = \Validator::make($params_array, [
                'name'      => 'required|regex:/^[a-zA-ZÀ-ÿ\s]+$/u',
                'surname'   => 'required|regex:/^[a-zA-ZÀ-ÿ\s]+$/u',
                'email'     => 'required|email|unique:users',
                'password'  => 'required'
            ], [
                'email.unique' => 'Ya existe un usuario con ese email'
            ]);

            if ($validate->fails()) {
                $data = [
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'El usuario no se ha creado',
                    'errors' => $validate->errors()
                ];
            } else {
                $pwd = hash('sha256', $params->password);

                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->password = $pwd;
                $user->role = 'ROLE_USER';

                $user->save();

                $data = [
                    'status' => 'success',
                    'code' => 201,
                    'message' => 'El usuario se ha creado correctamente',
                    'user' => $user
                ];
            }
        } else {
            $data = [
                'status' => 'error',
                'code' => 404,
                'message' => 'Los datos enviados no son correctos'
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function login(Request $request)
    {
        $jwtAuth = new \JwtAuth();

        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if (!empty($params) && !empty($params_array)) {
            $params_array = array_map('trim', $params_array);

            $validate = \Validator::make($params_array, [
                'email'     => 'required|email',
                'password'  => 'required'
            ]);

            if ($validate->fails()) {
                $data = [
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Los datos enviados no son correctos',
                    'errors' => $validate->errors()
                ];
            } else {
                $pwd = hash('sha256', $params->password);

                $data = $jwtAuth->signup($params->email, $pwd);

                if (!empty($params->getToken)) {
                    $data = $jwtAuth->signup($params->email, $pwd, true);
                }
            }
        } else {
            $data = [
                'status' => 'error',
                'code' => 404,
                'message' => 'Los datos enviados no son correctos'
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function update(Request $request)
    {
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();

        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            $user = $jwtAuth->checkToken($token, true);

            $validate = \Validator::make($params_array, [
                'name'      => 'required|regex:/^[a-zA-ZÀ-ÿ\s]+$/u',
                'surname'   => 'required|regex:/^[a-zA-ZÀ-ÿ\s]+$/u',
                'email'     => 'required|email|unique:users,email,'.$user->sub
            ], [
                'email.unique' => 'Ya existe un usuario con ese email'
            ]);

            if ($validate->fails()) {
                $data = [
                    'code'      => 400,
                    'status'    => 'error',
                    'message'   => 'No se ha podido editar el usuario.',
                    'errors'    => $validate->errors()
                ];
            } else {
                unset($params_array['id']);
                unset($params_array['role']);
                unset($params_array['created_at']);
                unset($params_array['remember_token']);

                if(empty(trim($params_array['password']))){
                    unset($params_array['password']);
                } else {
                    $params_array['password'] = hash('sha256', $params_array['password']);
                }

                $params_array['updated_at'] = new \DateTime('now');

                $user_update = User::where(
                    'id',
                    $user->sub
                )->update($params_array);

                $data = [
                    'code'      => 200,
                    'status'    => 'success',
                    'message'   => 'El usuario se ha editado correctamente',
                    'user'      => $user_update
                ];
            }
        } else {
            $data = [
                'status' => 'error',
                'code' => 404,
                'message' => 'Los datos enviados no son correctos'
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function upload(Request $request)
    {
        $image = $request->file('file0');

        $validate = \Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);

        if (!$image || $validate->fails()) {
            $data = [
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'Error al subir la imagen'
            ];
        } else {
            $image_name = time() . $image->getClientOriginalName();
            \Storage::disk('users')->put($image_name, \File::get($image));

            $data = [
                'code'      => 200,
                'status'    => 'success',
                'image'     => $image_name
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function getImage($filename)
    {
        $isSet = \Storage::disk('users')->exists($filename);

        if ($isSet) {
            $file = \Storage::disk('users')->get($filename);
            return new Response($file, 200);
        } else {
            $data = [
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'La imagen no existe.'
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function detail($id)
    {
        $user = User::find($id);
        if (is_object($user)) {
            $data = [
                'code'      => 200,
                'status'    => 'success',
                'user'      => $user
            ];
        } else {
            $data = [
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'El usuario no existe.'
            ];
        }

        return response()->json($data, $data['code']);
    }
}
