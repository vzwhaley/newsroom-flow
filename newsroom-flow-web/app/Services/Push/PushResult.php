<?php

namespace App\Services\Push;

enum PushResult
{
    case Sent;       // delivered to the push service
    case Invalid;    // token is dead/unregistered → prune it
    case Failed;     // transient failure → keep the token, try again next run
}
