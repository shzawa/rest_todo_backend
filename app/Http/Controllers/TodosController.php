<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Todo;
use App\User;

class TodosController extends Controller
{
  /**
   * Todo一覧出力
   *
   * @return \Illuminate\Http\Response
   */
  public function index(): \Illuminate\Http\Response
  {
    $todos = Todo::with(['user' => function($q) {
                $q->select(['users.id', 'users.name', 'users.email']);
              }])
              ->get();

    return response(['result' => $todos]);
  }

  /**
   * Todo登録
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(Request $request): \Illuminate\Http\Response
  {
    if (!$request->headers->has('uid')) return response(null, 401);

    $rules = ['title' => 'required|max:255'];
    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) return response(['result' => $validator->messages()], 400);

    $user = User::where('hash_id', $request->header('uid'))->first();
    if (!$user) return response(['message' => 'Please login'], 401);

    $todo = new Todo;
    $todo->title = $request->title;
    $todo->user_id = $user->id;
    if (!$todo->save()) return response(['message' => 'failed update record create record'], 500);

    return response(['result' => $todo], 201);
  }

  /**
   * Todo詳細出力
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function show(int $id): \Illuminate\Http\Response
  {
    $todo = Todo::find($id);

    if (!$todo) return response(['message' => 'Todo not found'], 404);

    return response(['result' => $todo]);
  }

  /**
   * Todo更新
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function update(Request $request, int $id): \Illuminate\Http\Response
  {
    if (!$request->headers->has('uid')) return response(null, 401);

    $rules = [
      'title' => 'required|max:255',
      'isDone' => 'required|max:1|boolean',
    ];
    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) return response(['result' => $validator->messages()], 400);

    $user = User::where('hash_id', $request->header('uid'))->first();
    if (!$user) return response(['message' => 'Please login'], 401);

    $todo = Todo::find($id);
    if (!$todo) return response(['message' => 'Todo not found'], 404);

    // ログインユーザーが投稿者自身でなければアクセス不可
    if ($user->id !== $todo->user_id) return response(null, 401);

    $todo->title = $request->title;
    $todo->isDone = $request->isDone;
    if (!$todo->save()) return response(['message' => 'failed update record'], 500);

    return response(['result' => $todo], 200);
  }

  /**
   * Todo削除
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy(Request $request , int $id): \Illuminate\Http\Response
  {
    if (!$request->headers->has('uid')) return response(null, 401);

    $user = User::where('hash_id', $request->header('uid'))->first();
    if (!$user) return response(['message' => 'Please login'], 401);

    $todo = Todo::find($id);
    if (!$todo) return response(['message' => 'Todo not found'], 404);

    // ログインユーザーが投稿者自身でなければアクセス不可
    if ($user->id !== $todo->user_id) return response(null, 401);

    $deleteNum = Todo::destroy($id);
    if ($deleteNum === 0) return response(['message' => 'failed delete record'], 500);

    return response(['result' => $todo], 204);
  }
}
