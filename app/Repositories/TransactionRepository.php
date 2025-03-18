<?php 

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\User;
use App\Models\FirebaseToken;
use Illuminate\Support\Facades\DB;

class TransactionRepository
{
    /**
     * Transfer money from sender to receiver.
     *
     * @param  int  $senderId
     * @param  string  $phone
     * @param  float  $amount
     * @return Transaction|null
     */
    public function transferMoney(int $senderId, string $phone, float $amount)
    {
        try {
            DB::beginTransaction();

            // Get sender
            $sender = User::findOrFail($senderId);

            // Get receiver by phone number
            $receiver = User::where('phone', $phone)->firstOrFail();

            // Prevent sending money to self
            if ($sender->id === $receiver->id) {
                return null;
            }

            // Check sender balance
            if ($sender->balance < $amount) {
                return null;
            }

            // Deduct from sender
            $sender->balance -= $amount;
            $sender->save();

            // Add to receiver
            $receiver->balance += $amount;
            $receiver->save();

            // Log the transaction
            $transaction = new Transaction();
            $transaction->sender_id = $sender->id;
            $transaction->receiver_id = $receiver->id;
            $transaction->amount = $amount;
            $transaction->save();

            DB::commit();

             
            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get the transaction history for a user.
     *
     * @param  int  $userId
     * @param  string  $transactionType
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserTransactions(int $userId, string $transactionType)
    {
        try {
            $query = Transaction::where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                      ->orWhere('receiver_id', $userId);
            });

            if ($transactionType === 'sent') {
                $query->where('sender_id', $userId);
            } elseif ($transactionType === 'received') {
                $query->where('receiver_id', $userId);
            }

            return $query->orderBy('created_at', 'desc')->with(['receiver', 'sender'])->get();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
