<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

class UsersController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index()
  {
      return User::all()->toJson();
  }

  /**
   * ユーザー新規登録
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(Request $request)
  {
    $token = bin2hex(random_bytes(64));
    $hashedPassword = password_hash($request->password, PASSWORD_BCRYPT);

    $user = new User;
    $user->name = $request->name;
    $user->email = $user->email;
    $user->hash_id = $token;
    $user->password = $hashedPassword;
    $user->save();

    return response(201)
      ->header('uid', $token)
      ->json($user);
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function show($id)
  {
    $user = User::find($id);

    if (!$user) return response('User not found', 404);

    return response()->json([
      'id' => $user->id,
      'name' => $user->name,
      'email' => $user->email
    ]);
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
    $user = User::find($id);

    if (!$user) return response('User not found', 404);

    // ログインユーザー自身でなければアクセス不可
    if ($user->hash_id !== $request->header('uid')) return response(401);

    $token = bin2hex(random_bytes(64));
    $hashedPassword = password_hash($request->password, PASSWORD_BCRYPT);

    $user->name = $request->name;
    $user->email = $request->email;
    $user->password = $hashedPassword;
    $user->hash_id = $token;
    $user->save();

    return response(204)
      ->header('uid', $token)
      ->json($user);
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy($id)
  {
    $user = User::find($id);

    if (!$user) return response('User not found', 404);

    // ログインユーザー自身でなければアクセス不可
    if ($user->hash_id !== $request->header('uid')) return response(401);

    User::destroy($id);
    return response(204)->json([
      'id' => $user->id,
      'name' => $user->name,
      'email' => $user->email
    ]);
  }
}
