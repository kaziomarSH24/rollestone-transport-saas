<?php

namespace App\Services\Driver;

use App\Models\Fare;
use App\Services\BaseService;
use App\Models\Journey;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JourneyService extends BaseService
{
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = Journey::class;

    public function __construct()
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();
    }

    /**
     * Blocks a trip for a driver for today.
     */
    public function blockTrip(int $tripId, string $fleetNumber, User $driverUser): Journey
    {
        $today = Carbon::today();
        if (Journey::where('trip_id', $tripId)->whereDate('journey_date', $today)->exists()) {
            throw new \Exception('This trip has already been blocked by another driver for today.');
        }

        $data = [
            'trip_id' => $tripId,
            'driver_id' => $driverUser->driver->id,
            'fleet_number' => $fleetNumber,
            'journey_date' => $today,
            'status' => 'blocked',
        ];
        return $this->create($data);
    }

    /**
     * Starts a previously blocked journey.
     */
    public function startJourney(Journey $journey, User $driverUser): Journey
    {
        // Ensure the journey belongs to the current driver and is in a 'Blocked' state
        if ($journey->driver_id !== $driverUser->driver->id || $journey->status !== 'blocked') {
            throw new \Exception('This trip cannot be started.');
        }
        // Check if the driver already has an ongoing journey
        if ($this->hasOngoingJourney($driverUser)) {
            throw new \Exception('You already have another trip in progress.');
        }

        $journey->update([
            'status' => 'ongoing',
            'actual_departure_time' => Carbon::now(),
        ]);
        return $journey;
    }

    /**
     * Ends an ongoing journey.
     */
    public function endJourney(Journey $journey, User $driverUser): Journey
    {
        if ($journey->driver_id !== $driverUser->driver->id || $journey->status !== 'ongoing') {
            throw new \Exception('This trip cannot be ended.');
        }

        $journey->update([
            'status' => 'completed',
            'actual_arrival_time' => Carbon::now(),
        ]);
        return $journey;
    }

    /**
     * Get the schedule of journeys for the currently authenticated driver for today.
     */
    public function getDriverSchedule(User $driverUser): \Illuminate\Database\Eloquent\Collection
    {
        return Journey::where('driver_id', $driverUser->driver->id)
                      ->whereDate('journey_date', Carbon::today())
                      ->with(['trip.route.stops'])
                      ->latest()
                      ->get();
    }

    private function hasOngoingJourney(User $driverUser): bool
    {
        return Journey::where('driver_id', $driverUser->driver->id)
                      ->where('status', 'ongoing')
                      ->exists();
    }

    /**
     * Processes a payment for one or more passengers for the driver's current ongoing journey.
     */
    public function processGroupPayment(User $driverUser, string $paymentMethod, array $fares, ?string $qrNumber = null): float
    {
        $journey = Journey::where('driver_id', $driverUser->driver->id)
                          ->where('status', 'ongoing')
                          ->first();
        if (!$journey) {
            throw new \Exception('You do not have an ongoing trip.');
        }

        $route = $journey->trip->route;

        $totalAmount = 0;
        $fareDetailsForJson = [];

        foreach ($fares as $fareInfo) {
            $fare = Fare::where('route_id', $route->id)
                        ->where('passenger_type', $fareInfo['type'])
                        ->where('payment_method', $paymentMethod)
                        ->firstOrFail();
            $totalAmount += $fare->amount * $fareInfo['quantity'];
            $fareDetailsForJson[] = [
                'type' => $fareInfo['type'],
                'quantity' => $fareInfo['quantity'],
                'unit_price' => $fare->amount,
            ];
        }
        dd($totalAmount, $fareDetailsForJson);
        $passenger = null;
        if ($paymentMethod === 'Wallet') {
            $passenger = User::where('qr_code_number', $qrNumber)->firstOrFail();
            if ($passenger->wallet->balance < $totalAmount) {
                throw ValidationException::withMessages(['wallet' => 'Insufficient wallet balance.']);
            }
        }
        DB::transaction(function () use ($passenger, $driverUser, $journey, $totalAmount, $fareDetailsForJson, $paymentMethod) {
            $payingUser = $passenger ?? $driverUser;

            // if the payment method is 'Wallet', deduct the amount from the passenger's wallet
            if ($paymentMethod === 'Wallet' && $passenger) {
                $passenger->wallet->decrement('balance', $totalAmount);
            }

            // Create the transaction record
            $payingUser->transactions()->create([
                'journey_id' => $journey->id,
                'processed_by_user_id' => $driverUser->id,
                'type' => 'TripFare',
                'amount' => -$totalAmount,
                'payment_method' => $paymentMethod,
                'fare_details' => json_encode($fareDetailsForJson),
                'status' => 'succeeded',
            ]);
        });

        return $totalAmount;
    }

      /**
     * Processes a "Scan & Go" payment for a single passenger, preventing duplicate charges.
     */
    public function processSingleUserPayment(User $driverUser, string $qrNumber): array
    {

        $journey = Journey::where('driver_id', $driverUser->driver->id)
                          ->where('status', 'Ongoing')
                          ->firstOrFail();

        //
        $passenger = User::where('qr_code_number', $qrNumber)->firstOrFail();

        // Check if the passenger has already paid for this specific journey.
        $existingTransaction = Transaction::where('user_id', $passenger->id)
                                          ->where('journey_id', $journey->id)
                                          ->where('type', 'TripFare')
                                          ->exists();

        if ($existingTransaction) {
            throw new \Exception('Payment has already been made for this trip.');
        }

        // find the fare for the passenger's type
        $fare = Fare::where('route_id', $journey->trip->route_id)
                    ->where('passenger_type', $passenger->rider_type)
                    ->where('payment_method', 'Wallet')
                    ->firstOrFail();

        // 4. Check if the passenger has sufficient wallet balance
        if ($passenger->wallet->balance < $fare->amount) {
            throw ValidationException::withMessages(['wallet' => 'Insufficient wallet balance.']);
        }


        $transaction = DB::transaction(function () use ($passenger, $journey, $fare, $driverUser) {
            $passenger->wallet->decrement('balance', $fare->amount);

            return $passenger->transactions()->create([
                'journey_id' => $journey->id,
                'processed_by_user_id' => $driverUser->id,
                'type' => 'TripFare',
                'amount' => -$fare->amount,
                'payment_method' => 'Wallet',
                'fare_details' => json_encode([
                    ['type' => $passenger->rider_type, 'quantity' => 1, 'unit_price' => $fare->amount]
                ]),
                'status' => 'succeeded',
            ]);
        });

        return [
            'passenger_name' => $passenger->name,
            'amount_charged' => $fare->amount,
            'new_balance' => $passenger->wallet->balance,
            'transaction_id' => $transaction->id,
        ];
    }

}

