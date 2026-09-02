<?php

use App\Mcp\Servers\AppTrackerServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('apptracker', AppTrackerServer::class);
