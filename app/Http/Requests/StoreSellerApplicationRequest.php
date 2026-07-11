<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreSellerApplicationRequest extends FormRequest
{
 public function authorize(): bool { return auth()->check() && !auth()->user()->sellerProfile; }
 public function rules(): array { return ['display_name'=>['required','string','max:120'],'username'=>['required','alpha_dash','max:80','unique:seller_profiles,username'],'country'=>['required','string','size:2'],'phone'=>['nullable','string','max:40'],'biography'=>['required','string','max:2000'],'business_name'=>['nullable','string','max:160'],'website'=>['nullable','url','max:255']]; }
}