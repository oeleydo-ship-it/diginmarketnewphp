<?php
namespace App\Http\Requests;
use App\Rules\MinPlainText;
use App\Support\RichText;
use Illuminate\Foundation\Http\FormRequest;
class UpdateProductRequest extends FormRequest
{
 public function authorize(): bool { return $this->user()?->can('update',$this->route('product'))??false; }
 public function rules(): array { return ['category_id'=>['required','exists:categories,id'],'title'=>['required','string','max:180'],'short_description'=>['required','string','max:500'],'description'=>['required','string',new MinPlainText(50)],'regular_price'=>['required','decimal:0,2','min:1'],'extended_price'=>['nullable','decimal:0,2','gt:regular_price'],'business_license_enabled'=>['nullable','boolean'],'support_extension_price'=>['nullable','decimal:0,2','min:1'],'support_extension_months'=>['nullable','integer','min:1','max:36'],'demo_url'=>['nullable','url:https','max:500'],'video_url'=>['nullable','url:https','max:500']]; }
 protected function prepareForValidation(): void
 {
  if($this->exists('description'))$this->merge(['description'=>RichText::sanitize((string)$this->input('description'))]);
  $this->merge([
   'support_extension_price'=>$this->filled('support_extension_price')?$this->input('support_extension_price'):null,
   'support_extension_months'=>$this->filled('support_extension_months')?(int)$this->input('support_extension_months'):6,
  ]);
 }
}
