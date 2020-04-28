<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Todo;

class TodosController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index()
  {
    return Todo::all()->toJson();
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(Request $request)
  {
    $user = User::where('hash_id', $request->header('uid'))->first();

    if (!$user) return response('Please login', 401);

    $todo = new Todo;
    $todo->title = $request->title;
    $todo->user_id = $user->id;
    $todo->save();
    return response(201)->json(Todo::find($todo->id));
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function show($id)
  {
    $todo = Todo::find($request->id)->first();

    if (!$todo) {
      return response('Todo not found', 404);
    }

    return $todo->toJson();
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function update(Request $request, $id)
  {
    $user = User::where('hash_id', $request->header('uid'))->first();
    if (!$user) return response('Please login', 401);

    $todo = Todo::find($id)->first();
    if (!$todo) return response('Todo not found', 404);

    // ログインユーザーが投稿者自身でなければアクセス不可
    if ($user->id !== $todo->user_id) return response(401);

    $todo->title = $request->title;
    $todo->isDone = $request->isDone;
    $todo->save();
    return response(204)->json($todo);
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy($id)
  {
    $user = User::where('hash_id', $request->header('uid'))->first();
    if (!$user) return response('Please login', 401);

    $todo = Todo::find($id)->first();
    if (!$todo) return response('Todo not found', 404);

    // ログインユーザーが投稿者自身でなければアクセス不可
    if ($user->id !== $todo->user_id) return response(401);

    Todo::destroy($id);
    return response(204)->json($todo);
  }
}
