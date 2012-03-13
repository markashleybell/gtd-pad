<?php

class Auth_Controller extends Base_Controller {

    p
	public $layout = 'layouts.main';

    public function action_login($provider)
    {
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