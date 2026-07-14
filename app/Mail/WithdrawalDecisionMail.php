<?php
namespace App\Mail;
use App\Models\WithdrawalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
class WithdrawalDecisionMail extends Mailable implements ShouldQueue
{
 use Queueable,SerializesModels;
 public function __construct(public WithdrawalRequest $withdrawal){}
 public function envelope(): Envelope {return new Envelope(subject:'Withdrawal '.$this->withdrawal->number.' '.($this->withdrawal->status==='paid'?'has been paid':'was declined'));}
 public function content(): Content {return new Content(markdown:'mail.withdrawal-decision',with:['withdrawal'=>$this->withdrawal]);}
}
