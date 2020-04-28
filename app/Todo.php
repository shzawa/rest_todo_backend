<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
  protected $fillable = [
    'title', 'isDone', 'user_id'
  ];

  public function user()
  {
      return $this->belongsTo('App\User');
  }
}
