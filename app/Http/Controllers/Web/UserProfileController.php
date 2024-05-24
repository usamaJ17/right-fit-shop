<?php

namespace App\Http\Controllers\Web;

use App\Utils\Helpers;
use App\Events\OrderStatusEvent;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\DeliveryMan;
use App\Models\DeliveryZipCode;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductCompare;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\Seller;
use App\Models\ShippingAddress;
use App\Models\SupportTicket;
use App\Models\Wishlist;
use App\Traits\CommonTrait;
use App\User;
use App\Utils\CustomerManager;
use App\Utils\ImageManager;
use App\Utils\OrderManager;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{
    use CommonTrait;

    public function __construct(
        private Order $order,
        private Seller $seller,
        private Product $product,
        private Review $review,
        private DeliveryMan $deliver_man,
        private ProductCompare $compare,
        private Wishlist $wishlist,
    )
    {

    }

    public function user_profile(Request $request)
    {
        $wishlists = $this->wishlist->whereHas('wishlistProduct', function ($q) {
            return $q;
        })->where('customer_id', auth('customer')->id())->count();
        $total_order = $this->order->where('customer_id', auth('customer')->id())->count();
        $total_loyalty_point = auth('customer')->user()->loyalty_point;
        $total_wallet_balance = auth('customer')->user()->wallet_balance;
        $addresses = ShippingAddress::where('customer_id', auth('customer')->id())->latest()->get();
        $customer_detail = User::where('id', auth('customer')->id())->first();

        return view(VIEW_FILE_NAMES['user_profile'], compact('customer_detail', 'addresses', 'wishlists', 'total_order', 'total_loyalty_point', 'total_wallet_balance'));
    }

    public function user_account(Request $request)
    {
        $country_restrict_status = Helpers::get_business_settings('delivery_country_restriction');
        $customerDetail = User::where('id', auth('customer')->id())->first();
        return view(VIEW_FILE_NAMES['user_account'], compact('customerDetail'));

    }
    public function user_update(Request $request)
    {
        $request->validate([
            'f_name' => 'required',
            'l_name' => 'required',
        ], [
            'f_name.required' => 'First name is required',
            'l_name.required' => 'Last name is required',
        ]);
        if ($request->password) {
            $request->validate([
                'password' => 'required|min:8|same:confirm_password'
            ]);
        }

        if (User::where('id', '!=', auth('customer')->id())->where(['phone'=>$request['phone']])->first()) {
            Toastr::warning(translate('phone_already_taken'));
            return back();
        }

        $image = $request->file('image');

        if ($image != null) {
            $imageName = ImageManager::update('profile/', auth('customer')->user()->image, 'webp', $request->file('image'));
        } else {
            $imageName = auth('customer')->user()->image;
        }

        User::where('id', auth('customer')->id())->update([
            'image' => $imageName,
        ]);

        $userDetails = [
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'phone' => $request->phone,
            'password' => strlen($request->password) > 5 ? bcrypt($request->password) : auth('customer')->user()->password,
        ];
        if (auth('customer')->check()) {
            User::where(['id' => auth('customer')->id()])->update($userDetails);
            Toastr::info(translate('updated_successfully'));
            return redirect()->back();
        } else {
            return redirect()->back();
        }
    }

    public function account_address_add()
    {
        $country_restrict_status = Helpers::get_business_settings('delivery_country_restriction');
        $zip_restrict_status = Helpers::get_business_settings('delivery_zip_code_area_restriction');
        $default_location = Helpers::get_business_settings('default_location');

        $countries = $country_restrict_status ? $this->get_delivery_country_array() : COUNTRIES;

        $zip_codes = $zip_restrict_status ? DeliveryZipCode::all() : 0;

        return view(VIEW_FILE_NAMES['account_address_add'], compact('countries', 'zip_restrict_status', 'zip_codes', 'default_location'));
    }

    public function account_delete($id)
    {
        if (auth('customer')->id() == $id) {
            $user = User::find($id);

            $ongoing = ['out_for_delivery','processing','confirmed', 'pending'];
            $order = Order::where('customer_id', $user->id)->whereIn('order_status', $ongoing)->count();
            if($order>0){
                Toastr::warning(translate('you_can`t_delete_account_due_ongoing_order'));
                return redirect()->back();
            }
            auth()->guard('customer')->logout();

            ImageManager::delete('/profile/' . $user['image']);
            session()->forget('wish_list');

            $user->delete();
            Toastr::info(translate('Your_account_deleted_successfully!!'));
            return redirect()->route('home');
        }

        Toastr::warning(translate('access_denied').'!!');
        return back();
    }

    public function account_address(): View|RedirectResponse
    {
        $country_restrict_status = getWebConfig(name: 'delivery_country_restriction');
        $zip_restrict_status = getWebConfig(name: 'delivery_zip_code_area_restriction');

        $countries = $country_restrict_status ? $this->get_delivery_country_array() : COUNTRIES;
        $zip_codes = $zip_restrict_status ? DeliveryZipCode::all() : 0;

        if (auth('customer')->check()) {
            $shippingAddresses = ShippingAddress::where('customer_id', auth('customer')->id())->latest()->get();
            return view('web-views.users-profile.account-address', compact('shippingAddresses', 'country_restrict_status', 'zip_restrict_status', 'countries', 'zip_codes'));
        } else {
            return redirect()->route('home');
        }
    }

    public function address_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phone' => 'required',
            'city' => 'required',
            'zip' => 'required',
            'country' => 'required',
            'address' => 'required',
        ]);

        $country_restrict_status = Helpers::get_business_settings('delivery_country_restriction');
        $zip_restrict_status = Helpers::get_business_settings('delivery_zip_code_area_restriction');

        $country_exist = self::delivery_country_exist_check($request->country);
        $zipcode_exist = self::delivery_zipcode_exist_check($request->zip);

        if ($country_restrict_status && !$country_exist) {
            Toastr::error(translate('Delivery_unavailable_in_this_country!'));
            return back();
        }

        if ($zip_restrict_status && !$zipcode_exist) {
            Toastr::error(translate('Delivery_unavailable_in_this_zip_code_area!'));
            return back();
        }

        $address = [
            'customer_id' => auth('customer')->check() ? auth('customer')->id() : null,
            'contact_person_name' => $request->name,
            'address_type' => $request->addressAs,
            'address' => $request->address,
            'city' => $request->city,
            'zip' => $request->zip,
            'country' => $request->country,
            'phone' => $request->phone,
            'is_billing' => $request->is_billing,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        DB::table('shipping_addresses')->insert($address);

        Toastr::success(translate('address_added_successfully!'));

        if(theme_root_path() == 'default'){
            return back();
        }else{
            return redirect()->route('user-profile');
        }
    }

    public function address_edit(Request $request, $id)
    {
        $shippingAddress = ShippingAddress::where('customer_id', auth('customer')->id())->find($id);
        $country_restrict_status = Helpers::get_business_settings('delivery_country_restriction');
        $zip_restrict_status = Helpers::get_business_settings('delivery_zip_code_area_restriction');

        if ($country_restrict_status) {
            $delivery_countries = self::get_delivery_country_array();
        } else {
            $delivery_countries = 0;
        }
        if ($zip_restrict_status) {
            $delivery_zipcodes = DeliveryZipCode::all();
        } else {
            $delivery_zipcodes = 0;
        }
        if (isset($shippingAddress)) {
            return view(VIEW_FILE_NAMES['account_address_edit'], compact('shippingAddress', 'country_restrict_status', 'zip_restrict_status', 'delivery_countries', 'delivery_zipcodes'));
        } else {
            Toastr::warning(translate('access_denied'));
            return back();
        }
    }

    public function address_update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phone' => 'required',
            'city' => 'required',
            'zip' => 'required',
            'country' => 'required',
            'address' => 'required',
        ]);

        $country_restrict_status = Helpers::get_business_settings('delivery_country_restriction');
        $zip_restrict_status = Helpers::get_business_settings('delivery_zip_code_area_restriction');

        $country_exist = self::delivery_country_exist_check($request->country);
        $zipcode_exist = self::delivery_zipcode_exist_check($request->zip);

        if ($country_restrict_status && !$country_exist) {
            Toastr::error(translate('Delivery_unavailable_in_this_country!'));
            return back();
        }

        if ($zip_restrict_status && !$zipcode_exist) {
            Toastr::error(translate('Delivery_unavailable_in_this_zip_code_area!'));
            return back();
        }


        $updateAddress = [
            'contact_person_name' => $request->name,
            'address_type' => $request->addressAs,
            'address' => $request->address,
            'city' => $request->city,
            'zip' => $request->zip,
            'country' => $request->country,
            'phone' => $request->phone,
            'is_billing' => $request->is_billing,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (auth('customer')->check()) {
            ShippingAddress::where('id', $request->id)->update($updateAddress);
            Toastr::success(translate('address_updated_successfully!'));
            return redirect()->back();
        } else {
            Toastr::error(translate('Insufficient_permission!'));
            return redirect()->back();
        }
    }

    public function address_delete(Request $request)
    {
        if (auth('customer')->check()) {
            ShippingAddress::destroy($request->id);
            Toastr::success(translate('address_Delete_Successfully'));
            return redirect()->back();
        } else {
            return redirect()->back();
        }
    }

    public function account_payment()
    {
        if (auth('customer')->check()) {
            return view('web-views.users-profile.account-payment');

        } else {
            return redirect()->route('home');
        }

    }

    public function account_order(Request $request)
    {
        $order_by = $request->order_by ?? 'desc';
        if(theme_root_path() == 'theme_fashion'){
            $show_order = $request->show_order ?? 'ongoing';

            $array = ['pending','confirmed','out_for_delivery','processing'];
            $orders = $this->order->withSum('orderDetails', 'qty')
                ->where(['customer_id'=> auth('customer')->id(), 'is_guest'=>'0'])
                ->when($show_order == 'ongoing', function($query) use($array){
                    $query->whereIn('order_status',$array);
                })
                ->when($show_order == 'previous', function($query) use($array){
                    $query->whereNotIn('order_status',$array);
                })
                ->when($request['search'], function($query) use($request){
                        $query->where('id', 'like', "%{$request['search']}%");
                })
                ->orderBy('id', $order_by)->paginate(10)->appends(['show_order'=>$show_order, 'search'=>$request->search]);
        }else{
            $orders = $this->order->withSum('orderDetails', 'qty')->where(['customer_id'=> auth('customer')->id(), 'is_guest'=>'0'])
                ->orderBy('id', $order_by)
                ->paginate(10);
        }

        return view(VIEW_FILE_NAMES['account_orders'], compact('orders', 'order_by'));
    }

    public function account_order_details(Request $request)
    {
        $order = $this->order->with(['deliveryManReview','customer','offlinePayments', 'details.product.reviewsByCustomer' => function($query){
            return $query->where('customer_id', auth('customer')->id());
        }])
        ->where(['customer_id'=>auth('customer')->id(), 'is_guest'=>'0'])
        ->find($request->id);
        $order?->details?->map(function($detail)use($order){
            $order['total_qty'] += $detail->qty;
        });

        $refund_day_limit = \App\Utils\Helpers::get_business_settings('refund_day_limit');
        $current_date = \Carbon\Carbon::now();
        if($order){
            return view(VIEW_FILE_NAMES['account_order_details'], compact('order', 'refund_day_limit', 'current_date'));
        }

        Toastr::warning(translate('invalid_order'));
        return redirect()->route('account-oder');
    }

    public function account_order_details_seller_info(Request $request)
    {
        $order = $this->order->with(['seller.shop'])->find($request->id);
        if(!$order) {
            Toastr::warning(translate('invalid_order'));
            return redirect()->route('account-oder');
        }
        $product_ids = $this->product->where(['added_by' => $order->seller_is , 'user_id'=>$order->seller_id])->pluck('id');
        $rating = $this->review->whereIn('product_id', $product_ids);
        $avg_rating = $rating->avg('rating') ?? 0 ;
        $rating_percentage = round(($avg_rating * 100) / 5);
        $rating_count = $rating->count();
        $product_count = $this->product->where(['added_by' => $order->seller_is , 'user_id'=>$order->seller_id])->active()->count();

        return view(VIEW_FILE_NAMES['seller_info'], compact('avg_rating', 'product_count', 'rating_count', 'order', 'rating_percentage'));

    }

    public function account_order_details_delivery_man_info(Request $request)
    {

        $order = $this->order->with(['verificationImages', 'details.product','deliveryMan.rating', 'deliveryManReview','deliveryMan'=>function($query){
                return $query->withCount('review');
            }])->find($request->id);

        if(!$order) {
            Toastr::warning(translate('invalid_order'));
            return redirect()->route('account-oder');
        }

        if(theme_root_path() == 'theme_fashion' || theme_root_path() == 'default') {
            foreach($order->details as $details) {
                if($details->product) {
                    if($details->product->product_type == 'physical'){
                        $order['product_type_check'] = $details->product->product_type;
                        break;
                    }else{
                        $order['product_type_check'] = $details->product->product_type;
                    }
                }
            }
        }

        $delivered_count = $this->order->where(['order_status' => 'delivered', 'delivery_man_id' => $order->delivery_man_id, 'delivery_type' => 'self_delivery'])->count();

        return view(VIEW_FILE_NAMES['delivery_man_info'], compact('delivered_count', 'order'));
    }
    public function account_order_details_reviews(Request $request){
        $order = $this->order->with('orderDetails.product.reviewsByCustomer')->where(['id' => $request->id])->first();
        if(!$order) {
            Toastr::warning(translate('invalid_order'));
            return redirect()->route('account-oder');
        }
        return view(VIEW_FILE_NAMES['order_details_review'], compact('order'));
    }


    public function account_wishlist()
    {
        if (auth('customer')->check()) {
            $wishlists = Wishlist::where('customer_id', auth('customer')->id())->get();
            return view('web-views.products.wishlist', compact('wishlists'));
        } else {
            return redirect()->route('home');
        }
    }

    public function account_tickets()
    {
        if (auth('customer')->check()) {
                $supportTickets = SupportTicket::where('customer_id', auth('customer')->id())->latest()->paginate(10);
            return view(VIEW_FILE_NAMES['account_tickets'], compact('supportTickets'));
        } else {
            return redirect()->route('home');
        }
    }

    public function submitSupportTicket(Request $request): RedirectResponse
    {
        $request->validate([
            'ticket_subject' => 'required',
            'ticket_type' => 'required',
            'ticket_priority' => 'required',
            'ticket_description' => 'required_without_all:image.*',
            'image.*' => 'required_without_all:ticket_description|image|mimes:jpeg,png,jpg,gif|max:6000',
        ], [
            'ticket_subject.required' => translate('The_ticket_subject_is_required'),
            'ticket_type.required' => translate('The_ticket_type_is_required'),
            'ticket_priority.required' => translate('The_ticket_priority_is_required'),
            'ticket_description.required_without_all' => translate('Either_a_ticket_description_or_an_image_is_required'),
            'image.*.required_without_all' => translate('Either_a_ticket_description_or_an_image_is_required'),
            'image.*.image' => translate('The_file_must_be_an_image'),
            'image.*.mimes' => translate('The_file_must_be_of_type:_jpeg,_png,_jpg,_gif'),
            'image.*.max' => translate('The_image_must_not_exceed_6_MB'),
        ]);

        $image = [] ;
        if ($request->file('image')) {
            foreach ($request['image'] as $key => $value) {
                $image_name = ImageManager::upload('support-ticket/', 'webp', $value);
                $image[] = $image_name;
            }
        }

        $ticket = [
            'subject' => $request['ticket_subject'],
            'type' => $request['ticket_type'],
            'customer_id' => auth('customer')->check() ? auth('customer')->id() : null,
            'priority' => $request['ticket_priority'],
            'description' => $request['ticket_description'],
            'attachment' => json_encode($image),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        DB::table('support_tickets')->insert($ticket);
        return back();
    }

    public function single_ticket(Request $request)
    {
        $ticket = SupportTicket::with(['conversations'=>function($query){
            $query->when(theme_root_path() == 'default' ,function($sub_query){
                $sub_query->orderBy('id', 'desc');
            });
        }])->where('id', $request->id)->first();
        return view(VIEW_FILE_NAMES['ticket_view'], compact('ticket'));
    }

    public function comment_submit(Request $request, $id)
    {
        if( $request->file('image') == null && empty($request['comment'])) {
            Toastr::error(translate('type_something').'!');
            return back();
        }

        DB::table('support_tickets')->where(['id' => $id])->update([
            'status' => 'open',
            'updated_at' => now(),
        ]);

        $image = [];
        if ($request->file('image')) {
            $validator =  $request->validate([
                'image.*' => 'image|mimes:jpeg,png,jpg,gif|max:6000'
            ]);
            foreach ($request->image as $key=>$value) {
                $image_name = ImageManager::upload('support-ticket/', 'webp', $value);
                $image[] = $image_name;
            }
        }
        DB::table('support_ticket_convs')->insert([
            'customer_message' => $request->comment,
            'attachment' =>json_encode($image),
            'support_ticket_id' => $id,
            'position' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Toastr::success(translate('message_send_successfully').'!');
        return back();
    }

    public function support_ticket_close($id)
    {
        DB::table('support_tickets')->where(['id' => $id])->update([
            'status' => 'close',
            'updated_at' => now(),
        ]);
        Toastr::success(translate('ticket_closed').'!');
        return redirect('/account-tickets');
    }


    public function support_ticket_delete(Request $request)
    {

        if (auth('customer')->check()) {
            $support = SupportTicket::find($request->id);

            if ($support->attachment && count(json_decode($support->attachment)) > 0) {
                foreach (json_decode($support->attachment, true) as $image) {
                    ImageManager::delete('/support-ticket/' . $image);
                }
            }

            foreach ($support->conversations as $conversation)
            {
                if ($conversation->attachment && count(json_decode($conversation->attachment)) > 0) {
                    foreach (json_decode($conversation->attachment, true) as $image) {
                        ImageManager::delete('/support-ticket/' . $image);
                    }
                }
            }
            $support->conversations()->delete();

            $support->delete();
            return redirect()->back();
        } else {
            return redirect()->back();
        }

    }

    public function track_order()
    {
        return view(VIEW_FILE_NAMES['tracking-page']);
    }
    public function track_order_wise_result(Request $request)
    {
        if (auth('customer')->check()) {
            $orderDetails = Order::with('orderDetails')->where('id', $request['order_id'])->whereHas('details', function ($query) {
                $query->where('customer_id', (auth('customer')->id()));
            })->first();

            if(!$orderDetails) {
                Toastr::warning(translate('invalid_order'));
                return redirect()->route('account-oder');
            }
            return view(VIEW_FILE_NAMES['track_order_wise_result'], compact('orderDetails'));
        }
        return back();
    }

    public function track_order_result(Request $request)
    {

        $user = auth('customer')->user();
        $user_phone = $request->phone_number ?? '';

        if (!isset($user)) {
            $user_id = User::where('phone', $request->phone_number)->first();
            $order = Order::where('id', $request['order_id'])->first();

            if($order && $order->is_guest){
                $orderDetails = Order::with('shippingAddress')->where('id', $request['order_id'])
                    ->first();

                $orderDetails = ($orderDetails && $orderDetails->shippingAddress && $orderDetails->shippingAddress->phone == $request->phone_number) ? $orderDetails : null;

                if(!$orderDetails){
                    $orderDetails = Order::where('id', $request['order_id'])
                        ->whereHas('billingAddress', function ($query) use ($request) {
                            $query->where('phone', $request->phone_number);
                        })->first();
                }
            }elseif($user_id){
                $orderDetails = Order::where('id', $request['order_id'])->whereHas('details', function ($query) use ($user_id) {
                    $query->where('customer_id', $user_id->id);
                })->first();
            }else{
                Toastr::error(translate('invalid_Phone_Number'));
                return redirect()->back()->withInput();
            }

        } else {
            $order = Order::where('id', $request['order_id'])->first();
            if($order && $order->is_guest){
                $orderDetails = Order::where('id', $request['order_id'])->whereHas('shippingAddress', function ($query) use ($request) {
                    $query->where('phone', $request->phone_number);
                })->first();

            }elseif ($user->phone == $request->phone_number) {
                $orderDetails = Order::where('id', $request['order_id'])->whereHas('details', function ($query) {
                    $query->where('customer_id', auth('customer')->id());
                })->first();
            }

            if ($request->from_order_details == 1) {
                $orderDetails = Order::where('id', $request['order_id'])->whereHas('details', function ($query) {
                    $query->where('customer_id', auth('customer')->id());
                })->first();
            }

        }

        $order_verification_status = Helpers::get_business_settings('order_verification');

        if (isset($orderDetails)) {
            return view(VIEW_FILE_NAMES['track_order'], compact('orderDetails','user_phone', 'order_verification_status'));
        }

        Toastr::error(translate('invalid_Order_Id_or_phone_Number'));
        return redirect()->back()->withInput();
    }

    public function track_last_order()
    {
        $orderDetails = OrderManager::track_order(Order::where('customer_id', auth('customer')->id())->latest()->first()->id);

        if ($orderDetails != null) {
            return view('web-views.order.tracking', compact('orderDetails'));
        } else {
            return redirect()->route('track-order.index')->with('Error', translate('invalid_Order_Id_or_phone_Number'));
        }

    }

    public function order_cancel($id)
    {
        $order = Order::where(['id' => $id])->first();
        if ($order['payment_method'] == 'cash_on_delivery' && $order['order_status'] == 'pending') {
            OrderManager::stock_update_on_order_status_change($order, 'canceled');
            Order::where(['id' => $id])->update([
                'order_status' => 'canceled'
            ]);
            Toastr::success(translate('successfully_canceled'));
        }elseif ($order['payment_method'] == 'offline_payment') {
            Toastr::error(translate('The_order_status_cannot_be_updated_as_it_is_an_offline_payment'));
        }else{
            Toastr::error(translate('status_not_changable_now'));
        }
        return back();
    }

    public function refund_request(Request $request, $id)
    {
        $order_details = OrderDetail::find($id);
        $user = auth('customer')->user();

        $wallet_status = Helpers::get_business_settings('wallet_status');
        $loyalty_point_status = Helpers::get_business_settings('loyalty_point_status');
        if ($loyalty_point_status == 1) {
            $loyalty_point = CustomerManager::count_loyalty_point_for_amount($id);

            if ($user->loyalty_point < $loyalty_point) {
                Toastr::warning(translate('you_have_not_sufficient_loyalty_point_to_refund_this_order').'!!');
                return back();
            }
        }

        return view('web-views.users-profile.refund-request', compact('order_details'));
    }

    public function store_refund(Request $request)
    {
        $request->validate([
            'order_details_id' => 'required',
            'amount' => 'required',
            'refund_reason' => 'required'

        ]);
        $order_details = OrderDetail::find($request->order_details_id);
        $user = auth('customer')->user();


        $loyalty_point_status = Helpers::get_business_settings('loyalty_point_status');
        if ($loyalty_point_status == 1) {
            $loyalty_point = CustomerManager::count_loyalty_point_for_amount($request->order_details_id);

            if ($user->loyalty_point < $loyalty_point) {
                Toastr::warning(translate('you_have_not_sufficient_loyalty_point_to_refund_this_order').'!!');
                return back();
            }
        }
        $refund_request = new RefundRequest;
        $refund_request->order_details_id = $request->order_details_id;
        $refund_request->customer_id = auth('customer')->id();
        $refund_request->status = 'pending';
        $refund_request->amount = $request->amount;
        $refund_request->product_id = $order_details->product_id;
        $refund_request->order_id = $order_details->order_id;
        $refund_request->refund_reason = $request->refund_reason;

        if ($request->file('images')) {
            $product_images = [];
            foreach ($request->file('images') as $img) {
                $product_images[] = ImageManager::upload('refund/', 'webp', $img);
            }
            $refund_request->images = json_encode($product_images);
        }
        $refund_request->save();

        $order_details->refund_request = 1;
        $order_details->save();

        $order = Order::find($order_details->order_id);
        OrderStatusEvent::dispatch('confirmed', 'customer', $order);

        Toastr::success(translate('refund_requested_successful!!'));
        return redirect()->route('account-order-details', ['id' => $order_details->order_id]);
    }

    public function generate_invoice($id)
    {
        $order = Order::with('seller')->with('shipping')->where('id', $id)->first();
        $data["email"] = $order->customer["email"];
        $data["order"] = $order;

        $mpdf_view = \View::make(VIEW_FILE_NAMES['order_invoice'], compact('order'));
        Helpers::gen_mpdf($mpdf_view, 'order_invoice_', $order->id);
    }

    public function refund_details($id)
    {
        $order_details = OrderDetail::find($id);
        $refund = RefundRequest::with(['product','order'])->where('customer_id', auth('customer')->id())
            ->where('order_details_id', $order_details->id)->first();
        $product = $this->product->find($order_details->product_id);
        $order = $this->order->find($order_details->order_id);

        if($product) {
            return view(VIEW_FILE_NAMES['refund_details'], compact('order_details', 'refund', 'product', 'order'));
        }

        Toastr::error(translate('product_not_found'));
        return redirect()->back();
    }

    public function submit_review(Request $request, $id)
    {
        $order_details = OrderDetail::where(['id' => $id])->whereHas('order', function ($q) {
            $q->where(['customer_id' => auth('customer')->id(), 'payment_status' => 'paid']);
        })->first();

        if (!$order_details) {
            Toastr::error(translate('invalid_order!'));
            return redirect('/');
        }

        return view('web-views.users-profile.submit-review', compact('order_details'));

    }

    public function refer_earn(Request $request)
    {
        $ref_earning_status = Helpers::get_business_settings('ref_earning_status') ?? 0;
        if(!$ref_earning_status){
            Toastr::error(translate('you_have_no_permission'));
            return redirect('/');
        }
        $customer_detail = User::where('id', auth('customer')->id())->first();

        return view(VIEW_FILE_NAMES['refer_earn'], compact('customer_detail'));
    }

    public function user_coupons(Request $request)
    {
        $seller_ids = Seller::approved()->pluck('id')->toArray();
        $seller_ids = array_merge($seller_ids, [NULL, '0']);

        $coupons = Coupon::with('seller')
                    ->where(['status' => 1])
                    ->whereIn('customer_id',[auth('customer')->id(), '0'])
                    ->whereIn('customer_id',[auth('customer')->id(), '0'])
                    ->whereDate('start_date', '<=', date('Y-m-d'))
                    ->whereDate('expire_date', '>=', date('Y-m-d'))
                    ->paginate(8);

        return view(VIEW_FILE_NAMES['user_coupons'], compact('coupons'));
    }
}
