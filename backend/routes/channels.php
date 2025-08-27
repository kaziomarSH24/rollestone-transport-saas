<?php

use App\Models\Journey;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('journey.{journeyId}', function ($user, $journeyId) {

    $journey = Journey::find($journeyId);
    return $journey && $journey->users()->where('user_id', $user->id)->exists();
});
