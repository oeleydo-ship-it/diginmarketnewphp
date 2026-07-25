<?php
namespace App\Mail;
use App\Models\ProductVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
class ProductUpdateMail extends Mailable implements ShouldQueue
{
 use Queueable,SerializesModels;
 public function __construct(public ProductVersion $version,public string $downloadUrl){}
 public function envelope(): Envelope {return new Envelope(subject:'Update available: '.$this->version->product->title.' '.$this->version->version_number);}
 public function content(): Content {return new Content(markdown:'mail.product-update',with:['version'=>$this->version,'product'=>$this->version->product,'downloadUrl'=>$this->downloadUrl]);}
}
