<?php

if (!function_exists('gate')) {
    function gate(array $callable): \Illuminate\Auth\Access\Response
    {
        $user = auth()->user();
        $arguments = func_get_args();
        $callable = array_shift($arguments);

        $policy = new $callable[0];
        $ability = $callable[1];
        $response = null;

        $reflection = new \ReflectionClass($policy);

        if ($reflection->hasMethod($ability)) {
            if ($reflection->hasMethod('before')) {
                $method = $reflection->getMethod('before');
                $userParameter = $method->getParameters()[0];

                if ($user || $userParameter->allowsNull()) {
                    $response = $method->invoke($policy, $user, $ability);
                }
            }

            if (is_null($response)) {
                $method = $reflection->getMethod($ability);
                $userParameter = $method->getParameters()[0];

                if ($user || $userParameter->allowsNull()) {
                    $arguments = is_array($arguments) ? $arguments : [$arguments];
                    array_unshift($arguments, $user);
                    $response = $method->invokeArgs($policy, $arguments);
                } else {
                    $response = \Illuminate\Auth\Access\Response::deny('Not allowed for guest user');
                }
            }
        }

        if (is_bool($response) || is_null($response)) {
            $response = $response ? \Illuminate\Auth\Access\Response::allow() : \Illuminate\Auth\Access\Response::deny();
        }

        return $response;
    }
}