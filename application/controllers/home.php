<?php

class Home_Controller extends Base_Controller {

    public function __construct()
    {
        $this->filter('before', 'auth');
    }

	public function action_index()
	{
        // Get the first page id by display order: this is our default index page
        $id = DB::only('select id from pages where deleted = 0 and user_id = ? order by displayorder limit 1', Auth::user()->id);

		return View::make('home.page')
                   ->with('pageid', $id);
	}

	public function action_page($id)
	{
		return View::make('home.page')
				   ->with('pageid', $id);
	}
}