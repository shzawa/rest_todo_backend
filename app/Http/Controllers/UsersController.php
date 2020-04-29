<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;

class UsersController extends Controller
{
  /**
   * ユーザー一覧出力
   *
   * @return \Illuminate\Http\Response
   */
  public function index(): \Illuminate\Http\Response
  {
    $users = User::select(['id', 'name', 'email'])
              ->get();

    return response(['result' => $users]);
  }

  /**
   * ユーザー登録
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(Request $request): \Illuminate\Http\Response
  {
    $rules = [
      'name' => 'required|max:64|unique:users',
      'email' => 'required|max:255|email:strict,dns|unique:users',
      'password' => 'required|max:255'
    ];
    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) return response(['result' => $validator->messages()], 400);

    $newToken = bin2hex(random_bytes(32));
    $hashedPassword = password_hash($request->password, PASSWORD_BCRYPT);
    $user = User::create([
      'name' => $request->name,
      'email' => $request->email,
      'hash_id' => $newToken,
      'password' => $hashedPassword
    ]);
    if (!$user) return response(['message' => 'failed create record'], 500);

    return response([
              'result' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
              ]
            ], 201)
            ->header('uid', $newToken);
  }

  /**
   * ユーザー詳細出力
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function show(int $id): \Illuminate\Http\Response
  {
    $user = User::select(['id', 'name', 'email'])
              ->where('id', $id)
              ->with('todos')
              ->first();

    if (!$user) return response(['message' => 'User not found'], 404);

    return response(['result' => $user]);
  }

  /**
   * ユーザー更新
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function update(Request $request, int $id): \Illuminate\Http\Response
  {
    if (!$request->headers->has('uid')) return response(null, 401);

    $rules = [
      'name' => 'required|max:64|unique:users',
      'email' => 'required|max:255|email:strict,dns|unique:users',
    ];
    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) return response(['result' => $validator->messages()], 400);

    $user = User::find($id);
    if (!$user) return response(['message' => 'User not found'], 404);

    // ログインユーザー自身でなければアクセス不可
    if ($user->hash_id !== $request->header('uid')) return response(null, 403);

    $user->name = $request->name;
    $user->email = $request->email;
    if ($user->save()) return response(['message' => ['failed update record']], 500);

    return response([
      'result' => [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email
      ]
    ], 200);
  }

  /**
   * ユーザー退会
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy(Request $request): \Illuminate\Http\Response
  {
    if (!$request->headers->has('uid')) return response(null, 401);

    $user = User::select(['id', 'name', 'email'])
              ->where('hash_id', $request->header('uid'))
              ->first();

    if (!$user) return response(['message' => 'User not found'], 404);

    $deleteNum = User::destory($user->id);
    if ($deleteNum === 0) return response(['message' => 'failed delete record'], 500);

    return response(['result' => $user], 204);
  }

  /**
   * ユーザーログイン
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function login(Request $request): \Illuminate\Http\Response
  {
    $rules = [
      'email' => 'required|max:255|email:strict,dns',
      'password' => 'required|max:255'
    ];
    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) return response(['result' => $validator->messages()], 400);

    $user = User::where('email', $request->email)->first();
    if (!$user) return response(['message' => 'Unregistered email address'], 404);

    if (!password_verify($request->password, $user->password)) {
      return response(['message' => 'Login failed...'], 404);
    }

    $newToken = bin2hex(random_bytes(32));
    $user->hash_id = $newToken;
    if (!$user->save()) return response(['message' => 'failed update record'], 500);

    return response([
              'message' => 'Login successfully!',
              'result' => $user
            ], 200)
            ->header('uid', $newToken);
  }
}
