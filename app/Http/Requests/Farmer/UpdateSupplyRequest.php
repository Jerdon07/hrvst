<?php

namespace App\Http\Requests\Farmer;

use App\Http\Requests\Schedule\UpdatePostRequest;

class UpdateSupplyRequest extends UpdatePostRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('farmer')
            && $this->user()->can('update', $this->route('post'));
    }
}
