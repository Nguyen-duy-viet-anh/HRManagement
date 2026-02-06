<?php

if (!function_exists('apiSuccess')) {
    function apiSuccess($data = null, $message = 'Success', $code = 200)
    {
        return response()->json([
            'status'  => true,
            'message' => $message,
            'data'    => $data
        ], $code);
    }
}

if (!function_exists('apiError')) {
    function apiError($message = 'Error', $code = 400)
    {
        return response()->json([
            'status'  => false,
            'message' => $message
        ], $code);
    }
}
