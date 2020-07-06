<?php

namespace App\Models;

use App\Models\Cluster;

class Project extends Model
{
    public function clusters()
    {
        return $this->hasMany(Cluster::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // public function productionCluster()
    // {
    //     return $this->hasMany(Cluster::class)->where(['environment' => 'prod']);
    // }

    // public function stagingCluster()
    // {
    //     return $this->hasMany(Cluster::class)->where(['environment' => 'staging']);
    // }

    // public function testCluster()
    // {
    //     return $this->hasMany(Cluster::class)->where(['environment' => 'test']);
    // }
}
