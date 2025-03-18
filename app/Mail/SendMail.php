<?php
namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
class SendMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;
    public $type;

    public function __construct($code, $type)
    {
        $this->code = $code;
        $this->type = $type;
    }

    public function build()
    {
        if($this->type=='transaction')
        {

            return $this->subject('Transaction Verification Code')
                    ->view('emails.transaction_verification')
                    ->with([
                        'verificationCode' => $this->code,
                    ]);
        }  else if($this->type=='forget')
        {
            return $this->subject('Transaction Verification Code')
            ->view('emails.new_password')
            ->with([
                'newPassword' => $this->code,
            ]);
        } else if($this->type=="AccountActivationMail")
        {
            return $this->subject('Activate Your Account')
                        ->view('emails.activate-account')
                        ->with(['verificationCode' => $this->code]);
        }  else if($this->type=='ResetPassword')
        {
            return $this->subject('Password Changed Successfully')
            ->view('emails.password_changed')
            ->with([
                'newPassword' => " ",
            ]);

             

        }
        
    }
}