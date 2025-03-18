<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;
use App\Models\FirebaseToken;
use App\Models\User; 

class AuthController extends Controller
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Register a new user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        Log::info("User created successfully with ID ");
        info("User created successfully: ID ");
        try {
            // Validate the request data
            $fields = $request->validate([
                'fname' => 'required|string',
                'lname' => 'required|string',
                'phone' => 'required|string|unique:users',
                'password' => 'required|string|min:6',
                'email' => 'email|unique:users', // Ensure email is unique if provided
            ]);

            // Create user using the repository
            $user = $this->userRepository->createUser($fields);
            info("User created successfully: ID - {$user->id}, Email - {$user->email}");
            Log::info("User created successfully with ID: {$user->id}, Email: {$user->email}");

            // Generate token
            $token = $this->userRepository->generateToken($user);
            // Firebase token
            $firebaseToken = FirebaseToken::updateOrCreate(
                ['user_id' => $user->id, 'token' => $request->firebase_token],
                ['active' => true]
            )->only(['token']);
            // Return response
            return response()->json([
                'message' => 'User registered successfully. Check your email for the verification code.',
                'user' => $user,
                'token' => $token,
                'firebase_token' => $request->firebase_token
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to process the registration. Please try again later.'], 500);
        }
    }

    /**
     * Login an existing user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // Validate the request data
        $request->validate([
            'email' => 'email',
            'password' => 'required|string',
            //'firebase_token' => 'required|string', // Ensure Firebase token is provided ,
        ]);

        try {
            // Authenticate user using the repository
            $user = $this->userRepository->authenticateUser($request->email, $request->password);

            if (!$user) {
                return response()->json([
                    'message' => 'The provided credentials are not correct ',
                ], 401);
            }
            // Check activated account
            if (!$user->email_verified_at) {
        return response()->json(['message' => 'Please verify your email to activate your account.'], 403);
    }
            
            $activated = $this->userRepository->authenticateUserActivated($request->email, $request->password);
          // save firebaase token
         
          if($request->firebase_token!='')
            $firebaseToken = FirebaseToken::updateOrCreate(
                ['user_id' => $user->id, 'token' => $request->firebase_token],
                ['active' => true]
            )->only(['token']);
            // Generate token
            $token = $this->userRepository->generateToken($user);

            // Return response
            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token,
                'activated' => $activated,
                'firebase_token' => $firebaseToken 
            ], 200);

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while logging in. Please try again later.'], 500);
        }
    }

    public function verifyCode(Request $request)
        {
            $request->validate([
                'email' => 'required|email',
                'verification_code' => 'required|string',
            ]);

            $user = User::where('email', $request->email)
                        ->where('verification_code', $request->verification_code)
                        ->first();

            if (!$user) {
                return response()->json(['message' => 'Invalid verification code.'], 400);
            }

            // Activate the account
            $user->email_verified_at = now();
            $user->verification_code = null; // Remove code after activation
            $user->save();

            return response()->json(['message' => 'Account successfully activated.'], 200);
        }

    /**
     * Activate the user's account.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function activateAccount(Request $request)
    {
        try {
            // Validate the request data
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'code' => 'required|string',
            ]);

            // Retrieve user
            $user = User::find($request->user_id);

            // Activate the account
            $this->userRepository->activateAccount($user);

            return response()->json([
                'message' => 'Account activated successfully',
                'user' => $user
            ], 200);

        } catch (\Exception $e) {
            Log::error('Activation error: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while activating the account. Please try again later.'], 500);
        }
    }

    /**
     * Logout the user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $user->tokens()->delete();

            if($request->firebase_token!='')
            FirebaseToken::where('user_id', $user->id)
            ->where('token', $request->firebase_token)
            ->update(['active' => false]);

            return response()->json(['message' => 'Logged out successfully']);

        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while logging out. Please try again later.'], 500);
        }
    }

    /**
     * Get the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }


      // Forget password and send a new password via email
      public function forgetPassword(Request $request) {
        //$2y$12$W9sP8tWP5OpQ0lD.rpZuseaT7DG3uqiR5pxVhXSq4Zib2Cb47RxbG
        // Validate the email address
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            return $this->userRepository->forgetPassword($request->email);
        } catch (\Exception $e) {
            Log::error('Error in forgetPassword: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred, please try again later.'], 500);
        }
    }

    // Reset password with current password and new password
    public function resetPassword(Request $request) {
        // Validate the request data
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        try {
            return $this->userRepository->resetPassword($request->user()->id, $request->current_password, $request->new_password);
        } catch (\Exception $e) {
            Log::error('Error in resetPassword: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred, please try again later.'], 500);
        }
    }
}
