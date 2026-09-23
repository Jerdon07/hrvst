<?php

namespace App\Enums\Post;

enum PostType: string
{
    case Supply = 'supply';
    case Demand = 'demand';
}
