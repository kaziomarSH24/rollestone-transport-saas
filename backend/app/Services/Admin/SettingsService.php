<?php
namespace App\Services\Admin;

use App\Models\Company;
use Stripe\Stripe;
use Stripe\Balance;

class SettingsService
{
    /**
     * Get the settings for the current company.
     */
    public function getSettings(): Company
    {
        // tenant() helper will get the current logged-in admin's company
        return tenant();
    }

    /**
     * Update the settings for the current company.
     */
    public function saveSettings(array $data): Company
    {
        $company = tenant();
        $company->update($data);
        return $company;
    }

    /**
     * Test the provided Stripe API keys by making a simple API call.
     */
    public function testStripeConnection(string $secretKey): array
    {
        try {
            // Temporarily set the API key for this request
            Stripe::setApiKey($secretKey);

            // Make a simple, harmless API call to check if the key is valid
            Balance::retrieve();

            return ['status' => 'success', 'message' => 'Stripe connection successful!'];
        } catch (\Exception $e) {
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }
}


