<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class TestKubernetes extends Model
{
    protected $connection = 'mongodb';

    protected $fillable = ['is_running', 'run_count'];
}
