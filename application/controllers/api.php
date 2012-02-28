<?php

class Api_Controller extends Base_Controller {

    public $restful = true;

    // GET: GET /pages/1 or GET /pages
    public function get_pages($id = null)
    {
        // If the id is null, show all pages
        if($id == null)
        {
            $pages = Page::where('deleted', '!=', true)->get();

            $output = array();

            foreach ($pages as $page) {
                $output[] = $page->attributes;
            }

            return Response::make(json_encode($output), 200, array('Content-Type' => 'application/json'));
        }

        $fields = Input::get('fields');

        // If there's a list of fields in the querystring
        if($fields != null)
        {
            try
            {
                // Attempt to retrieve only the requested fields for this record
                $page = Page::where('deleted', '!=', true)->where('id', '=', $id)->get(explode(',', $fields));

                // If null, return 404
                if($page == null)
                    return ApiUtils::createResponse(404, 'Not Found', 'No item exists with the requested id');

                // In this case $page is actually an array because we used get() rather than first(), 
                // so just return the attributes of the first element
                return Response::make(json_encode($page[0]->attributes), 200, array('Content-Type' => 'application/json'));
            }
            catch(Exception $e)
            {
                // In most cases, this will be thrown if the client gets a field name wrong
                return ApiUtils::createResponse(400, 'Bad Request', 'Property list contained one or more invalid property names'); 
            }
        }
        else // Client wants ALL data fields
        {
            $page = Page::where('deleted', '!=', true)->where('id', '=', $id)->first();

            if($page == null)
                return ApiUtils::createResponse(404, 'Not Found', 'No item exists with the requested id');

            return Response::make(json_encode($page->attributes), 200, array('Content-Type' => 'application/json'));
        }
    }

    // CREATE: POST /pages
    public function post_pages($id = null)
    {
        // Prevent POST requests to a url with an id segment
        if($id != null)
            return ApiUtils::createResponse(400, 'Bad Request', 'POST requests to an item/id url are not supported');

        try
        {
            // Create a new page from POST data
            $page = new Page;

            // TODO: need some input validation here! Does Laravel help with this already?
            $page->title = Input::get('title');
            $page->displayorder = Input::get('displayorder');

            // Set the user id from the authed user
            //$page->user_id = 

            $page->save();
        }
        catch(Exception $e)
        {
            // In most cases, this will be thrown if the client gets a field name wrong
            return ApiUtils::createResponse(500, 'Server Error', 'Payload error: please check the fields you are POSTing are correctly named'); 
        }

        // Return all the details of the new page as a JSON response
        return Response::make(json_encode($page->attributes), 201, array('Content-Type' => 'application/json'));
    }

    // UPDATE: PUT /pages/1
    public function put_pages($id = null)
    {
        // Handle the lack of an ID
        if($id == null)
            return ApiUtils::createResponse(400, 'Bad Request', 'You must supply an id for PUT actions');

        // Get the existing page data
        $page = Page::where('deleted', '!=', true)->where('id', '=', $id)->first();

        // If the page doesn't exist, return a helpful message
        if($page == null)
            return ApiUtils::createResponse(404, 'Not Found', 'No item exists with the requested id');

        // Update the data from PUT data fields
        $page->title = Input::get('title');
        $page->displayorder = Input::get('displayorder');

        $page->save();

        // Return all the details of the updated page as a JSON response
        return Response::make(json_encode($page->attributes), 200, array('Content-Type' => 'application/json'));
    }

    // DELETE: DELETE /pages/1
    public function delete_pages($id = null)
    {
        // Handle the lack of an ID
        if($id == null)
            return ApiUtils::createResponse(400, 'Bad Request', 'You must supply an id for DELETE actions');

        // Get the existing page data
        $page = Page::where('deleted', '!=', true)->where('id', '=', $id)->first();

        // If the page doesn't exist, return a helpful message
        if($page == null)
            return ApiUtils::createResponse(404, 'Not Found', 'No item exists with the requested id');

        // 'Soft delete' to allow for undo functionality
        $page->deleted = true;

        $page->save();

        // Return the details of the deleted page as a JSON response
        return Response::make(json_encode($page->attributes), 200, array('Content-Type' => 'application/json'));
    }
}