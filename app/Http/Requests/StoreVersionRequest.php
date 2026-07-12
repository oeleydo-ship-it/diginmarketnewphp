<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreVersionRequest extends FormRequest
{
 public function authorize(): bool { return $this->user()?->can('addVersion',$this->route('product'))??false; }
 public function rules(): array { return ['version_number'=>['required','string','max:50'],'release_title'=>['required','string','max:180'],'release_notes'=>['nullable','string'],'archive'=>['required','file','mimes:zip','max:102400']]; }
}
