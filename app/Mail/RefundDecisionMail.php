<?php
namespace App\Mail;
use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
class RefundDecisionMail extends Mailable implements ShouldQueue
{
 use Queueable,SerializesModels;
 public function __construct(public RefundRequest $refund){}
 public function envelope(): Envelope {return new Envelope(subject:'Refund request '.$this->refund->number.' '.($this->refund->status==='refunded'?'approved':'declined'));}
 public function content(): Content {return new Content(markdown:'mail.refund-decision',with:['refund'=>$this->refund]);}
}
