<?php

class JSONUtils {

    public static function createResponse($statusCode, $statusText, $message)
    {
        return Response::make(
            json_encode(array('status' => $statusText, 'message' => $message)), 
            $statusCode, 
            array('Content-Type' => 'application/json')
        );
    }

}