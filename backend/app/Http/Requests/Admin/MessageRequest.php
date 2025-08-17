<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class MessageRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('put')) {
            return [
            'subject' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
            'message_type' => 'sometimes|string',
            'recipient_type' => 'sometimes|string|in:all,drivers,passengers',
            'action' => 'sometimes|string|in:send_now,save_draft,schedule',
            'scheduled_at' => 'required_if:action,schedule|nullable|date|after:now',
            ];
        }

        return [
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'message_type' => 'required|string',
            'recipient_type' => 'required|string|in:all,drivers,passengers',
            'action' => 'required|string|in:send_now,save_draft,schedule',
            'scheduled_at' => 'required_if:action,schedule|nullable|date|after:now',
        ];
    }
    
}
