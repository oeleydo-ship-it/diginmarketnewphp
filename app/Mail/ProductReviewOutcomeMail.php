<?php
namespace App\Mail;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
class ProductReviewOutcomeMail extends Mailable implements ShouldQueue
{
 use Queueable,SerializesModels;
 public function __construct(public Product $product,public string $outcome,public string $notes=''){}
 public function envelope(): Envelope {return new Envelope(subject:$this->outcome==='approved'?('Your product is live: '.$this->product->title):('Changes requested: '.$this->product->title));}
 public function content(): Content {return new Content(markdown:'mail.product-review-outcome',with:['product'=>$this->product,'outcome'=>$this->outcome,'notes'=>$this->notes]);}
}
