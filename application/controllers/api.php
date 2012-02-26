<?php

class Api_Controller extends Base_Controller {

	public $restful = true;

	public function get_pages()
	{
		$pages = Page::where('deleted', '!=', true)->get();

		$output = array();

		foreach ($pages as $page) {
			$output[] = $page->attributes;
		}

		return Response::make(json_encode($output), 200, array('Content-Type' => 'application/json'));
	}

	public function get_page($id)
	{
		$page = Page::where('deleted', '!=', true)->where('id', '=', $id)->first();

		if($page == null)
			return JSONUtils::createResponse(404, 'Not Found', 'No item exists with the requested id');

		return Response::make(json_encode($page->attributes), 200, array('Content-Type' => 'application/json'));
	}

	public function post_page()
	{
		$page = new Page;

		$page->title = Input::get('title');
		$page->displayorder = Input::get('displayorder');

		// Set the user id from the authed user
		//$page->user_id = 

		$page->save();

		return Response::make(json_encode($page->attributes), 201, array('Content-Type' => 'application/json'));
	}

	public function put_page($id)
	{
		$page = Page::where('deleted', '!=', true)->where('id', '=', $id)->first();

		if($page == null)
			return JSONUtils::createResponse(404, 'Not Found', 'No item exists with the requested id');

		$page->title = Input::get('title');
		$page->displayorder = Input::get('displayorder');

		$page->save();

		return Response::make(json_encode($page->attributes), 200, array('Content-Type' => 'application/json'));
	}

	public function delete_page($id)
	{
		$page = Page::where('deleted', '!=', true)->where('id', '=', $id)->first();

		if($page == null)
			return JSONUtils::createResponse(404, 'Not Found', 'No item exists with the requested id');

		$page->deleted = true;

		$page->save();

		return Response::make(json_encode($page->attributes), 200, array('Content-Type' => 'application/json'));
	}
}