<?php

namespace Tests\Unit\Events;

use App\Events\ClusterHasFailed;
use App\Events\ClusterWasBooted;
use App\Events\ClusterWasCreated;
use App\Events\ClusterWasDestroyed;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;

class ClusterHasFailedTest extends TestCase
{
    /**
     * @var ClusterHasFailed
     */
    private $event;

    /**
     * @var integer
     */
    private $clusterId = 998;

    protected function setUp(): void
    {
        parent::setUp();

        $this->event = new ClusterHasFailed($this->clusterId);
    }

    /**
     * @test
     */
    public function create_has_failed_has_public_cluster_id_property()
    {
        $this->assertEquals($this->clusterId, $this->event->clusterId);
    }
}
