<?php

namespace App\Services\Admin;

use App\Services\BaseService;
use App\Models\Faq;

class FaqService extends BaseService
{
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = Faq::class;

    public function __construct()
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();
    }
}
