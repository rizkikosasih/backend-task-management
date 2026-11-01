<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'assigned_to' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
            'due_date' => $this->due_date
                ? Carbon::parse($this->due_date)->locale('id')->translatedFormat('l, d F Y')
                : null,
            'created_at' => $this->created_at?->locale('id')->translatedFormat('l, d F Y'),
            'updated_at' => $this->updated_at?->locale('id')->translatedFormat('l, d F Y'),
            'project' => $this->whenLoaded('project', function () {
                return [
                    'id' => $this->project->id,
                    'name' => $this->project->name,
                ];
            }),
        ];
    }
}
