<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProject;
use App\Project;
use App\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return array
     */
    public function index()
    {
        /** @var  User */
        $user = Auth::user();

        return $user->projects()->get()->toArray();
    }

    /**
     * Project create page
     */
    public function create()
    {
        return Inertia::render('project/create');
    }

    public function store(StoreProject $request)
    {
        $credentials = json_decode($request->get('provider')['creds'], true);
        $provider = $request->get('provider')['id'];

        $project = Project::create([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'creds' => encrypt($credentials),
            'provider' => $provider,
            'user_id' => Auth::user()->id
        ]);

        return redirect()->route('cluster.create');
    }
}
