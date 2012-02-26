<?php

class Home_Controller extends Base_Controller {

	public $layout = 'layouts.main';

	public function action_index()
	{
		$this->layout->content = View::make('home.index');
	}

	public function action_page($id)
	{
		$page = Page::find($id);

		echo(json_encode($page->attributes));

		$this->layout->content = View::make('home.page')
								     ->with('page', $page);
	}
}