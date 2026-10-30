<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class googlecontroller extends Controller
{
    //

    public function index() {

        return Socialite::driver('google')->redirect();
    }

    public function verify() {

        $gUser = Socialite::driver('google')->user();
        $user = User::where('email', $gUser->email)->first();

        if($user) {

            $user->update([
                'google_id' => $gUser->id,
                'name' => $gUser->name,
                'google_token' => $gUser->token,
                'google_refresh_token' => $gUser->refreshToken,
                'google_avatar' => $gUser->avatar,
            ]);

        }else {

            $user = User::create([
                'google_id' => $gUser->id,
                'name' => $gUser->name,
                'email' => $gUser->email,
                'google_token' => $gUser->token,
                'google_refresh_token' => $gUser->refreshToken,
                'google_avatar' => $gUser->avatar,
                'password' => bcrypt('12345678'), // Default password, can be changed later
            ]);

        }
        Auth::login($user);

         $notification = array(
            'message' => 'Welcome to Dashbaord Successfully',
            'alert-type' => 'success'
         ); 

        return redirect()->route('dashboard')->with($notification);
    }
}
