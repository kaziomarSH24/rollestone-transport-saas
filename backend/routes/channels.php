<?php

use App\Models\Journey;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;


// Broadcast::channel('journey.{journeyId}', function (User $user, int $journeyId) {
//     Log::info('--- BROADCAST AUTH CHECK START ---');
//     Log::info('Authorizing User ID: ' . $user->id);
//     Log::info('For Journey ID: ' . $journeyId);


//     $is_authorized = true; // Ba apnar nijer logic, jemon: $journey->users()->...

//     Log::info('Is user authorized? ' . ($is_authorized ? 'YES' : 'NO'));
//     Log::info('--- BROADCAST AUTH CHECK END ---');

//     return $is_authorized;
// });
