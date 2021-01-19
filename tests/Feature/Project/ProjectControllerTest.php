<?php

declare(strict_types=1);

namespace Tests\Feature\Project;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\WithSubscribedUser;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use WithSubscribedUser;

    /**
     * @test
     */
    public function create_renders_inertia_project_create()
    {
        $this->withSubscribedUser();

        $this->actingAs($this->user);

        $this->assertInertiaViewExists('project/create/create');
        $this->get(route('project.create'))->assertInertia('project/create/create');
    }
}