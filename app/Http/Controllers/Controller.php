<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    /**
     * Lets controllers call $this->authorize(); policies are the single place
     * where admin permissions are decided.
     */
    use AuthorizesRequests;
}
