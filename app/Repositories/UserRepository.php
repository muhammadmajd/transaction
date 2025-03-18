<?php
namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
 
use App\Mail\SendMail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Exception;

class UserRepository
{
    /**
     * Create a new user.
     *
     * @param  array  $data
     * @return User
     */
    public function createUser(array $data)
    {

        // Generate verification code
        $verificationCode = Str::random(6);
        // Create a new user
        $user = User::create([
            'fname' => $data['fname'],
            'lname' => $data['lname'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'verification_code' => $verificationCode, // Store verification code
        ]);
        // Log message after user creation
        Log::info("User created successfully with ID: {$user->id}, Email: {$user->email}");


        // Send email with the verification code
        
         // Send activation email
        // Mail::to($user->email)->send(new AccountActivationMail($verificationCode));
         Mail::to($user->email)->send(new SendMail($verificationCode,"AccountActivationMail"));




        return $user;
    }

    /**
     * Authenticate the user for login.
     *
     * @param  string  $email
     * @param  string  $password
     * @return User|null
     */
    public function authenticateUser(string $email, string $password)
    {
        $user = User::where('email', $email)->first();

        // Check if user exists and if password is correct
        if ($user && Hash::check($password, $user->password)) {
            // Check if account is activated
            /*if (!$user->email_verified_at) {
                return null; // Return null if account is not activated
            }*/
            return $user;
        }

        return null;
    }

    public function authenticateUserActivated(string $email, string $password)
    {
        $user = User::where('email', $email)->first();

        // Check if user exists and if password is correct
        if ($user && Hash::check($password, $user->password)) {
            // Check if account is activated
            if (!$user->email_verified_at) {
                return false; // Return null if account is not activated
            }
            return true;
        }

        return null;
    }

    /**
     * Generate an API token for the user.
     *
     * @param  User  $user
     * @return string
     */
    public function generateToken(User $user)
    {
        $token = $user->createToken($user->fname);
        return $token->plainTextToken;
    }

    /**
     * Activate the user's account.
     *
     * @param  User  $user
     * @return User
     */
    public function activateAccount(User $user)
    {
        // Set the email_verified_at field to the current timestamp
        $user->email_verified_at = Carbon::now();
        $user->save();

        return $user;
    }


    public function forgetPassword($email) {
        try {
            // Check if user exists
            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json(['message' => 'User not found.'], 404);
            }

            // Generate new password
            $newPassword = Str::random(10);
            $user->password = Hash::make($newPassword);
            $user->save();

            // Send email with new password
            Mail::to($email)->send(new SendMail($newPassword,"forget"));

            return response()->json(['message' => 'A new password has been sent to your email.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to process request.', 'error' => $e->getMessage()], 500);
        }
    }


    // Reset password with current password and send confirmation
    public function resetPassword($userId, $currentPassword, $newPassword) {
        try {
            // Find user by ID
            $user = User::find($userId);
            if (!$user) {
                return response()->json(['message' => 'User not found.'], 404);
            }

            // Check if current password matches
            if (!Hash::check($currentPassword, $user->password)) {
                return response()->json(['message' => 'Current password is incorrect.'], 400);
            }

            // Update password
            $user->password = Hash::make($newPassword);
            $user->save();

            // Send confirmation email
           
         Mail::to($user->email)->send(new SendMail($user->password,"ResetPassword"));


            return response()->json(['message' => 'Password updated successfully.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to reset password.', 'error' => $e->getMessage()], 500);
        }
    }

}
