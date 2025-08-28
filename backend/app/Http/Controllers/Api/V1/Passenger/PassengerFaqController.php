<?php

namespace App\Http\Controllers\Api\V1\Passenger;

use App\Http\Controllers\Controller;
use App\Services\Admin\FaqService;
use Illuminate\Http\Request;

class PassengerFaqController extends Controller
{
    protected FaqService $faqService;
    public function __construct(FaqService $faqService)
    {
        $this->faqService = $faqService;
    }

    //list faqs
    public function index()
    {
        $faqs = $this->faqService->getAll();
       if($faqs->isEmpty()){
        return response_error('No FAQs found.', [], 404);
       }
        return response_success('FAQs retrieved successfully.', $faqs);
    }

}
