<?php

namespace App\Enums;

enum ChatUserRole: string
{
    case MEMBER = 'member';
    case ADMIN = 'admin';
}
