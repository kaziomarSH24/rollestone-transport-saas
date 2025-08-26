<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;


class RegisterRequest extends BaseRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:100|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'rider_type' => 'required|in:adult,child,student',
            'phone_number' => 'nullable|string|max:15',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Max 2MB
            'address' => 'nullable|string',
            'fcm_token' => 'nullable|string|max:500', // FCM token for push notifications
        ];
    }


     /**
     * Get the custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your full name.',
            'username.unique' => 'This username is already taken. Please choose another one.',
            'email.required' => 'An email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'An account with this email address already exists.',
            'password.required' => 'A password is required.',
            'password.min' => 'The password must be at least 6 characters long.',
            'password.confirmed' => 'The password confirmation does not match.',
            'rider_type.required' => 'Please select your rider type.',
            'rider_type.in' => 'Please select a valid rider type (Adult, Child, or Student).',
        ];
    }


}
