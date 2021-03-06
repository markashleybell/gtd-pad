<?php

class ApiV1_Items_Controller extends Base_Controller {

    public $restful = true;

    public function __construct()
    {
        $this->filter('before', 'api_auth');
    }

    // GET: GET /pages/1/items/1 or GET /pages/1/items
    public function get_index($pageid = null, $id = null)
    {
        // Check a page ID has been supplied
        if($pageid == null)
            return ApiUtils::createResponse(400, 'Bad Request', 'You must supply a valid page id to view an item list'); 

        $page = Page::where('deleted', '!=', true)->where('user_id', '=', Auth::user()->id)->where('id', '=', $pageid)->first();

        // If null, return 404
        if($page == null)
            return ApiUtils::createResponse(404, 'Not Found', 'No page exists with the requested id');

        // If the id is null, show all pages
        if($id == null)
        {
            $items = Item::where('page_id', '=', $pageid)->where('user_id', '=', Auth::user()->id)->where('deleted', '!=', true)->order_by('displayorder', 'asc')->get();

            $output = array();

            foreach ($items as $item) {
                // Manually convert boolean int values into proper booleans
                $item->attributes['list'] = (intval($item->attributes['list']) === 1) ? true : false;
                $output[] = $item->attributes;
            }

            return Response::make(json_encode($output, JSON_NUMERIC_CHECK), 200, array('Content-Type' => 'application/json'));
        }

        $item = Item::where('deleted', '!=', true)->where('user_id', '=', Auth::user()->id)->where('page_id', '=', $pageid)->where('id', '=', $id)->first();

        if($item == null)
            return ApiUtils::createResponse(404, 'Not Found', 'No item exists with the requested id');

        // Manually convert boolean int values into proper booleans
        $item->attributes['list'] = (intval($item->attributes['list']) === 1) ? true : false;

        return Response::make(json_encode($item->attributes, JSON_NUMERIC_CHECK), 200, array('Content-Type' => 'application/json'));

        // $fields = Input::get('fields');

        // // If there's a list of fields in the querystring
        // if($fields != null)
        // {
        //     try
        //     {
        //         // Attempt to retrieve only the requested fields for this record
        //         $item = Item::where('deleted', '!=', true)->where('id', '=', $id)->get(explode(',', $fields));

        //         // If null, return 404
        //         if($item == null)
        //             return ApiUtils::createResponse(404, 'Not Found', 'No item exists with the requested id');

        //         // In this case $item is actually an array because we used get() rather than first(), 
        //         // so just return the attributes of the first element
        //         return Response::make(json_encode($item[0]->attributes, JSON_NUMERIC_CHECK), 200, array('Content-Type' => 'application/json'));
        //     }
        //     catch(Exception $e)
        //     {
        //         // In most cases, this will be thrown if the client gets a field name wrong
        //         return ApiUtils::createResponse(400, 'Bad Request', 'Property list contained one or more invalid property names'); 
        //     }
        // }
        // else // Client wants ALL data fields
        // {
        //     $item = Item::where('deleted', '!=', true)->where('id', '=', $id)->first();

        //     if($item == null)
        //         return ApiUtils::createResponse(404, 'Not Found', 'No item exists with the requested id');

        //     return Response::make(json_encode($item->attributes, JSON_NUMERIC_CHECK), 200, array('Content-Type' => 'application/json'));
        // }
    }

    // CREATE: POST /pages/1/items
    public function post_index($pageid = null, $id = null)
    {
        // Check for page id
        if($pageid == null)
            return ApiUtils::createResponse(400, 'Bad Request', 'You must supply a page id to create an item');

        // Prevent POST requests to a url with an id segment
        if($id != null)
            return ApiUtils::createResponse(400, 'Bad Request', 'POST requests to an item/id url are not supported');

        try
        {
            // Create a new page from POST data
            $item = new Item;

            // TODO: need some input validation here! Does Laravel help with this already?
            $item->title = Input::get('title');
            $item->body = Input::get('body');
            $item->list = (Input::get('list') === "true") ? 1 : 0;
            
            if(Input::get('displayorder') != null)
                $item->displayorder = Input::get('displayorder');

            $item->page_id = $pageid;
            $item->user_id = Auth::user()->id;

            $item->save();
        }
        catch(Exception $e)
        {
            // In most cases, this will be thrown if the client gets a field name wrong
            return ApiUtils::createResponse(500, 'Server Error', 'Payload error: please check the fields you are POSTing are correctly named'); 
        }

        // Return all the details of the new page as a JSON response
        return Response::make(json_encode($item->attributes, JSON_NUMERIC_CHECK), 201, array('Content-Type' => 'application/json'));
    }

