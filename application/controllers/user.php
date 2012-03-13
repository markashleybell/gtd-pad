<?php

class User_Controller extends Base_Controller {

    public $restful = true;

	public $layout = 'layouts.main';

    public function get_login()
    {
        $this->layout->content = View::make('user.login');
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

        if (Auth::attempt($email, $password))
        {
             return Redirect::to('home/index');
        }

        return Redirect::to('user/login')
                           ->with('content', View::make('user.login'))
                           ->with_input('except', array('password'));
    }    

    public function get_logout()
    {
        Auth::logout();

        return Redirect::to('home/index');
    }

    public function get_hashpassword($password)
    {
        $this->layout->content = View::make('user.hashpassword')
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