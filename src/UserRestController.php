<?php
/**
 *  Laravel-RestApi (http://github.com/malhal/Laravel-RestApi)
 *
 *  Created by Malcolm Hall on 14/9/2016.
 *  Copyright © 2016 Malcolm Hall. All rights reserved.
 */

namespace Malhal\RestApi;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

class UserRestController extends RestController
{
    use AuthenticatesUsers;

    protected $authorizeRequests = true;

    protected $createRules = [
        //'name' => 'required|max:255',
        'email' => 'required|email|max:255|unique:users',
        'password' => 'required|min:6'
    ];

    public function getModelClass()
    {
        return config('auth.providers.users.model');
    }

    protected function restCreate(Request $request, $model)
    {
        $password = $request->json()->get('password');
        if (!is_null($password)) {
            $request->json()->set('password', bcrypt($password));
            $model->setAttribute('api_token', Str::random(60));
            $model->makeVisible(['api_token']);
            Auth::setUser($model);
        }
        return parent::restCreate($request, $model);
    }

    protected function guard()
    {
        return Auth::guard('web');
    }

    public function username()
    {
        return 'id';
    }

    // had to reimplement this method to remove the call to session.
    protected function sendLoginResponse(Request $request)
    {
        $this->clearLoginAttempts($request);

        return true;
    }

    protected function sendLockoutResponse(Request $request)
    {
        $seconds = $this->limiter()->availableIn(
            $this->throttleKey($request)
        );

        $message = Lang::get('auth.throttle', ['seconds' => $seconds]); // e.g. Too many login attempts. Please try again in 60 seconds.

        throw new TooManyRequestsHttpException($seconds, $message);
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        return false;
    }

    public function show(Request $request, $id)
    {
        if($request->has('password')) {
            $request->query->set('id', $id);
            if(!$this->login($request)){
                throw new AuthenticationException();
            }
            Auth::setUser($this->guard()->user());
        }
        return parent::show($request, $id); // TODO: Change the autogenerated stub
    }

    protected function restView(Request $request, $model){
//        if($request->has('password')) {
//            if (!Auth::guard('web')->getProvider()->validateCredentials($model, ['password' => $request->query->get('password')])){
//                throw new AuthenticationException();
//            }
        if(Auth::user() == $model){
            $model->makeVisible(['api_token']);
        }
        return $model;
    }

    public function index(Request $request)
    {
        $this->missingMethod();
    }
}