    // UPDATE: PUT /pages/1/items/1
    public function put_index($pageid = null, $id = null)
    {
        // Check for page id
        if($pageid == null)
            return ApiUtils::createResponse(400, 'Bad Request', 'You must supply a page id to update an item');

        // Handle the lack of an ID
        if($id == null)
            return ApiUtils::createResponse(400, 'Bad Request', 'You must supply an id for PUT actions');

        // Get the existing page data
        // We don't filter on page id, which allows this method to be used for updating the page an item is associated with
        $item = Item::where('deleted', '!=', true)->where('user_id', '=', Auth::user()->id)->where('id', '=', $id)->first();

        // If the page doesn't exist, return a helpful message
        if($item == null)
            return ApiUtils::createResponse(404, 'Not Found', 'No item exists with the requested id');

        // Update the data from PUT data fields
        if(Input::get('title') != null)
            $item->title = Input::get('title');

        if(Input::get('body') != null)
            $item->body = Input::get('body');

        if(Input::get('list') != null)
            $item->list = (Input::get('list') === "true") ? 1 : 0;

        if(Input::get('displayorder') != null)
            $item->displayorder = Input::get('displayorder');

        if($pageid != $item->page_id)
            $item->displayorder = -1;

        $item->page_id = $pageid;

        $item->save();

        // Return all the details of the updated page as a JSON response
        return Response::make(json_encode($item->attributes, JSON_NUMERIC_CHECK), 200, array('Content-Type' => 'application/json'));
    }

    // UPDATE: PUT /pages/1/items/order
    public function put_order($pageid = null)
    {
        // Check for page id
        if($pageid == null)
            return ApiUtils::createResponse(400, 'Bad Request', 'You must supply a page id to update item ordering');

        // Get the existing page data
        $items = Item::where('deleted', '!=', true)->where('user_id', '=', Auth::user()->id)->where('page_id', '=', $pageid)->get();

        try
        {
            foreach($items as $item)
            {
                $item->displayorder = Input::get('displayorder-' . $item->id);
                $item->save();
            }
        }
        catch(Exception $e)
        {
            // In most cases, this will be thrown if the client gets a field name wrong
            return ApiUtils::createResponse(500, 'Server Error', 'Payload error: please check the fields you are POSTing are correctly named'); 
        }

        // Return a flag indicating success or failure
        return ApiUtils::createResponse(200, 'OK', 'Update successful'); 
    }

    // DELETE: DELETE /pages/1/items/1
    public function delete_index($pageid = null, $id = null)
    {
        // Check for page id
        if($pageid == null)
            return ApiUtils::createResponse(400, 'Bad Request', 'You must supply a page id to delete an item');

        // Handle the lack of an ID
        if($id == null)
            return ApiUtils::createResponse(400, 'Bad Request', 'You must supply an id for DELETE actions');

        // Get the existing page data
        $item = Item::where('deleted', '!=', true)->where('user_id', '=', Auth::user()->id)->where('page_id', '=', $pageid)->where('id', '=', $id)->first();

        // If the page doesn't exist, return a helpful message
        if($item == null)
            return ApiUtils::createResponse(404, 'Not Found', 'No item exists with the requested id');

        // 'Soft delete' to allow for undo functionality
        $item->deleted = true;

        $item->save();

        // Return the details of the deleted page as a JSON response
        return Response::make(json_encode($item->attributes, JSON_NUMERIC_CHECK), 200, array('Content-Type' => 'application/json'));
    }
}