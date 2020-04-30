<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Todo;
use App\User;

use App\Rules\NonSpace;

class TodosController extends Controller
{
  private $todo;
  private $user;
  private $rules;

  public function __construct(Todo $todo, User $user) {
    $this->todo = $todo;
    $this->user = $user;
    $this->rules = [
      'title' => 'required|non-space|max:255',
      'isDone' => 'sometimes|required|regex:/^[0-9]+$/|boolean',
    ];
  }

  /**
   * Todo一覧出力
   *
   * @return \Illuminate\Http\Response
   */
  public function index(): \Illuminate\Http\Response
  {
    $todos = $this->todo
              ->with(['user' => function($q) {
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

    $user = $this->user
              ->where('hash_id', $request->header('uid'))
              ->first();
    if (!$user) return response(['message' => 'Please login'], 401);

    $validator = Validator::make($request->all(), $this->rules);
    if ($validator->fails()) return response(['result' => $validator->messages()], 400);

    $this->todo->title = $request->title;
    $this->todo->user_id = $user->id;
    if (!$this->todo->save()) {
      return response(['message' => 'failed update record create record'], 500);
    }

    return response(['result' => $this->todo], 201);
  }

  /**
   * Todo詳細出力
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function show(int $id): \Illuminate\Http\Response
  {
    $todo = $this->todo->find($id);

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

    $user = $this->user
              ->where('hash_id', $request->header('uid'))
              ->first();
    if (!$user) return response(['message' => 'Please login'], 401);

    $todo = $this->todo->find($id);
    if (!$todo) return response(['message' => 'Todo not found'], 404);

    // ログインユーザーが投稿者自身でなければアクセス不可
    if ($user->id !== $todo->user_id) return response(null, 401);

    $validator = Validator::make($request->all(), $this->rules);
    if ($validator->fails()) return response(['result' => $validator->messages()], 400);

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

    $user = $this->user
              ->where('hash_id', $request->header('uid'))
              ->first();
    if (!$user) return response(['message' => 'Please login'], 401);

    $todo = $this->todo->find($id);
    if (!$todo) return response(['message' => 'Todo not found'], 404);

    // ログインユーザーが投稿者自身でなければアクセス不可
    if ($user->id !== $todo->user_id) return response(null, 401);

    if (!$todo->delete()) return response(['message' => 'failed delete record'], 500);

    return response(['result' => $todo], 204);
  }
}
