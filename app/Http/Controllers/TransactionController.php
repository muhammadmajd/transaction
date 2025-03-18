<?php

namespace App\Http\Controllers;

use App\Repositories\TransactionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User; 
use App\Mail\SendMail;
use App\Repositories\FireBaseTokenRepository;
use Cache;


use Illuminate\Support\Facades\Mail;
class TransactionController extends Controller
{
    protected $transactionRepository;
    protected $firebaseTokenRepository;

    public function __construct(TransactionRepository $transactionRepository,FireBaseTokenRepository $firebaseTokenRepository)
    {
        $this->transactionRepository = $transactionRepository;
        $this->firebaseTokenRepository = $firebaseTokenRepository;
    }

    /**
     * Transfer money from a sender to a receiver using phone number.
     */
    public function transferMoney(Request $request)
    {
        try {
            $fields = $request->validate([
                'sender_id' => 'required|exists:users,id',
                'phone' => 'required|string|exists:users,phone',
                'amount' => 'required|numeric|min:1',
            ]);

       
            // Get receiver by phone number
            $receiver = User::where('phone', $fields['phone'])->first();

            if (!$receiver) {
                return response()->json(['message' => 'Receiver not found.'], 404);
            }

            
            // Generate a random verification code
            $verificationCode = rand(100000, 999999);
            Log::info("User created successfully with ID :". $verificationCode);
            
            // Send the verification code via email to the sender
            $sender = User::find($fields['sender_id']);
             // store code in a temporary session or a table for persistence.
             //session(['transaction_verification_code' => $verificationCode]);
             Cache::put('transaction_verification_code_' . $fields['sender_id'], $verificationCode,now()->addMinutes(10)); // Store with an expiration time of 10 minutes
 
            Log::info("User   :". $sender->email);
            $this->sendVerificationEmail($sender->email, $verificationCode);
            
            Log::info("verificationCodes  :". $verificationCode);
 
            return response()->json([
                'message' => 'Code Vervication Transaction sent please confirm the operation.',
               // 'transaction' => $transaction,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Transaction error: ' . $e->getMessage());

            return response()->json([
                'message' => 'An error occurred while processing the transaction. Please try again later.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function verifyTransaction(Request $request)
        {
            $fields =$request->validate([
                'verification_code' => 'required|numeric',
                 'sender_id' => 'required|numeric', 
                 'phone' => 'required|string|exists:users,phone',
                'amount' => 'required|numeric|min:1',
            ]);
           
            
            // Retrieve the verification code from the session 
            $sessionVerificationCode = Cache::get('transaction_verification_code_'.$fields['sender_id']);
          //  $sessionVerificationCode = (string) session('transaction_verification_code');
            Log::info("sessionVerificationCode :". $sessionVerificationCode);
            if ($request->verification_code == $sessionVerificationCode) {
    
                $transaction = $this->transactionRepository->transferMoney(
                    $fields['sender_id'],
                    $fields['phone'],
                    $fields['amount']
                );
                if ($transaction === null) {
                    return response()->json([
                        'message' => 'Transaction failed. Please check the sender balance or ensure the receiver exists and is not the same as the sender.',
                    ], 400);
                }
                // Get active Firebase tokens for sender and receiver
                // $senderTokens = FirebaseToken::where('user_id', $sender->id)->where('active', true)->pluck('token'); 
                 $firebaseToken =$this->firebaseTokenRepository->getFirebaseTokenByPhone($fields['phone']);
                 


    
                return response()->json([
                    'message' => 'Transaction successful.',
                    'transaction' => $transaction,
                    'firebaseToken' => $firebaseToken
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Invalid verification code.',
                ], 400);
            }
        }

        
    /**
     * helper function to send email 
     */

    public function sendVerificationEmail($userEmail, $verificationCode)
        {
            try {
                Log::info("email :". $userEmail);
                // Send verification code via email to the sender
                
                Mail::to($userEmail)->send(new SendMail($verificationCode,"transaction"));
                //Mail::to($$userEmail)->send(new AccountActivationMail($verificationCode));

            } catch (\Exception $e) {
                Log::error('Error sending verification email: ' . $e->getMessage());
                throw new \Exception('Failed to send the verification code.');
            }
        }

    /**
     * Get received transaction history for a specific user.
     */
    public function getUserTransactions(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'transaction_type' => 'required',
            ]);

            $userId = $request->user_id;
            $transactionType = $request->transaction_type;

            $transactions = $this->transactionRepository->getUserTransactions($userId, $transactionType);

            if ($transactions->isEmpty()) {
                return response()->json([
                    'message' => 'No transactions found for this user.',
                ], 404);
            }

            // Format the transactions
            $formattedTransactions = $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'created_at' => $transaction->created_at,
                    'transaction_type' => $transaction->sender_id === $transaction->user_id ? 'sent' : 'received',
                    'user' => [
                        'id' => $transaction->receiver->id,
                        'name' => $transaction->receiver->name,
                        'phone' => $transaction->receiver->phone,
                    ]
                ];
            });

            return response()->json([
                'message' => 'Transaction history retrieved successfully.',
                'transactions' => $formattedTransactions,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Transaction retrieval error: ' . $e->getMessage());

            return response()->json([
                'message' => 'An error occurred while retrieving transactions. Please try again later.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
