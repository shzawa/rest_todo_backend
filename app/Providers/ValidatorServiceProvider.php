<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Validators\NonSpaceValidator;

class ValidatorServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   *
   * @return void
   */
  public function register()
  {
    //
  }

  /**
   * Bootstrap services.
   *
   * @return void
   */
  public function boot()
  {
    $this->app['validator']->resolver(function($translator, $data, $rules, $messages, $attributes) {
      return new NonSpaceValidator($translator, $data, $rules, $messages, $attributes);
    });
  }
}
