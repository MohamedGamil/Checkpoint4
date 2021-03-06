<?php

namespace Techademia\Http\Controllers\Auth;

use Mail;
use Socialite;
use Validator;
use Techademia\User;
use Illuminate\Http\Request;
use Techademia\AuthenticateUser;
use Illuminate\Support\Facades\Auth;
use Techademia\Http\Controllers\Controller;
use Techademia\Repositories\UserRepository;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;


class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */
    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    private $repository;
    /**
     * @param UserRepository $repository
     */
    public function __construct(AuthenticateUser $authenticate, UserRepository $repository)
    {
        $this->middleware('guest', ['except' => 'getLogout']);
        $this->repository = $repository;
        $this->authenticate = $authenticate;
    }

    /**
     * Returns user registration view
     *
     * @param none
     * @return
     */
    public function getRegister()
    {
        $user = Auth::user();
        return view('pages.register', compact('user'));
    }


    /**
     * Returns the login view
     *
     * @return
     */
    public function getLogin(Request $request)
    {
        return view('pages.login');
    }

    /**
     * Handles normal login
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function postLogin(Request $request)
    {
        $v = Validator::make($request->all(), [
            'email'         => 'required',
            'password'      => 'required',
        ]);

        $authStatus = Auth::attempt($request->only(['email', 'password']));

        if (! $authStatus) {
            return redirect()->back()->with('warning', 'Credentials supplied do not match our records.');
        }

        return redirect('/feeds');
    }

    /**
     * Log out current user
     *
     * @return
     */
    public function getLogout()
    {
        Auth::logout();
        return redirect('/');
    }


    /**
     * Handles user registration
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function postRegister(Request $request)
    {
        $this->validate($request, [
            'fullname'      => 'required',
            'username'      => 'required|max:255',
            'occupation'    => 'required',
            'email'         => 'required|email|unique:users',
            'password'      => 'required',
            'terms'         => 'accepted'
        ]);

        $data = $request->all();
        $data['avatar'] = 'http://goo.gl/1j6BFk';
        $data['password'] = bcrypt($request->input('password'));
        
        $this->repository->create($data);

        return redirect('/auth/login')->with('status', 'Great job! Please Login to continue.');
    }


    /**
     * Handles social authentication
     * @param \Illuminate\Http\Request $request
     * @return
     */
    public function doSocial(Request $request, $provider)
    {
        return $this->authenticate->execute(($request->has('code') || $request->has('oauth_token')) , $this, $provider) ;
    }
}
