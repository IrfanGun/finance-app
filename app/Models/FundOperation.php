<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'idempotency_key', 'payload_hash', 'operation_id'])]
class FundOperation extends Model {}
