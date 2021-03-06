<?php

class ApiV1_ListItems_Controller extends Base_Controller {

    public $restful = true;

    public function __construct()
    {
        $this->filter('before', 'api_auth');
    }

    // GET: GET /pages/1/items/1/items/1 or GET /pages/1/items/1/items
    public function get_index($pageid = null, $listid = null, $id = null)
    {
        // Check a page ID has been supplied
        if($pageid == null || $listid == null)
            return ApiUtils::createResponse(400, 'Bad Request', 'You must supply a valid page id and a valid list id to view an item list'); 

        $page = Page::where('deleted', '!=', true)->where('user_id', '=', Auth::user()->id)->where('id', '=', $pageid)->first();

        // If null, return 404
        if($page == null)
            return ApiUtils::createResponse(404, 'Not Found', 'No page exists with the requested id');

        $list = Item::where('deleted', '!=', true)->where('user_id', '=', Auth::user()->id)->where('id', '=', $listid)->first();

        // If null, return 404
        if($list == null)
            return ApiUtils::createResponse(404, 'Not Found', 'No list exists with the requested id');

        // If the id is null, show all list items
        if($id == null)
        {
            $items = ListItem::where('item_id', '=', $listid)->where('user_id', '=', Auth::user()->id)->where('deleted', '!=', true)->where('completed', '!=', true)->order_by('displayorder', 'asc')->get();

            $output = array();

            foreach ($items as $item) {
                $output[] = $item->attributes;
            }

            return Response::make(json_encode($output, JSON_NUMERIC_CHECK), 200, array('Content-Type' => 'application/json'));
        }

        $item = ListItem::where('deleted', '!=', true)->where('user_id', '=', Auth::user()->id)->where('item_id', '=', $listid)->where('id', '=', $id)->first();

        if($item == null)
            return ApiUtils::createResponse(404, 'Not Found', 'No list item exists with the requested id');

        return Response::make(json_encode($item->attributes, JSON_NUMERIC_CHECK), 200, array('Content-Type' => 'application/json'));
    }

    // CREATE: POST /pages/1/items/1/items
    public function post_index($pageid = null, $listid = null, $id = null)
    {
        // Check for page id
        if($pageid == null || $listid == null)
            return ApiUtils::createResponse(400, 'Bad Request', 'You must supply a page id and a list id to create an item');

        // Prevent POST requests to a url with an id segment
        if($id != null)
            return ApiUtils::createResponse(400, 'Bad Request', 'POST requests to an item/id url are not supported');

        try
        {
            // Create a new page from POST data
            $item = new ListItem;

            // TODO: need some input validation here! Does Laravel help with this already?
            $item->body = Input::get('body');
            $item->item_id = $listid;
            $item->displayorder = 0; //Input::get('displayorder');
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

    // UPDATE: PUT /pages/1/items/1/items/1
    public function put_index($pageid = null, $listid = null, $id = null)
    {
        // Check for page id
        if($pageid == null || $listid == null)
            return ApiUtils::createResponse(400, 'Bad Request', 'You must supply a page id and a list id to update an item');

        // Handle the lack of an ID
        if($id == null)
            return ApiUtils::createResponse(400, 'Bad Request', 'You must supply an id for PUT actions');

        // Get the existing list item data
        $item = ListItem::where('deleted', '!=', true)->where('user_id', '=', Auth::user()->id)->where('item_id', '=', $listid)->where('id', '=', $id)->first();

        // If the page doesn't exist, return a helpful message
        if($item == null)
            return ApiUtils::createResponse(404, 'Not Found', 'No list item exists with the requested id');

        // Update the data from PUT data fields
        $item->body = Input::get('body');
        $item->item_id = $listid;
        $item->displayorder = Input::get('displayorder');

        $item->save();

        // Return all the details of the updated page as a JSON response
        return Response::make(json_encode($item->attributes, JSON_NUMERIC_CHECK), 200, array('Content-Type' => 'application/json'));
    }

    // UPDATE: PUT /pages/1/items/1/items/1/complete
    public function put_complete($pageid = null, $listid = null, $id = null)
    {
        // Check for page id
        if($pageid == null || $listid == null)
            return ApiUtils::createResponse(400, 'Bad Request', 'You must supply a page id and a list id to update an item');

        // Handle the lack of an ID
        if($id == null)
            return ApiUtils::createResponse(400, 'Bad Request', 'You must supply an id for PUT actions');

        // Get the existing list item data
        $item = ListItem::where('deleted', '!=', true)->where('user_id', '=', Auth::user()->id)->where('item_id', '=', $listid)->where('id', '=', $id)->first();

        // If the page doesn't exist, return a helpful message
        if($item == null)
            return ApiUtils::createResponse(404, 'Not Found', 'No list item exists with the requested id');

        // Update the data from PUT data fields
        if(Input::get('completed') != null)
            $item->completed = (Input::get('completed') === "true") ? 1 : 0;

        $item->save();

        // Return all the details of the updated page as a JSON response
        return Response::make(json_encode($item->attributes, JSON_NUMERIC_CHECK), 200, array('Content-Type' => 'application/json'));
    }

    // UPDATE: PUT /pages/1/items/1/items/order
    public function put_order($pageid = null, $listid = null)
    {
        // Check for page id
        if($pageid == null)
            return ApiUtils::createResponse(400, 'Bad Request', 'You must supply a page id and a list id to update item ordering');

        // Get an array of ids, in the correct order
        $idList = explode(',', Input::get('items'));

        try
        {
            // Loop through all list item ids in the array, updating the display order to
            // the current array index and the list id to the current list
            // This makes dragging and dropping between lists possible because the parent list can be reset
            foreach($idList as $k => $id)
            {
                $item = ListItem::where('deleted', '!=', true)->where('user_id', '=', Auth::user()->id)->where('id', '=', $id)->first();
                $item->displayorder = $k;
                $item->item_id = $listid;
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

    // DELETE: DELETE /pages/1/items/1/items/1
    public function delete_index($pageid = null, $listid = null, $id = null)
    {
        // Check for page id
        if($pageid == null || $listid == null)
            return ApiUtils::createResponse(400, 'Bad Request', 'You must supply a page id and a list id to delete an item');

        // Handle the lack of an ID
        if($id == null)
            return ApiUtils::createResponse(400, 'Bad Request', 'You must supply an id for DELETE actions');

        // Get the existing page data
        $item = ListItem::where('deleted', '!=', true)->where('user_id', '=', Auth::user()->id)->where('item_id', '=', $listid)->where('id', '=', $id)->first();

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