<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
        $this->middleware('can:manage settings'); // Protect with a permission
    }

    public function getSettings()
    {
        try {
            $settings = $this->settingsService->getSettings();
            $settings['webhook_endpoint'] = route('webhook.stripe'); // Add webhook URL to settings
            return response_success('Settings retrieved successfully.', $settings);
        } catch (\Exception $e) {
            return response_error('Failed to retrieve settings: ' . $e->getMessage(), [], 500);
        }
    }

    public function saveSettings(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'stripe_publishable_key' => 'nullable|string|starts_with:pk_test_,pk_live_',
                'stripe_secret_key' => 'nullable|string|starts_with:sk_test_,sk_live_',
                'stripe_webhook_secret' => 'nullable|string',
                'fare_rules' => 'nullable|string',
                'zello_channel' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response_error($validator->errors()->first(), $validator->errors()->toArray(), 422);
            }

            $settings = $this->settingsService->saveSettings($validator->validated());
            return response_success('Settings saved successfully.', $settings);
        } catch (\Exception $e) {
            return response_error('Failed to save settings: ' . $e->getMessage(), [], 500);
        }
    }

    public function testStripeConnection(Request $request)
    {
        try {

            // $validator = Validator::make($request->all(), [
            //     'stripe_secret_key' => 'required|string|starts_with:sk_test_,sk_live_',
            // ]);
            // if ($validator->fails()) {
            //     return response_error($validator->errors()->first(), $validator->errors()->toArray(), 422);
            // }
            $company = tenant();
            if (!$company->stripe_secret_key) {
                return response_error('Stripe secret key is not set for the current company.', [], 400);
            }

            $result = $this->settingsService->testStripeConnection($company->stripe_secret_key);

            if ($result['status'] === 'success') {
                return response_success($result['message']);
            } else {
                return response_error($result['message']);
            }
        } catch (\Exception $e) {
            return response_error('Failed to test Stripe connection: ' . $e->getMessage(), [], 500);
        }
    }
}
