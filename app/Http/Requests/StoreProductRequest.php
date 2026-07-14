<?php
namespace App\Http\Requests;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
class StoreProductRequest extends FormRequest
{
 public function authorize(): bool { return $this->user()?->can('create',Product::class)??false; }
 public function rules(): array { return ['category_id'=>['required','exists:categories,id'],'title'=>['required','string','max:180'],'short_description'=>['required','string','max:500'],'description'=>['required','string','min:50'],'regular_price'=>['required','decimal:0,2','min:1'],'extended_price'=>['nullable','decimal:0,2','gt:regular_price'],'business_license_enabled'=>['nullable','boolean'],'version_number'=>['required','string','max:50'],'release_title'=>['required','string','max:180'],'release_notes'=>['nullable','string'],'archive'=>['required','file','mimes:zip','max:102400']]; }
}