<?php

namespace App\Enums;

enum MessageStatusType: string
{
    case DELIVERED = 'delivered';
    case READ = 'read';
}
