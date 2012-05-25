<?php

class User_Controller extends Base_Controller {

    public $restful = true;

    public function __construct()
    {
        $this->filter('before', 'csrf')->on('post');
    }

    public function get_signup()
    {
        return View::make('user.signup');
    }    

    public function post_signup()
    {
        $rules = array(
            'email' => 'required|unique:users',
            'password' => 'required|confirmed'
        );               

        $validator = Validator::make(Input::get(), $rules);

        if (!$validator->valid())
        {
            return Redirect::to('user/signup')
                           ->with('content', View::make('user.signup'))
                           ->with('errors', $validator->errors)
                           ->with_input('except', array('password'));
        }

        $user = new User;

        // TODO: need some input validation here! Does Laravel help with this already?
        $user->email = Input::get('email');
        $user->password = Hash::make(Input::get('password'));

        $user->save();

        // Add the user's initial Page and Item
        $page = new Page;
        $page->title = "Your First Page";
        $page->displayorder = 0;
        $page->user_id = $user->id;
        $page->save();

        $item = new Item;
        $item->title = "Welcome to GTD-Pad";
        $item->body = "This is your first item. Use the controls to the left to edit or delete it."; //
        $item->displayorder = 0;
        $item->user_id = $user->id;
        $item->page_id = $page->id;
        $item->save();

        return Redirect::to('user/login')
                           ->with('content', View::make('user.login'))
                           ->with_input('except', array('password'));
    } 

    public function get_login()
    {
        return View::make('user.login');
    }    

    public function post_login()
    {
        $rules = array(
            'email' => 'required',
            'password' => 'required'
        );               

        $validator = Validator::make(Input::get(), $rules);

        if (!$validator->valid())
        {
            return Redirect::to('user/login')
                           ->with('content', View::make('user.login'))
                           ->with('errors', $validator->errors)
                           ->with_input('except', array('password'));
        }

        $email = Input::get('email');
        $password = Input::get('password');
        $remember = (Input::get('remember') === "1") ? true : false;

        $credentials = array('username' => $email, 'password' => $password, 'remember' => $remember);

        if (Auth::attempt($credentials))
        {
             return Redirect::to('');
        }

        return Redirect::to('user/login')
                           ->with('content', View::make('user.login'))
                           ->with_input('except', array('password'));
    }    

    public function get_logout()
    {
        Auth::logout();

        return Redirect::to('');
    }

    public function get_hashpassword($password)
    {
        return View::make('user.hashpassword')
                   ->with('hash', Hash::make($password));
    }

	/*public function action_session($provider)
	{
	    Bundle::start('laravel-oauth2');

		$oauth_config = Config::get('auth.oauth_app_details');

	    $provider = OAuth2::provider($provider, $oauth_config[$provider]);

	    if (!isset($_GET['code']))
	    {
	        // By sending no options it'll come back here
	        return $provider->authorize();
	    }
	    else
	    {
	        // Howzit?
	        try
	        {
	            $params = $provider->access($_GET['code']);

	            //$user = $provider->get_user_info($params);

                $token = new OAuth2_Token_Access(array('access_token' => $params->access_token));
                $user = $provider->get_user_info($token);

	            // Here you should use this information to A) look for a user B) help a new user sign up with existing data.
	            // If you store it all in a cookie and redirect to a registration page this is crazy-simple.
	            echo "<pre>";
	            var_dump($params);
	            var_dump($user);
	        }

	        catch (OAuth2_Exception $e)
	        {
	            show_error('That didnt work: '.$e);
	        }

	    }
	}*/

}