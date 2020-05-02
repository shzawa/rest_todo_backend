<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;

use App\Rules\NonSpace;

class UsersController extends Controller
{
  private $user;

  private $regRules = [
    'name' => 'required|non-space|regex:/^[a-zA-Z0-9\s　]+$/|max:64|unique:users',
    'email' => 'required|non-space|regex:/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/|max:255|email:strict,dns|unique:users',
    'password' => 'required|non-space|regex:/^[a-zA-Z0-9\s　]+$/|max:255'
  ];

  private $acsRules = [
    'name' => 'sometimes|required|non-space|regex:/^[a-zA-Z0-9\s　]+$/|max:64',
    'email' => 'required|non-space|regex:/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/|max:255|email:strict,dns',
    'password' => 'sometimes|required|non-space|regex:/^[a-zA-Z0-9\s　]+$/|max:255'
  ];

  public function __construct(User $user)
  {
    $this->user = $user;
  }

  /**
   * ユーザー一覧出力
   *
   * @return \Illuminate\Http\Response
   */
  public function index(): \Illuminate\Http\Response
  {
    $users = $this->user
              ->select(['id', 'name', 'email'])
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
    $validator = Validator::make($request->all(), $this->regRules);
    if ($validator->fails()) return response(['result' => $validator->messages()], 400);

    $newToken = bin2hex(random_bytes(32));
    $hashedPassword = password_hash($request->password, PASSWORD_BCRYPT);
    $this->user->name = $request->name;
    $this->user->email = $request->email;
    $this->user->hash_id = $newToken;
    $this->user->password = $hashedPassword;
    if (!$this->user->save()) return response(['message' => 'failed create record'], 500);

    return response([
              'result' => [
                "id" => $this->user->id,
                "uid" => $newToken,
                "name" => $this->user->name,
                "email" => $this->user->email,
              ]
            ], 201)
            ->header("uid", $newToken);
  }

  /**
   * ユーザー詳細出力
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function show(int $id): \Illuminate\Http\Response
  {
    $user = $this->user
              ->select(['id', 'name', 'email'])
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
    $validationMsgs = [];

    if (!$request->headers->has('uid')) return response(null, 401);

    $user = $this->user->find($id);
    if (!$user) return response(['message' => 'User not found'], 404);

    // ログインユーザー自身でなければアクセス不可
    if ($user->hash_id !== $request->header('uid')) return response(null, 403);

    $validator = Validator::make($request->all(), $this->acsRules);
    if ($validator->fails()) return response(['result' => $validator->messages()], 400);

    /**
     * [memo]
     *  IlluminateDatabaseQueryExceptionでunique制約エラーをキャッチする場合、
     *  セキュリティ上の問題により、どのカラムでエラーが起きたかを検知することができない。
     *  これを対処する為に以下の独自バリデーションを実装している。が、きちんとするならば独自例外として実装したい…
     */

    /**
     * [memo]
     * $this->user->where('id', '!=', $user->id) を変数に入れて使い回すのが上手く行かない
     * (nameもしくはemailそれぞれのテストで500エラー)
     * 原因不明…
     */

    $validationMsgs = [];

    // ログインユーザー本人以外を対象
    $isNameOverLapped = $this->user
                          ->where('id', '!=', $user->id)
                          ->where('name', $request->name)
                          ->exists();
    if ($isNameOverLapped) {
      $validationMsgs['name'] = 'name param is duplicate with other users';
    }

    $isEmailOverLapped = $this->user
                          ->where('id', '!=', $user->id)
                          ->where('email', $request->email)
                          ->exists();
    if ($isEmailOverLapped) {
      $validationMsgs['email'] = 'email param is duplicate with other users';
    }

    if ($validationMsgs) {
      return response(['message' => $validationMsgs], 400);
    }

    $user->name = $request->name;
    $user->email = $request->email;
    if (!$user->save()) return response(['message' => ['failed update record']], 500);

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

    $user = $this->user
              ->select(['id', 'name', 'email'])
              ->where('hash_id', $request->header('uid'))
              ->first();
    if (!$user) return response(['message' => 'User not found'], 404);

    if (!$user->delete()) return response(['message' => 'failed delete record'], 500);

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
    $validator = Validator::make($request->all(), $this->acsRules);
    if ($validator->fails()) return response(['message' => $validator->messages()], 400);

    $user = $this->user
              ->where('email', $request->email)
              ->first();
    if (!$user) return response(['message' => 'Unregistered email address'], 404);

    if (!password_verify($request->password, $user->password)) {
      return response(['message' => 'Login failed...'], 404);
    }

    $newToken = bin2hex(random_bytes(32));
    $user->hash_id = $newToken;
    if (!$user->save()) return response(['message' => 'failed update record'], 500);

    return response([
              'message' => 'Login successfully!',
              'result' => [
                'id' => $user->id,
                'uid' => $user->hash_id,
                'name' => $user->name,
                'email' => $user->email
              ]
            ], 200)
            ->header('uid', $newToken);
  }
}
