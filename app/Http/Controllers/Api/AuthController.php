<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Tenant;
use App\Models\SystemRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $user->createToken($request->device_name ?? 'mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user->load('tenants')
        ]);
    }

    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $email = $request->email;
        $code = rand(100000, 999999);

        // Store OTP in Cache for 10 minutes
        Cache::put('registration_otp_' . $email, $code, 600);

        try {
            Mail::raw("Your verification code is: {$code}", function ($message) use ($email) {
                $message->to($email)
                    ->subject('Verify your email address');
            });
            
            return response()->json([
                'message' => 'Verification code sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send verification code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'verification_code' => 'required',
            'invitation_code' => 'required|exists:tenants,code',
            'name' => 'required|string|max:255',
            'device_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify OTP
        $cachedCode = Cache::get('registration_otp_' . $request->email);
        
        if (!$cachedCode || $cachedCode != $request->verification_code) {
             return response()->json([
                'errors' => ['verification_code' => ['Invalid or expired verification code.']]
            ], 422);
        }

        // Verify Tenant
        $tenant = Tenant::where('code', $request->invitation_code)->first();
        if (!$tenant) {
            return response()->json([
                'errors' => ['invitation_code' => ['Invalid invitation code.']]
            ], 422);
        }

        $staffRole = SystemRole::where('role', 'Staff')->first();

        // Create User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'last_active_tenant_id' => $tenant->id,
        ]);

        // Attach to pivot
        $user->tenants()->attach($tenant->id, [
            'role_id' => $staffRole ? $staffRole->id : 4,
            'display_name' => $user->name,
        ]);

        // Clean up OTP
        Cache::forget('registration_otp_' . $request->email);

        $token = $user->createToken($request->device_name ?? 'mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'token' => $token,
            'user' => $user->load('tenants')
        ], 201);
    }

    public function switchTenant(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $tenantId = $request->tenant_id;

        // Verify the user belongs to this tenant
        if (!$user->tenants->contains($tenantId)) {
            return response()->json([
                'message' => 'Unauthorized access to this tenant.'
            ], 403);
        }

        $user->last_active_tenant_id = $tenantId;
        $user->save();

        return response()->json([
            'message' => 'Tenant switched successfully',
            'user' => $user->load('tenants')
        ]);
    }
}
