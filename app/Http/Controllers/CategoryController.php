<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Category;
use App\Post;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('api.authAdm', ['except' => [
            'index',
            'show'
        ]]);
    }

    public function index()
    {
        $categories = Category::all();

        $data = [
            'code'          => 200,
            'status'        => 'success',
            'categories'    => $categories
        ];

        return response()->json($data, $data['code']);
    }

    public function show($id)
    {
        $category = Category::find($id);

        if (is_object($category)) {
            $data = [
                'code'      => 200,
                'status'    => 'success',
                'category'  => $category
            ];
        } else {
            $data = [
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'La categoria no existe'
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function store(Request $request)
    {
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            $validate = \Validator::make($params_array, [
                'name' => 'required|unique:categories'
            ], [
                'name.unique' => 'Ya existe una categoría con ese nombre.'
            ]);

            if ($validate->fails()) {
                $data = [
                    'code'      => 400,
                    'status'    => 'error',
                    'message'   => 'No se ha guardado la categoria.',
                    'errors'    => $validate->errors()
                ];
            } else {
                $category = new Category();
                $category->name = $params_array['name'];
                $category->save();

                $data = [
                    'code'      => 201,
                    'status'    => 'success',
                    'message'   => 'La categoría se ha creado correctamente',
                    'category'  => $category
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
            $validate = \Validator::make($params_array, [
                'name' => 'required'
            ]);

            if ($validate->fails()) {
                $data = [
                    'code'      => 400,
                    'status'    => 'error',
                    'message'   => 'No se ha editado la categoria.',
                    'errors'    => $validate->errors()
                ];
            } else {
                unset($params_array['id']);
                unset($params_array['created_at']);

                $category = Category::where(
                    'id',
                    $id
                )->updateOrCreate($params_array);

                $data = [
                    'status'    => 'success',
                    'code'      => 200,
                    'category'  => $category
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
        $category = Category::find($id);

        if (!empty($category)) {
            $posts = Post::where(
                'category_id',
                $category->id
            )->update('category_id', 1);

            $category->delete();

            $data = [
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'La categoría ha sido borrada y sus posts se movieron a la categoría general.'
            ];
        } else {
            $data = [
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'Los datos enviados no son correctos'
            ];
        }
    }
}
