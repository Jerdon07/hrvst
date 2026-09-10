<?php

namespace App\Http\Requests\Dealer;

use App\Http\Requests\Schedule\UpdatePostRequest;

class UpdateDemandRequest extends UpdatePostRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('dealer');
    }
}
