<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TodosTableSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    DB::table('todos')
      ->insert(
        [
          [
            'title' => 'foobar1',
            'isDone' => '0',
            'user_id' => '1',
            'created_at' => '2020/04/01 00:00:00',
            'updated_at' => '2020/04/01 00:00:00'
          ],
          [
            'title' => 'foobar2',
            'isDone' => '1',
            'user_id' => '1',
            'created_at' => '2020/04/01 00:00:00',
            'updated_at' => '2020/04/01 00:00:00'
          ]
        ]
      );

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
  }
}
