<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // 自定义验证
        \Validator::resolver(
            function ($translator, array $data, array $rules, array $messages, array $customAttributes) {
                return new \Validation\CustomValidator($translator, $data, $rules, $messages, $customAttributes);
            }
        );
    }
}
