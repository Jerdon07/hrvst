<?php

namespace App\Exceptions\Billing;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

class PaymentFailedException extends Exception implements ShouldntReport {}
