<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    DB::table('users')
      ->insert([
        'hash_id' => 'bfb6e77bd7168d4c3f65d952716f26d357af9a0805acb93e8bf728f976799c25',
        'name' => 'test0',
        'email' => 'test0@gmail.com',
        'password' => '$2y$10$l/PDud72xPIwEhEx93MxU..adwWETNJC.oz9teqSpxEqzEaiYdIzi',
        'created_at' => '2020/04/01 00:00:00',
        'updated_at' => '2020/04/01 00:00:00'
      ]);

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
  }
}
