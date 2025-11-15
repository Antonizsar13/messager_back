<?php

namespace App\Enums;

enum BucketName: string
{
    case PROFILE = 's3-profile';
    case MESSAGE = 's3-message';
    case OTHER = 's3-other';
}
