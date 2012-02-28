<?php

class ApiUtils {

    public static function createResponse($statusCode, $statusText, $message)
    {
        return Response::make(
            json_encode(array('code' => $statusCode, 'status' => $statusText, 'message' => $message)), 
            $statusCode, 
            array('Content-Type' => 'application/json')
        );
    }

}