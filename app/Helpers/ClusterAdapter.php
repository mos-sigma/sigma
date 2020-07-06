<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Cluster as AppCluster;
use Exception;
use Laravel\Horizon\Exec;
use Sigmie\App\Core\Cloud\Regions\America;
use Sigmie\App\Core\Cloud\Regions\Asia;
use Sigmie\App\Core\Cloud\Regions\Europe;
use Sigmie\App\Core\Cluster as CoreCluster;

class ClusterAdapter
{
    public static function toCoreCluster(AppCluster $cluster): CoreCluster
    {
        $coreCluster = new CoreCluster();

        $coreCluster->setName($cluster->name);

        if ($cluster->data_center === 'europe') {
            $coreCluster->setRegion(new Europe);
        }

        if ($cluster->data_center === 'asia') {
            $coreCluster->setRegion(new Asia);
        }

        if ($cluster->data_center === 'america') {
            $coreCluster->setRegion(new America);
        }

        $coreCluster->setDiskSize(15);
        $coreCluster->setNodesCount($cluster->nodes_count);

        $coreCluster->setUsername($cluster->username);
        $coreCluster->setPassword(decrypt($cluster->password));

        return $coreCluster;
    }

    public static function toAppCluster(CoreCluster $cluster): AppCluster
    {
        $coreCluster = new AppCluster();

        throw new Exception('Function toAppCluster isn\'t implemented yet.');

        return $coreCluster;
    }
}