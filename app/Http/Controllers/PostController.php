<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Post;
use App\Helpers\JwtAuth;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('api.auth', ['except' => [
            'index',
            'show',
            'getImage',
            'getPostsByCategory',
            'getPostsByUser'
        ]]);
    }

    private function getIdentity(Request $request)
    {
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);

        return $user;
    }

    private function isAdmin(Request $request)
    {
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $isAdmin = $jwtAuth->isAdmin($token);

        return $isAdmin;
    }

    public function index()
    {
        $posts = Post::all()->load('category');

        $data = [
            'code'     => 200,
            'status'   => 'success',
            'posts'    => $posts
        ];

        return response()->json($data, $data['code']);
    }

    public function show($id)
    {
        $post = Post::find($id)
            ->load('category')
            ->load('user');

        if (is_object($post)) {
            $data = [
                'code'      => 200,
                'status'    => 'success',
                'post'      => $post
            ];
        } else {
            $data = [
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'El post no existe'
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function store(Request $request)
    {
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            $identity = $this->getIdentity($request);

            $validate = \Validator::make($params_array, [
                'category_id'   => 'required',
                'title'         => 'required',
                'content'       => 'required'
            ]);

            if ($validate->fails()) {
                $data = [
                    'code'      => 400,
                    'status'    => 'error',
                    'message'   => 'El post no se ha podido crear.',
                    'errors'    => $validate->errors()
                ];
            } else {
                $post = new Post();
                $post->user_id      = $identity->sub;
                $post->category_id  = $params_array['category_id'];
                $post->title        = $params_array['title'];
                $post->content      = $params_array['content'];
                $post->image        = $params_array['image'];
                $post->save();

                $data = [
                    'code'      => 201,
                    'status'    => 'success',
                    'message'   => 'El post se ha creado correctamente.',
                    'post'      => $post
                ];
            }
        } else {
            $data = [
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'Los datos enviados no son correctos'
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function update($id, Request $request)
    {
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            $user = $this->getIdentity($request);
            $isAdmin =  $this->isAdmin($request);

            if ($params_array['user_id'] == $user->sub || $isAdmin) {
                $validate = \Validator::make($params_array, [
                    'category_id'   => 'required',
                    'title'         => 'required',
                    'content'       => 'required'
                ]);

                if ($validate->fails()) {
                    $data = [
                        'code'      => 400,
                        'status'    => 'error',
                        'message'   => 'No se ha podido editar el post.',
                        'errors'    => $validate->errors()
                    ];
                } else {
                    unset($params_array['id']);
                    unset($params_array['user_id']);
                    unset($params_array['created_at']);

                    $params_array['updated_at'] = new \DateTime('now');

                    $post = Post::where(
                        'id',
                        $id
                    )->update($params_array);

                    $data = [
                        'status'    => 'success',
                        'code'      => 200,
                        'message'   => 'El post se ha editado correctamente',
                        'post'      => $post
                    ];
                }
            } else {
                $data = [
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'No posee permisos para realizar esta transacción.'
                ];
            }
        } else {
            $data = [
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'Los datos enviados no son correctos'
            ];
        }

        return response($data, $data['code']);
    }

    public function destroy($id, Request $request)
    {
        $post = Post::find($id);

        if (!empty($post)) {
            $user = $this->getIdentity($request);
            $isAdmin =  $this->isAdmin($request);

            if ($post->user_id == $user->sub || $isAdmin) {
                $post->delete();

                $data = [
                    'status'    => 'success',
                    'code'      => 200,
                    'message'   => 'El post ha sido borrado.',
                    'post'      => $post
                ];
            } else {
                $data = [
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'No posee permisos para realizar esta transacción.'
                ];
            }
        } else {
            $data = [
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'Los datos enviados no son correctos'
            ];
        }
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
            \Storage::disk('images')->put($image_name, \File::get($image));

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
        $isSet = \Storage::disk('images')->exists($filename);

        if ($isSet) {
            $file = \Storage::disk('images')->get($filename);
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

    public function getPostsByCategory($id)
    {
        $posts = Post::where(
            'category_id',
            $id
        )->get();

        $data = [
            'code'     => 200,
            'status'   => 'success',
            'posts'    => $posts
        ];

        return response()->json($data, $data['code']);
    }

    public function getPostsByUser($id)
    {
        $posts = Post::where(
            'user_id',
            $id
        )->get();

        $data = [
            'code'     => 200,
            'status'   => 'success',
            'posts'    => $posts
        ];

        return response()->json($data, $data['code']);
    }
}
