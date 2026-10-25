<?php
namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
/** One short seller account email: a heading, a line of copy, optional reviewer notes and a button. */
class SellerNoticeMail extends Mailable implements ShouldQueue
{
 use Queueable,SerializesModels;
 public function __construct(public string $heading,public string $body,public string $actionUrl,public string $actionLabel,public string $notes=''){}
 public function envelope(): Envelope {return new Envelope(subject:$this->heading);}
 public function content(): Content {return new Content(markdown:'mail.seller-notice',with:['heading'=>$this->heading,'body'=>$this->body,'actionUrl'=>$this->actionUrl,'actionLabel'=>$this->actionLabel,'notes'=>$this->notes]);}
}
