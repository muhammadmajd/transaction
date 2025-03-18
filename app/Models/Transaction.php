<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    // Validate request
    protected $fillable=[
        'sender_id' => 'required|exists:users,id',
        'phone' => 'required|string|exists:users,phone',
        'amount' => 'required|numeric|min:1',
    ];

    // Detect User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

       // Define relationship with sender (User)
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Define relationship with receiver (User)
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }


     /**
     * Convert the transaction to JSON.
     *
     * @return array
     */
    public function transactionToJson()
    {
        return [
            'id' => $this->id,
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'amount' => $this->amount,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'sender' => $this->sender,  // Assuming relationship exists
            'receiver' => $this->receiver // Assuming relationship exists
        ];
    }

    /**
     * Populate the transaction from a JSON object.
     *
     * @param  array  $data
     * @return Transaction
     */
    public function transactionFromJson(array $data)
    {
        $this->sender_id = $data['sender_id'];
        $this->receiver_id = $data['receiver_id'];
        $this->amount = $data['amount'];
        return $this;
    }

}
