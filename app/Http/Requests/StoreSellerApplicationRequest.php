<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreSellerApplicationRequest extends FormRequest
{
 public function authorize(): bool { return \App\Models\Setting::enabled('features.seller_applications') && auth()->check() && !auth()->user()->sellerProfile; }
 public function rules(): array { return ['full_name'=>['required','string','max:160'],'display_name'=>['required','string','max:120'],'username'=>['required','alpha_dash','max:80','unique:seller_profiles,username'],'country'=>['required','string','size:2'],'address'=>['required','string','max:255'],'city'=>['required','string','max:120'],'postal_code'=>['nullable','string','max:20'],'phone'=>['nullable','string','max:40'],'biography'=>['required','string','max:2000'],'business_name'=>['nullable','string','max:160'],'website'=>['nullable','url','max:255']]; }
 protected function prepareForValidation(): void { $this->merge(['business_name'=>filled($this->input('business_name'))?$this->input('business_name'):null]); }
}