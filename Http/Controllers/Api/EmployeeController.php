<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeController extends Controller
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        $users = $this->users->staff();

        return UserResource::collection($users);
    }
}
