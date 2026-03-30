<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AppResetCodeMail;
use App\Mail\VerificationMail;
use App\Models\ListingView;
use App\Models\SearchHistory;
use App\Models\User;
use App\Models\UserFeedback;
use App\Services\UserDeletionService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class UserAuthController extends Controller
{
    // Register new user
    public function register(Request $request)
    {
        try {
            $data = Validator::make($request->all(), [
                'name' => 'required|string',
                'username' => [
                    'nullable',
                    'string',
                    Rule::unique('users', 'username')->where(function ($query) {
                        $query->where('status', '!=', 3);
                    }),
                ],
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => [
                    'required',
                    'string',
                    Rule::unique('users', 'email')->where(function ($query) {
                        $query->where('status', '!=', 3);
                    }),
                ],
                'password' => 'required|string|min:6',
                'city' => 'nullable|string|max:20',
                'state' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:20',
                'country_id' => 'nullable|max:20|exists:countries,id',
                'regions_id' => 'nullable|max:20|exists:regions,id',
                'governorates_id' => 'nullable|max:20|exists:governorates,id',
                'city_id' => 'nullable|max:20|exists:cities,id',
                'area_id' => 'nullable|max:20|exists:area,id',
                'phone' => [
                    'nullable',
                    'string',
                    'max:20',
                    Rule::unique('users', 'phone')->where(function ($query) {
                        $query->where('status', '!=', 3);
                    }),
                ],
                'gender' => 'nullable|string|in:male,female,other',
                'date_of_birth' => 'nullable|date',
                'billing_address' => 'nullable|string|max:500',
                'customer_number' => 'nullable|string|max:50',
                'account_type' => 'nullable|in:business,personal',
            ]);

            if ($data->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'error' => $data->errors()->first(),
                    'errors' => $data->errors()->toArray(),
                ], 422);
            }

            $existingUser = User::where('email', $request->email)->first();
            $code = (string) random_int(100000, 999999);
            $expiration = now()->addMinutes(30);

            // ✅ Case 1: Restore previously deleted account
            if ($existingUser && $existingUser->status == 3) {
                $existingUser->update([
                    'name' => $request->name,
                    'username' => $request->username,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'city' => $request->city,
                    'state' => $request->state,
                    'country' => $request->country,
                    'phone' => $request->phone,
                    'gender' => $request->gender,
                    'country_id' => $request->country_id,
                    'regions_id' => $request->regions_id,
                    'governorates_id' => $request->governorates_id,
                    'city_id' => $request->city_id,
                    'area_id' => $request->area_id,
                    'date_of_birth' => $request->date_of_birth,
                    'billing_address' => $request->billing_address,
                    'account_type' => $request->account_type ?? 'personal',
                    'verification_code' => $code,
                    'verification_expires_at' => $expiration,
                    'is_verified' => false,
                    'status' => 1, // ✅ Restore account
                ]);

                $user = $existingUser;
            }
            // ✅ Case 2: Create a completely new account
            else {
                $memberId = $this->generateMemberId();
                $customerNumber = 'CN'.strtoupper(uniqid());
                $user_code = $this->generateUniqueCode();

                while (User::where('user_code', $user_code)->exists()) {
                    $user_code = $this->generateUniqueCode();
                }

                $user = User::create([
                    'name' => $request->name,
                    'user_code' => $user_code,
                    'memberId' => $memberId,
                    'username' => $request->username,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'city' => $request->city,
                    'state' => $request->state,
                    'country' => $request->country,
                    'phone' => $request->phone,
                    'gender' => $request->gender,
                    'country_id' => $request->country_id,
                    'regions_id' => $request->regions_id,
                    'governorates_id' => $request->governorates_id,
                    'city_id' => $request->city_id,
                    'area_id' => $request->area_id,
                    'date_of_birth' => $request->date_of_birth,
                    'billing_address' => $request->billing_address,
                    'customer_number' => $customerNumber,
                    'account_type' => $request->account_type ?? 'personal',
                    'verification_code' => $code,
                    'verification_expires_at' => $expiration,
                    'is_verified' => false,
                ]);
            }

            // ✅ Send OTP verification email (use VerificationMail Mailable for reliability)
            \Log::info('OTP for ' . $user->email . ': ' . $code);
            try {
                Mail::to($user->email)->send(new VerificationMail($user, $code));
            } catch (\Throwable $mailError) {
                \Log::error('OTP email failed: ' . $mailError->getMessage(), [
                    'email' => $user->email,
                    'otp' => $code,
                    'mailer' => config('mail.default'),
                ]);
                $payload = [
                    'success' => true,
                    'message' => $existingUser && $existingUser->status == 3
                        ? 'Account restored. Check console/log for OTP.'
                        : 'Successfully registered. Check console/log for OTP.',
                    'email' => $user->email,
                ];
                if (app()->hasDebugModeEnabled()) {
                    $payload['otp'] = $code;
                }
                return response()->json($payload, 200);
            }

            // ✅ Attach guest data (if any)
            if ($request->header('X-Guest-ID')) {
                $guestId = $request->header('X-Guest-ID');
                SearchHistory::where('guest_id', $guestId)
                    ->update(['user_id' => $user->id, 'guest_id' => null]);
                ListingView::where('guest_id', $guestId)
                    ->update(['user_id' => $user->id, 'guest_id' => null]);
            }

            $payload = [
                'success' => true,
                'message' => $existingUser && $existingUser->status == 3
                    ? 'Account restored and verification email sent.'
                    : 'Successfully registered.',
                'email' => $user->email,
            ];
            if (app()->hasDebugModeEnabled()) {
                $payload['otp'] = $code;
            }
            return response()->json($payload, 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
                'errors' => app()->hasDebugModeEnabled() ? ['_exception' => [$e->getMessage()]] : null,
            ], 500);
        }
    }

    /**
     * Sends a verification token to a user to initiate account restoration.
     */
    public function requestRestoreToken(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);

            $user = User::where('email', $request->email)->first();

            // Ensure user exists and is marked as deleted
            if (! $user || $user->status !== 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'No deleted account found for this email.',
                ], 404);
            }

            // Generate and save a new verification code
            $code = (string) random_int(100000, 999999);
            $user->forceFill([
                'verification_code' => $code,
                'verification_expires_at' => now()->addMinutes(30),
            ])->save();

            // Send the restoration code via email
            Mail::send('emails.restoringAccount', ['user' => $user, 'code' => $code, 'is_restore' => true], function ($message) use ($user) {
                $message->to($user->email)->subject('Your Account Restoration Code');
            });

            return response()->json([
                'success' => true,
                'message' => 'A restoration code has been sent to your email.',
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send restoration code.',
                'error' => app()->hasDebugModeEnabled() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Verifies the restoration token, restores the account, and logs the user in.
     */
    public function verifyAndRestore(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'verification_code' => 'required|digits:6',
            ]);

            $user = User::where('email', $request->email)->where('status', 3)->first();

            if (! $user || ! $user->verification_code || $user->verification_code !== $request->verification_code) {
                return response()->json(['success' => false, 'message' => 'Invalid verification code.'], 400);
            }

            if (now()->isAfter($user->verification_expires_at)) {
                return response()->json(['success' => false, 'message' => 'Verification code has expired.'], 400);
            }

            // Restore user
            $user->status = 1; // Set to active
            $user->verification_code = null;
            $user->verification_expires_at = null;
            $user->save();

            // Log the user in by creating a new token
            $token = $user->createToken('user-token-restored')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Your account has been restored successfully.',
                'data' => $user,
                'token' => $token,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred during account restoration.'], 500);
        }
    }

    public function resendOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $user = User::where('email', $request->email)->first();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not registered',
                ], 400);
            }

            if ($user->is_verified) {
                return response()->json([
                    'success' => true,
                    'message' => 'Email is already verified',
                ], 200);
            }

            // Generate a new 6-digit OTP
            $code = (string) random_int(100000, 999999);
            $expiration = now()->addMinutes(30);

            // Update user with new verification code
            $user->forceFill([
                'verification_code' => $code,
                'verification_expires_at' => $expiration,
            ])->save();

            // Send OTP email
            try {
                Mail::to($user->email)->send(new VerificationMail($user, $code));
            } catch (\Throwable $mailError) {
                \Log::error('Resend OTP email failed: ' . $mailError->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Could not send verification email. Please check mail configuration.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Verification code sent successfully',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend OTP',
                'error' => app()->hasDebugModeEnabled() ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function emailVerification(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'verification_code' => 'required|digits:6',
            ]);

            $user = User::where('email', $request->email)->first();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not registered yet',
                ], 400);
            }

            if ($user->is_verified) {
                return response()->json([
                    'success' => true,
                    'message' => 'Email already verified',
                ], 200);
            }

            // If you want to block when no code is set:
            if (empty($user->verification_code) || empty($user->verification_expires_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active verification code. Please request a new one.',
                ], 400);
            }

            // Check expiration first (safer UX)
            if ($user->verification_expires_at && now()->isAfter($user->verification_expires_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification code has expired',
                ], 400);
            }

            // Check code
            if ($user->verification_code !== $request->verification_code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification code',
                ], 400);
            }

            // Mark verified
            $user->forceFill([
                'verification_code' => null,
                'verification_expires_at' => null,
                'is_verified' => true,
                'last_login_at' => now(),
            ])->save();

            $token = $user->createToken('user-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Your email is verified successfully',
                'data' => $user,
                'token' => $token,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong during verification',
                'error' => app()->hasDebugModeEnabled() ? $e->getMessage() : null,
            ], 400);
        }
    }
    /* public function upgradeToBusiness(Request $request)
    {
        $request->validate([
            'business_name'   => 'required|string|max:255',
            'tax_id'          => 'nullable|string|max:50',
            'business_license'=> 'nullable|string|max:100',
            // 'store_description' => 'nullable|string',
        ]);

        $user = auth()->user();
        if($user->account_type === 'business'){
            return resposne()->json([
                'success' => false,
                'message' => 'Your account is already a business account',
                'user'    => $user
            ]);
        }

        $user->update([
            'account_type'    => 'business',
            'business_name'   => $request->business_name,
            'tax_id'          => $request->tax_id,
            'business_license'=> $request->business_license,
            // 'store_description'=> $request->store_description,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Account upgraded to business successfully',
            'user' => $user
        ]);
    } */

    // Update user info
    /* public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $request->validate([
                'name' => 'sometimes|required|string',
                'email' => 'sometimes|required|email|unique:users,email,' . $id,
                'phone' => 'nullable|string|max:20',
                'billing_address' => 'nullable|string|max:500',
            ]);

            $user->update([
                'name' => $request->name ?? $user->name,
                'email' => $request->email ?? $user->email,
                'phone' => $request->phone ?? $user->phone,
                'billing_address' => $request->billing_address ?? $user->billing_address,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User update failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    } */
    // Edit contact details
    // username check
    public function checkUsername(Request $request)
    {
        $request->validate([
            'username' => 'nullable|string',
            'email' => 'nullable|email',
        ]);

        // If username is provided
        if ($request->filled('username')) {
            $username = $request->username;
            $exists = \App\Models\User::where('username', $username)->exists();

            if ($exists) {
                $suggestions = [];
                $attempts = 0;

                // Generate up to 5 unique suggestions
                while (count($suggestions) < 5 && $attempts < 20) {
                    $attempts++;
                    $newUsername = $username.rand(100, 999);

                    if (rand(0, 1)) {
                        $newUsername .= chr(rand(97, 122)); // random lowercase letter
                    }

                    if (! \App\Models\User::where('username', $newUsername)->exists()) {
                        $suggestions[] = $newUsername;
                    }
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Username is already taken',
                    'suggestions' => $suggestions,
                ], 409);
            }

            return response()->json([
                'success' => true,
                'message' => 'Username is available',
            ], 200);
        }

        // If email is provided
        if ($request->filled('email')) {
            $email = $request->email;
            $exists = \App\Models\User::where('email', $email)->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email is already taken',
                ], 409);
            }

            return response()->json([
                'success' => true,
                'message' => 'Email is available',
            ], 200);
        }

        // If neither is provided
        return response()->json([
            'success' => false,
            'message' => 'Please provide username or email to check',
        ], 400);
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            $request->validate([
                'first_name' => 'nullable|string|max:100',
                'last_name' => 'nullable|string|max:100',
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|email|unique:users,email,'.$user->id,
                'phone' => [
                    'nullable',
                    'string',
                    'max:20',
                    Rule::unique('users', 'phone')->ignore($user->id),
                ],
                'landline' => [
                    'nullable',
                    'string',
                    'max:20',
                    Rule::unique('users', 'landline')->ignore($user->id),
                ],
                'gender' => 'nullable|string|in:male,female,other',
                'account_type' => 'nullable|in:business,personal',
                'business_name' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:100',
                'address_finder' => 'nullable|string|max:255',
                'address_1' => 'nullable|string|max:255',
                'address_2' => 'nullable|string|max:255',
                'suburb' => 'nullable|string|max:255',
                'post_code' => 'nullable|string|max:20',
                'closest_district' => 'nullable|string|max:255',
                'billing_address' => 'nullable|string|max:500',
                'street_address' => 'nullable|string|max:255',
                'apartment' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'country_id' => 'nullable|max:20|exists:countries,id',
                'regions_id' => 'nullable|max:20|exists:regions,id',
                'governorates_id' => 'nullable|max:20|exists:governorates,id',
                'city_id' => 'nullable|max:20|exists:cities,id',
                'area_id' => 'nullable|max:20|exists:area,id',
                'state' => 'nullable|string|max:100',
                'zip_code' => 'nullable|string|max:20',
            ]);

            $user->update($request->only([
                'first_name',
                'last_name',
                'name',
                'email',
                'phone',
                'landline',
                'gender',
                'account_type',
                'business_name',
                'country_id',
                'regions_id',
                'governorates_id',
                'city_id',
                'area_id',
                'country',
                'address_finder',
                'address_1',
                'address_2',
                'suburb',
                'post_code',
                'closest_district',
                'billing_address',
                'street_address',
                'apartment',
                'city',
                'state',
                'zip_code',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $user->fresh(), // returns updated user
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // update profile method to add occupation, about_me, and favourite quote
    public function updateProfileDetails(Request $request)
    {
        try {
            $user = $request->user();
            $request->validate([
                'occupation' => 'nullable|string|max:255',
                'about_me' => 'nullable|string|max:500',
                'favourite_quote' => 'nullable|string|max:500',
            ]);
            $user->update([
                'occupation' => $request->occupation,
                'about_me' => $request->about_me,
                'favourite_quote' => $request->favourite_quote,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile details updated successfully',
                'data' => $user->fresh(), // returns updated user
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // change user name
    public function updateName(Request $request)
    {
        try {
            $request->validate([
                'new_name' => 'required|string|max:255',
                'username' => 'nullable|string|max:50',
                'password' => 'required',
            ]);

            $user = $request->user();
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated user',
                ], 401);
            }

            if (! Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incorrect password',
                ], 401);
            }

            $fullname = trim($request->new_name);

            $parts = preg_split('/\s+/', $fullname);
            $lastName = array_pop($parts);
            $firstName = implode(' ', $parts);

            if($firstName == ""){
                $firstName = $lastName;
                $lastName = null;
            }
            $user->name = $request->new_name;
            $user->first_name = $firstName;
            $user->last_name = $lastName;
            if ($request->filled('username')) {
                $user->username = $request->username;
            }
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Name updated successfully',
                'data' => [
                    'user' => $user,
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update name',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Change email

    public function updateEmail(Request $request)
    {
        try {
            $request->validate([
                'new_email' => 'required|email|unique:users,email',
                'password' => 'required',
            ]);

            if (! Hash::check($request->password, $request->user()->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incorrect password',
                ], 401);
            }

            $user = $request->user();
            $user->email = $request->new_email;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Email updated successfully',
                'data' => [
                    'email' => $user->email,
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(), // return full array of validation errors
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update email',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Change password

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 401);
        }
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ], 200);
    }

    // forgot password (send reset link)
    public function sendResetLinkEmail(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'from' => 'required|in:web,app',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        }

        $user = User::where('email', $request->input('email'))->first();

        // --- Case 1: Web (send reset link) ---
        if ($request->input('from') === 'web') {
            $status = Password::sendResetLink($request->only('email'));

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'status' => $status,
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => __($status),
            ], 400);
        }

        // --- Case 2: App (send OTP code) ---
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiration = now()->addMinutes(30);

        $user->reset_p_code = $code;
        $user->reset_p_code_expire_at = $expiration;
        $user->save();

        // Send code via email
        Mail::to($user->email)->send(new AppResetCodeMail($user, $code));

        return response()->json([
            'status' => true,
            'message' => 'A 6-digit reset code has been sent to your email address.',
            'code' => $code, // only for testing, remove in production
        ]);
    }

    // reset password

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'password' => 'required|string|min:8|confirmed',
                // either token or code is required
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        }

        $email = $request->input('email');
        $token = $request->input('token');
        $code = $request->input('code');

        // --- Case 1: Web Reset (token-based) ---
        if ($token) {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ])->save();

                    event(new PasswordReset($user));
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'status' => true,
                    'message' => 'Password reset successful (web).',
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => __($status),
            ], 400);
        }

        // --- Case 2: App Reset (OTP code) ---
        if ($code) {
            $user = User::where('email', $email)
                ->where('reset_p_code', $code)
                ->where('reset_p_code_expire_at', '>', now())
                ->first();

            if (! $user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid or expired code.',
                ], 400);
            }

            $user->password = Hash::make($request->input('password'));
            $user->reset_p_code = null;
            $user->reset_p_code_expire_at = null;
            $user->remember_token = Str::random(60);
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Password reset successful (app).',
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Missing token or code in request.',
        ], 400);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $fieldtype = filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($fieldtype, $request->email)->first();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($user->status == 3) { // 3 = Deleted
            return response()->json([
                'success' => false,
                'message' => 'This account has been deleted. Please contact support to restore it.',
            ], 403); // 403 Forbidden
        }

        if ($user->is_verified == null) {
            if ($user->is_verified != 1) {
                $code = rand(100000, 999999);
                $user->verification_code = $code;
                $user->verification_expires_at = now()->addMinutes(30);
                $expiration = now()->addMinutes(30);
                $user->save();

                $user->forceFill([
                    'verification_code' => $code,
                    'verification_expires_at' => $expiration,
                ])->save();

                // Send OTP email
                try {
                    Mail::to($user->email)->send(new VerificationMail($user, (string) $code));
                } catch (\Throwable $e) {
                    \Log::error('Login OTP email failed: ' . $e->getMessage());
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Emails is not verified yet',
                    'email' => $user->email,
                    'is_verified' => 0,
                ], 400);
            }
        } else {
            if ($user->is_verified != 1) {
                $code = rand(100000, 999999);
                $user->verification_code = $code;
                $user->verification_expires_at = now()->addMinutes(30);
                $expiration = now()->addMinutes(30);
                $user->save();

                $user->forceFill([
                    'verification_code' => $code,
                    'verification_expires_at' => $expiration,
                ])->save();

                // Send OTP email
                try {
                    Mail::to($user->email)->send(new VerificationMail($user, (string) $code));
                } catch (\Throwable $e) {
                    \Log::error('Login OTP email failed: ' . $e->getMessage());
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Emails is not verified yet',
                    'email' => $user->email,
                    'is_verified' => 0,
                ], 400);
            }
        }
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 400);
        }
        $user->update(['last_login_at' => now()]);
        if ($request->header('X-Guest-ID')) {
            $guestId = $request->header('X-Guest-ID');
            SearchHistory::where('guest_id', $guestId)
                ->update(['user_id' => $user->id, 'guest_id' => null]);
            ListingView::where('guest_id', $guestId)
                ->update([
                    'user_id' => $user->id,
                    'guest_id' => null,
                ]);
        }
        $token = $user->createToken('user-token')->plainTextToken;

        // /Verification code sent to your email. Please verify
        return response()->json([
            'success' => true,
            'message' => 'login successfull',
            // 'email' => $user->email,
            'data' => $user,
            'token' => $token,
        ], 200);
    }

    public function verifyCode(Request $request)
    {
        // dd("awd");
        $request->validate([
            'email' => 'required|string',
            'verification_code' => 'required|string',
        ]);
        $fieldtype = filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($fieldtype, $request->email)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }
        if ($user->verification_code !== $request->verification_code) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code',
            ], 400);
        }
        if (now()->greaterThan($user->verification_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code has expired',
            ], 400);
        }

        // Clear verification code and expiry
        $user->verification_code = null;
        $user->verification_expires_at = null;
        $user->save();
        $token = $user->createToken('user-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => $user,
            'token' => $token,
        ], 200);
    }

    public function deleteAccount()
    {
        $user = auth('api')->user();

        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found or unauthenticated.',
            ], 404);
        }

        // Optional: purge user activity here using UserDeletionService
        // UserDeletionService::purgeUserData($user);

        // Mark account as deleted and clear verification data
        $user->update([
            'status' => 3,
            'verification_code' => null,
            'verification_expires_at' => null,
            'is_verified' => false,
        ]);
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'Your account has been marked for deletion.',
        ]);
    }

    public function testToken(Request $request)
    {
        // Get token from Authorization header
        $authHeader = $request->header('Authorization');

        if (! $authHeader || ! str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'status' => false,
                'message' => 'Authorization token missing or invalid format.',
            ], 401);
        }

        // Extract the token value
        $token = substr($authHeader, 7);

        // Try to find the token in the database
        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken) {
            return response()->json([
                'status' => false,
                'message' => 'Token is invalid or expired.',
            ], 401);
        }

        $user = $accessToken->tokenable;

        // Optional: Check if user account is active
        if (! $user || $user->status == 3) { // 3 = deleted, as per your logic
            return response()->json([
                'status' => false,
                'message' => 'User account is inactive or deleted.',
            ], 403);
        }

        // If token and user are valid
        return response()->json([
            'status' => true,
            'message' => 'Token is valid.',
            'user' => $user,
        ]);
    }

    // User profile
    public function profile(Request $request)
    {
        $user = $request->user();

        // $userFeedback =
        $feedbacks = UserFeedback::where('reviewed_user_id', $user->id)->get();
        $totalFeedback = $feedbacks->count();
        $positive = $feedbacks->whereIn('rating', [4, 5])->count();
        $neutral = $feedbacks->where('rating', 3)->count();
        $negative = $feedbacks->whereIn('rating', [1, 2])->count();
        $feedbacks = [
            'total' => $totalFeedback,
            'positive' => $positive,
            'neutral' => $neutral,
            'negative' => $negative,
        ];
        $user->feedback_summary = new \stdClass;
        $user->feedback_summary = $feedbacks;
        $user->recent_activity = [
            'last_search' => $user->searchHistories()->latest()->first()?->keyword ?? null,
            'last_viewed_listing' => $user->listings()->with('views')->latest()->first()?->title ?? null,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Successfully Fetched',
            'data' => $user,
        ], 200);
    }

    

    

    

    

    // Upload profile photo
    public function uploadProfilePhoto(Request $request)
    {
        try {
            $user = $request->user();

            $request->validate([
                'profile_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $path = $request->file('profile_photo')->store('uploads/profile_photos', 'public');
            $user->profile_photo = $path;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile photo uploaded successfully',
                'data' => [
                    'profile_photo' => $path,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Something went wrong: '.$e->getMessage(),
                ],
                500
            );
        }
    }

    // Upload background photo
    public function uploadBackgroundPhoto(Request $request)
    {
        try {
            $user = $request->user();

            $request->validate([
                'background_photo' => 'required|image|mimes:jpeg,png,jpg|max:4096',
            ]);

            $path = $request->file('background_photo')->store('uploads/background_photos', 'public');
            $user->background_photo = $path;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Background photo uploaded successfully',
                'data' => [
                    'background_photo' => $path,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'User not found',
                ],
                404
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Something went wrong: '.$e->getMessage(),
                ],
                500
            );
        }
    }

    // Logout user
    public function logout(Request $request)
    {
        $request->user()->tokens()->where('id', $request->user()->currentAccessToken()->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully Logged out',
        ], 200);
    }

    private function generateUniqueCode()
    {
        // Characters: digits + uppercase letters
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        // Generate 8 random characters
        $randomString = '';
        for ($i = 0; $i < 8; $i++) {
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }

        // Add dash in the middle (after 4 chars)
        return substr($randomString, 0, 4).substr($randomString, 4, 4);
    }

    private function generateMemberId()
    {
        // Get Year + Month (last 2 digits of year + month)
        $prefix = now()->format('ym'); // e.g. 2509 for Sept 2025

        // Generate 3 random uppercase letters
        $letters = strtoupper(Str::random(3));

        // Get the latest memberId number part and increment
        $lastUser = User::whereNotNull('memberId')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastUser && preg_match('/-(\d{4})$/', $lastUser->memberId, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1001; // starting point
        }

        // Ensure it's 4 digits (padded with leading zeros)
        $number = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$letters}{$number}";
    }
}
