<?php
namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Controller;
use App\Http\Resources\MarketplaceListingResource;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVariant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class MarketplaceVariantController extends Controller {
    public function generate(Request $request, MarketplaceListing $listing) {
        $validated=$request->validate(['default_stock'=>['nullable','integer','min:0'],'replace'=>['nullable','boolean']]);
        $listing->load('fashionDetails'); abort_unless($listing->fashionDetails,422,'Variants can currently be generated for fashion listings only.');
        $groups=['gender'=>$listing->fashionDetails->genders??[],'size'=>$listing->fashionDetails->sizes??[],'color'=>$listing->fashionDetails->colors??[]];
        $groups=array_filter($groups,fn($values)=>count($values)>0); abort_if(!$groups,422,'Add at least one gender, size, or color before generating variants.');
        $combinations=[[]]; foreach($groups as $name=>$values){$next=[];foreach($combinations as $combination)foreach($values as $value)$next[]=$combination+[$name=>$value];$combinations=$next;}
        if($validated['replace']??false)$listing->variants()->delete();
        foreach($combinations as $options)$listing->variants()->firstOrCreate(['option_key'=>MarketplaceListingVariant::optionKey($options)],['options'=>$options,'stock_quantity'=>$validated['default_stock']??0,'is_active'=>true]);
        return new MarketplaceListingResource($listing->fresh()->load(['category','images','vehicleDetails','fashionDetails','gadgetDetails','variants']));
    }
    public function update(Request $request, MarketplaceListing $listing, MarketplaceListingVariant $variant) {
        abort_unless($variant->listing_id===$listing->id,404);
        $data=$request->validate(['sku'=>['nullable','string','max:100',Rule::unique('marketplace_listing_variants','sku')->ignore($variant)],'price'=>['nullable','numeric','min:0'],'stock_quantity'=>['required','integer','min:0'],'is_active'=>['required','boolean']]);
        $variant->update($data); return new MarketplaceListingResource($listing->fresh()->load(['category','images','vehicleDetails','fashionDetails','gadgetDetails','variants']));
    }
    public function destroy(MarketplaceListing $listing, MarketplaceListingVariant $variant) {abort_unless($variant->listing_id===$listing->id,404);$variant->delete();return response()->json(null,204);}
}
