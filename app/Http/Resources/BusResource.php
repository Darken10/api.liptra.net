<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Bus
 */
final class BusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'registration_number' => $this->registration_number,
            'brand' => $this->brand,
            'model' => $this->model,
            'total_seats' => $this->total_seats,
            'comfort_type' => $this->comfort_type->value,
            'comfort_type_label' => $this->comfort_type->label(),
            'manufacture_year' => $this->manufacture_year,
            'color' => $this->color,
            'has_air_conditioning' => $this->has_air_conditioning,
            'has_wifi' => $this->has_wifi,
            'has_usb_charging' => $this->has_usb_charging,
            'has_toilet' => $this->has_toilet,
            'photo' => $this->photo,
            'mileage' => $this->mileage,
            'is_active' => $this->is_active,
            'photos' => BusPhotoResource::collection($this->whenLoaded('photos')),
        ];
    }
}
