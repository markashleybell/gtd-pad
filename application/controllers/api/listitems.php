<?php

class Api_ListItems_Controller extends Base_Controller {

    public $restful = true;

    // GET: GET /pages/1/items/1/items/1 or GET /pages/1/items/1/items
    public function get_index($pageid = null, $listid = null, $id = null)
    {
        // Check a page ID has been supplied
        if($pageid == null || $listid == null)
            return ApiUtils::createResponse(400, 'Bad Request', 'You must supply a valid page id and a valid list id to view an item list'); 

        $page = Page::where('deleted', '!=', true)->where('id', '=', $pageid)->first();

        // If null, return 404
        if($page == null)
            return ApiUtils::createResponse(404, 'Not Found', 'No page exists with the requested id');

        $list = Item::where('deleted', '!=', true)->where('id', '=', $listid)->first();

        // If null, return 404
        if($list == null)
            return ApiUtils::createResponse(404, 'Not Found', 'No list exists with the requested id');

        // If the id is null, show all list items
        if($id == null)
        {
            $items = ListItem::where('item_id', '=', $listid)->where('deleted', '!=', true)->get();

            $output = array();

            foreach ($items as $item) {
                $output[] = $item->attributes;
            }

            return Response::make(json_encode($output), 200, array('Content-Type' => 'application/json'));
        }

        $item = ListItem::where('deleted', '!=', true)->where('item_id', '=', $listid)->where('id', '=', $id)->first();

        if($item == null)
            return ApiUtils::createResponse(404, 'Not Found', 'No list item exists with the requested id');

        return Response::make(json_encode($item->attributes), 200, array('Content-Type' => 'application/json'));
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
            $item->displayorder = Input::get('displayorder');

            // Set the user id from the authed user
            //$item->user_id = 

            $item->save();
        }
        catch(Exception $e)
        {
            // In most cases, this will be thrown if the client gets a field name wrong
            return ApiUtils::createResponse(500, 'Server Error', 'Payload error: please check the fields you are POSTing are correctly named'); 
        }

        // Return all the details of the new page as a JSON response
        return Response::make(json_encode($item->attributes), 201, array('Content-Type' => 'application/json'));
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
        $item = ListItem::where('deleted', '!=', true)->where('item_id', '=', $listid)->where('id', '=', $id)->first();

        // If the page doesn't exist, return a helpful message
        if($item == null)
            return ApiUtils::createResponse(404, 'Not Found', 'No list item exists with the requested id');

        // Update the data from PUT data fields
        $item->body = Input::get('body');
        $item->item_id = $listid;
        $item->displayorder = Input::get('displayorder');

        $item->save();

        // Return all the details of the updated page as a JSON response
        return Response::make(json_encode($item->attributes), 200, array('Content-Type' => 'application/json'));
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
        $item = ListItem::where('deleted', '!=', true)->where('item_id', '=', $listid)->where('id', '=', $id)->first();

        // If the page doesn't exist, return a helpful message
        if($item == null)
            return ApiUtils::createResponse(404, 'Not Found', 'No item exists with the requested id');

        // 'Soft delete' to allow for undo functionality
        $item->deleted = true;

        $item->save();

        // Return the details of the deleted page as a JSON response
        return Response::make(json_encode($item->attributes), 200, array('Content-Type' => 'application/json'));
    }
}