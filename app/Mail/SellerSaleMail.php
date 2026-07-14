<?php
namespace App\Mail;
use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
class SellerSaleMail extends Mailable implements ShouldQueue
{
 use Queueable,SerializesModels;
 public function __construct(public OrderItem $item){}
 public function envelope(): Envelope {return new Envelope(subject:'You made a sale: '.$this->item->product_title);}
 public function content(): Content {return new Content(markdown:'mail.seller-sale',with:['item'=>$this->item]);}
}
